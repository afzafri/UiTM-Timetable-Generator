<?php

require_once('./config.php');
require_once('./modules/http_module.php');

function icress_getJadual() {
	
    $get = file_get_contents(getTimetableURL() . 'cfc/select.cfc?method=find_cam_icress_student&key=All&page=1&page_limit=30');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    $data = json_decode($get, true);
	$collect = [];

	foreach ($data['results'] as $result) {
		$code = $result['id'];
		$fullname = $result['text'];

		if ($result['id'] === 'X') {
			continue;
		} else if (strpos($fullname, 'SELANGOR') === false) {
			$fullname = explode('-', $fullname, 2)[1];
		}

		$collect[] = array('code' => $code, 'fullname' => $fullname);
	}

    return json_encode($collect);
}

function icress_getFaculty() {
	
    $get = file_get_contents(getTimetableURL() . 'cfc/select.cfc?method=find_fac_icress_student&key=All&page=1&page_limit=30');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    $data = json_decode($get, true);
	$collect = [];

	foreach ($data['results'] as $result) {
		$code = $result['id'];
		$fullname = explode('-', $result['text'], 2)[1];

		$collect[] = array('code' => $code, 'fullname' => $fullname);
	}

    return json_encode($collect);
}

function icress_getCampus($campus, $faculty) {
		// $form_names = getFormNames();

		$postdata = http_build_query(
				array(
						// $form_names['search_campus'] => $campus,
						// $form_names['search_faculty'] => $faculty
						'search_campus' => $campus,
						'search_faculty' => $faculty,
						'search_course' => '',
				)
		);
		
		$options = array('http' =>
				array(
						'method'  => 'POST',
						'header'  => "Content-Type: application/x-www-form-urlencoded\nReferer: https://simsweb4.uitm.edu.my/estudent/class_timetable/index.htm",
						'content' => $postdata
				)
		);
		
		$context  = stream_context_create($options);
		
		$get = file_get_contents(getTimetableURL() . 'index_result.cfm', false, $context);
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
			$subject = rtrim($row->childNodes[3]->nodeValue);
			$buttons = $row->getElementsByTagName('button');
			$onclick = $buttons[0]->getAttribute('onclick');
			$path = explode("'", $onclick)[1];

			$subjects[] = array('subject' => $subject, 'path' => $path);
		}

		return json_encode($subjects);
}

function icress_getSubject($path) {
	
    $subjects_output = [];
    
	$subjects_output = icress_getSubject_wrapper($path);

    return json_encode($subjects_output);
}

function icress_getSubject_wrapper($path) {

    # start fetching the icress data
    $jadual = file_get_contents(getTimetableURL(true) . $path);
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
	$get = file_get_contents(getTimetableURL());
	$http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 
	$get = cleanHTML($get);

	// set error level
	$internalErrors = libxml_use_internal_errors(true);
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($get);
	// Restore error level
	libxml_use_internal_errors($internalErrors);

	$selectCampusElem = $htmlDoc->getElementById('search_cam');
	$selectCampus = $selectCampusElem->getAttribute('name');

	// $searchFacultyElem = $htmlDoc->getElementById('search_faculty');
	$searchFacultyElem = $htmlDoc->getElementById('eyJ0eXAiOiiiJKV1QiLCJhbGciOiJIUzI1NiJ9');
	$searchFaculty = $searchFacultyElem->getAttribute('name');


	return [
		'search_campus' => $selectCampus,
		'search_faculty' => $searchFaculty
	];
}

function extractRedirect($url) {
	$response = file_get_contents($url);
	$http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

	if (preg_match('/window\.location\.replace\([\s]{0,}[\"\'](.*)[\"\'][\s]{0,}\)/i', $response, $redirect_result['1']) ||
		preg_match('/\$\(location\)\.attr\([\s]{0,}[\"\'][\s]{0,}href[\s]{0,}[\"\'][\s]{0,}\,[\s]{0,}[\"\'][\s]{0,}(.*)[\s]{0,}[\"\'][\s]{0,}\)/i', $response, $redirect_result['2']) ||
		preg_match('/window\.location[\s]{0,}\=[\s]{0,}[\"\'](.*)[\"\']/i', $response, $redirect_result['3']) ||
		preg_match('/window\.location\.href[\s]{0,}\=[\s]{0,}[\"\'](.*)[\"\']/i', $response, $redirect_result['4']) ||
		preg_match('/window\.href[\s]{0,}\=[\s]{0,}[\"\'](.*)[\"\']/i', $response, $redirect_result['5']) ||
		preg_match('/<[\s]*meta[\s]*http-equiv="?REFRESH"?' . '[\s]*content="?[0-9]*;[\s]*URL[\s]*=[\s]*([^>"]*)"?' . '[\s]*[\/]?[\s]*>/si', $response, $redirect_result['6'])) {
		for ($i = 1; $i <= 6; $i++) {
			if ($redirect_result[$i]) {
				$window_location_final = $redirect_result[$i];
				break;
			}
		}
		$window_location_final = end($window_location_final);
		if (substr($window_location_final, 0, 1) === '/' && trim($redirect_url)) {
			$window_location_final = rtrim($redirect_url, '/') . $window_location_final;
		}
		$window_location_final = trim($window_location_final) ? trim($window_location_final) : '';
		if ($window_location_final) {
			$redirect_url = $window_location_final;
		}    

		return $redirect_url;
	}

	return '';
}

function getTimetableURL() {
	return "https://simsweb4.uitm.edu.my/estudent/class_timetable/";
}

?>
