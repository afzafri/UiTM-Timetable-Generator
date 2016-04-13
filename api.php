<?php

define('URL', 'icress.uitm.edu.my');

if(isset($_GET['getlist'])) {

    $get = file_get_contents('http://' . URL . '/jadual/jadual/jadual.asp');
    preg_match_all('/(?<=value=")(\w*)-(.[^"]*)/', $get, $out);

    $collect = array();

    for($i = 0; $i < count($out[1]); $i++) {
        $collect[] = array('code' => $out[1][$i], 'fullname' => $out[2][$i]);
    }

    die(json_encode($collect));
}

if(isset($_GET['getsubject'])) {
    if(!empty($_POST['faculty'])) {

        $get = file_get_contents("http://" . URL . "/jadual/{$_POST['faculty']}/{$_POST['faculty']}.html");
        preg_match_all('/>(.*)<\//', $get, $out);

        die(json_encode($out[1]));
    }
}

if(isset($_GET['getgroup'])) {
    if(!empty($_POST['subject']) && !empty($_POST['faculty'])) {

        //start fetch icress data
        $jadual = file_get_contents("http://" . URL . "/jadual/{$_POST['faculty']}/{$_POST['subject']}.html");
        $jadual = str_replace(array("\r", "\n"), '', $jadual);

        preg_match_all('#<td>(.*?)</td>#i', $jadual, $outs);

        $splits = array_chunk(array_splice($outs[1], 7), 7);
        $new = array();

        foreach($splits as $split) {
            $new[$split[0]][] = $split;
            foreach($new[$split[0]] as &$each) {
                unset($each[0]);
            }
        }
        //end fetch icress data

        die(json_encode($new));

    }
}