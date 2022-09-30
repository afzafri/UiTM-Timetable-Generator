<?php

require_once('./config.php');
require_once('./modules/http_module.php');

function icress_getJadual() {

    $get = file_get_contents('https://' . ICRESS_URL . '/timetable/search.asp');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 
		
    $collect = [];

    # extract campus name and its code
		$htmlDoc = new DOMDocument();
		$htmlDoc->loadHTML($get);
		$options = $htmlDoc->getElementsByTagName('option');

		for ($i = 0; $i < count($options); $i++) {
			if ($i === 0) {
				continue;
			}

			$value = trim($options[$i]->nodeValue);
			$value = explode('-', $value, 2);

			if (is_null($value[1])) {
				continue;
			}

			$collect[] = array('code' => $value[0], 'fullname' => $value[1]);
		}

    return json_encode($collect);
}

function icress_getCampus($campus, $faculty) {
		$postdata = http_build_query(
				array(
						'search_campus' => $campus,
						'search_faculty' => $faculty
				)
		);
		
		$options = array('http' =>
				array(
						'method'  => 'POST',
						'header'  => 'Content-Type: application/x-www-form-urlencoded',
						'content' => $postdata
				)
		);
		
		$context  = stream_context_create($options);
		
		$get = file_get_contents('https://' . ICRESS_URL . '/timetable/search.asp', false, $context);
		$http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

		$htmlDoc = new DOMDocument();
		$htmlDoc->loadHTML($get);
		$tableRows = $htmlDoc->getElementsByTagName('tr');
		$subjects = [];

		foreach ($tableRows as $key => $row) {
			if ($key === 0) {
				continue;
			}
			$subject = $row->childNodes[3]->nodeValue;
			$subjects[] = array($subject);
		}

		return json_encode($subjects);
}

function icress_getSubject($campus, $faculty, $subject) {

    $subjects_output = [];
    
		$subjects_output = icress_getSubject_wrapper($campus, $faculty, $subject);

    return json_encode($subjects_output);
}

function icress_getSubject_wrapper($campus, $faculty, $subject) {

    # start fetching the icress data
    $jadual = file_get_contents("https://" . ICRESS_URL . "/timetable/list/{$campus}/{$faculty}/{$subject}.html");
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    # parse the html to more neat representation about classes
    $jadual = str_replace(array("\r", "\n"), '', $jadual);

		$htmlDoc = new DOMDocument();
		$htmlDoc->loadHTML($jadual);
		$tableRows = $htmlDoc->getElementsByTagName('tr');
		$groups = [];

		foreach ($tableRows as $key => $row) {
			if ($key === 0 || $key === 1) {
				continue;
			}
			$tableDatas = [];
			foreach($row->childNodes as $tableData) {
				if (strcmp($tableData->nodeName, 'td') === 0) {
					array_push($tableDatas, $tableData->nodeValue);
				}
			}

			$group = trim($row->childNodes[5]->nodeValue);
			array_shift($tableDatas);
			$groups[$group][] = $tableDatas;
		}

    return $groups;
}

?>
