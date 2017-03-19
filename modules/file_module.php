<?php

require_once('./config.php');

if (!file_exists('./cache')) {
    mkdir('./cache', 0777, true);
}

function file_getJadual() {

    $filename = './cache/jadual.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getJadual());

    return file_get_contents($filename);
}

function file_getFaculty($faculty) {

    $filename = './cache/' . $faculty . '.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getFaculty($faculty));

    return file_get_contents($filename);
}

function file_getSubject($faculty, $subject) {

    $filename = './cache/' . $_POST['faculty'] . '-' . $_POST['subject'] . '.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getSubject($faculty, $subject));

    return file_get_contents($filename);
}



/**
 * Return file age by hour(s)
 */
function getFileOld($file) {
    return (time() - filemtime($file)) / 60 / 60;
}

?>
