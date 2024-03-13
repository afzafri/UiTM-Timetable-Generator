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

function file_getFaculty() {

    $filename = './cache/faculty.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getFaculty());

    return file_get_contents($filename);
}

function file_getCampus($campus, $faculty) {

		$filename = empty($faculty) ?
				'./cache/' . $campus . '.dat' :
				'./cache/' . $campus . '-' . $faculty . '.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getCampus($campus, $faculty));

    return file_get_contents($filename);
}

function file_getSubject($campus, $faculty, $subject) {
    $subjects = file_getCampus($campus, $faculty);
    $subjects = json_decode($subjects, true);
    $path = '';

    foreach ($subjects as $s) {
        if ($s['subject'] == $subject) {
            $path = $s['path'];
        }
    }

		$filename = empty($faculty) ?
    		'./cache/' . $_POST['campus'] . '-' . $_POST['subject'] . '.dat' :
    		'./cache/' . $_POST['campus'] . '-' . $_POST['faculty'] . '-' . $_POST['subject'] . '.dat';

    if (!file_exists($filename) || getFileOld($filename) > CACHE_TIMELEFT)
        file_put_contents($filename, icress_getSubject($path));

    return file_get_contents($filename);
}



/**
 * Return file age by hour(s)
 */
function getFileOld($file) {
    return (time() - filemtime($file)) / 60 / 60;
}

?>
