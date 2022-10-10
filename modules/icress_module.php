<?php

require_once('./config.php');
require_once('./modules/http_module.php');

function icress_getJadual() {

    $get = file_get_contents('https://' . ICRESS_URL . '/timetable_/search.asp');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

	$get = cleanHTML($get);
		
    $collect = [];

	// set error level
	$internalErrors = libxml_use_internal_errors(true);

    # extract campus name and its code
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($get);

	// Restore error level
	libxml_use_internal_errors($internalErrors);

	$options = $htmlDoc->getElementsByTagName('option');

	for ($i = 0; $i < count($options); $i++) {
		if ($i === 0) {
			continue;
		}

		$value = trim($options[$i]->nodeValue);
		$value = explode('-', $value, 2);

		$code = isset($value[0]) ? $value[0] : "";
		$fullname = isset($value[1]) ? $value[1] : "";

		if (is_null($fullname) || $fullname == "") {
			continue;
		}

		$collect[] = array('code' => $code, 'fullname' => $fullname);
	}

    return json_encode($collect);
}

function icress_getCampus($campus, $faculty) {
		$form_names = getFormNames();

		$postdata = http_build_query(
				array(
						$form_names['search_campus'] => $campus,
						$form_names['search_faculty'] => $faculty
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
		
		$get = file_get_contents('https://' . ICRESS_URL . '/timetable_/search.asp', false, $context);
		$http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

		$get = cleanHTML($get);

		// set error level
		$internalErrors = libxml_use_internal_errors(true);
		$htmlDoc = new DOMDocument();
		$htmlDoc->loadHTML($get);
		// Restore error level
		libxml_use_internal_errors($internalErrors);

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
    $jadual = file_get_contents("https://" . ICRESS_URL . "/timetable_/list/{$campus}/{$faculty}/{$subject}.html");
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 
	
    # parse the html to more neat representation about classes
    $jadual = str_replace(array("\r", "\n"), '', $jadual);

	// set error level
	$internalErrors = libxml_use_internal_errors(true);
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($jadual);
	// Restore error level
	libxml_use_internal_errors($internalErrors);

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

function cleanHTML($html) {
	$patern = "/<SCRIPT.*?>(.*?)<\/SCRIPT>/si";
	preg_match_all($patern, $html, $parsed);

	$rm_script = $parsed[0][0];

	$html = str_replace($rm_script, "", $html);
	
	return $html;
}

function getFormNames() {
	$get = file_get_contents('https://' . ICRESS_URL . '/timetable_/search.asp');
	$http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 
	$get = cleanHTML($get);

	// set error level
	$internalErrors = libxml_use_internal_errors(true);
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($get);
	// Restore error level
	libxml_use_internal_errors($internalErrors);

	$selectCampusElem = $htmlDoc->getElementById('search_campus');
	$selectCampus = $selectCampusElem->getAttribute('name');

	$searchFacultyElem = $htmlDoc->getElementById('search_faculty');
	$searchFaculty = $searchFacultyElem->getAttribute('name');


	return [
		'search_campus' => $selectCampus,
		'search_faculty' => $searchFaculty
	];
}

?>
