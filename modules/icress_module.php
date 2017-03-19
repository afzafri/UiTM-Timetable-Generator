<?php

require_once('./config.php');

function icress_getJadual() {

    $get = file_get_contents('http://' . ICRESS_URL . '/jadual/jadual/jadual.asp');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    $collect = array();
    preg_match_all('/(?<=value=")(\w*)-(.[^"]*)/', $get, $out);

    for ($i = 0; $i < count($out[1]); $i++) 
        $collect[] = array('code' => $out[1][$i], 'fullname' => $out[2][$i]);

    return json_encode($collect);
}

function icress_getFaculty($faculty) {

    $get = file_get_contents("http://" . ICRESS_URL . "/jadual/{$faculty}/{$faculty}.html");
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    preg_match_all('/>(.*)<\//', $get, $out);

    return json_encode($out[1]);
}

function icress_getSubject($faculty, $subject) {

    //start fetch icress data
    $jadual = file_get_contents("http://" . ICRESS_URL . "/jadual/{$faculty}/{$subject}.html");
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    $jadual = str_replace(array("\r", "\n"), '', $jadual);
    preg_match_all('#<td>(.*?)</td>#i', $jadual, $outs);
    $splits = array_chunk(array_splice($outs[1], 7), 7);
    $new = array();

    foreach ($splits as $split) {
        $new[$split[0]][] = $split;
        foreach ($new[$split[0]] as &$each) {
            unset($each[0]);
        }
    }

    return json_encode($new);
}

?>
