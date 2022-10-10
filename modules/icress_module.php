<?php

require_once('./config.php');
require_once('./modules/http_module.php');

function icress_getJadual() {
	
    $get = file_get_contents(getTimetableURL());
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
		
		$get = file_get_contents(getTimetableURL(), false, $context);
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
    $jadual = file_get_contents(getTimetableURL(true) . "/list/{$campus}/{$faculty}/{$subject}.html");
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

	$selectCampusElem = $htmlDoc->getElementById('search_campus');
	$selectCampus = $selectCampusElem->getAttribute('name');

	$searchFacultyElem = $htmlDoc->getElementById('search_faculty');
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

function getTimetableURL($directoryOnly = false) {
	$redirect_url = 'https://' . ICRESS_URL;
	$redirects = [];

	// if extractRedirect return string, continue to extractRedirect
	// else, break the loop, return $redirect_url
	while ($redirect_url = extractRedirect($redirect_url)) {
		$redirects[] = $redirect_url;
	}
	
	$final = sizeof($redirects) > 0 ? $redirects[sizeof($redirects) - 1] : '';

	// if $directoryOnly is true, return the directory only
	if ($directoryOnly) {
		$final = substr($final, 0, strrpos($final, '/') + 1);
		$final = rtrim($final, '/');
	}

	return $final;
}

?>
