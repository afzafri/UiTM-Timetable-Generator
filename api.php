<?php

define('URL', 'icress.uitm.edu.my');
define('CACHE_TIMELEFT', 1); // in hours

if (!file_exists('./cache')) {
    mkdir('./cache', 0777, true);
}

if(isset($_GET['getlist'])) {

    $filename = './cache/jadual.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT) {

        $get = file_get_contents('http://' . URL . '/jadual/jadual/jadual.asp');

        if($http_response_header == null) {
            die("icress_timeout");
        }

        preg_match_all('/(?<=value=")(\w*)-(.[^"]*)/', $get, $out);

        $collect = array();

        for ($i = 0; $i < count($out[1]); $i++) {
            $collect[] = array('code' => $out[1][$i], 'fullname' => $out[2][$i]);
        }

        file_put_contents($filename, json_encode($collect));
    }

    die(file_get_contents($filename));
}

if(isset($_GET['getsubject'])) {
    if(!empty($_POST['faculty'])) {

        $filename = './cache/' . $_POST['faculty'] . '.dat';

        if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT) {

            $get = file_get_contents("http://" . URL . "/jadual/{$_POST['faculty']}/{$_POST['faculty']}.html");

            if($http_response_header == null) {
                die("icress_timeout");
            }

            preg_match_all('/>(.*)<\//', $get, $out);
            file_put_contents($filename, json_encode($out[1]));
        }

        die(file_get_contents($filename));
    }
}

if(isset($_GET['getgroup'])) {
    if(!empty($_POST['subject']) && !empty($_POST['faculty'])) {

        $filename = './cache/' . $_POST['faculty'] . '-' . $_POST['subject'] . '.dat';

        if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT) {

            //start fetch icress data
            $jadual = file_get_contents("http://" . URL . "/jadual/{$_POST['faculty']}/{$_POST['subject']}.html");

            if($http_response_header == null) {
                die("icress_timeout");
            }

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

            //end fetch icress data

            file_put_contents($filename, json_encode($new));
        }

        die(file_get_contents($filename));

    }
}

/**
 * Return file age by hour(s)
 */
function getFileOld($file) {
    return (time() - filemtime($file)) / 60 / 60;
}
