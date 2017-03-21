<?php

require_once('./config.php');
require_once('./modules/http_module.php');

# non-selangor UiTMs code
# refer `istudent_module.php` for description about each code
$list = array( 'AR', 'SI', 'JK', 'MA', 'SG', 'SP', 'AG', 'KP', 'BT', 'SA', 'KK', 'DU' );

function icress_getJadual() {

    global $list;

    $get = file_get_contents('http://' . ICRESS_URL . '/jadual/jadual/jadual.asp');
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    $collect = [];
    $selangor = [];

    # extract faculty name and its code
    preg_match_all('/(?<=value=")(\w*)-(.[^"]*)/', $get, $out);

    for ($i = 0; $i < count($out[1]); $i++) {

        if (in_array($out[1][$i], $list)) { # if non-selangor uitm
            $collect[] = array('code' => $out[1][$i], 'fullname' => $out[2][$i]);
        } else {
            $selangor[] = array($out[1][$i] => $out[2][$i]);
        }
    }

    # add dummy code for UiTM inside selangor
    $collect[] = array('code' => 'B', 'fullname' => 'Kampus Selangor');

    # save selangor's faculties
    file_put_contents("./cache/SELANGOR_FACULTIES.dat", json_encode($selangor));

    return json_encode($collect);
}

function icress_getFaculty($faculty) {

    # special case - if UiTM in selangor
    if ($faculty == 'B') {

        $selangor = json_decode(file_get_contents("./cache/SELANGOR_FACULTIES.dat"), true);

        # courses inside selangor will be inside here
        $courses = [];

        # referer - later use to get courses that mapped with faculty
        $courses_referer = [];

        $links = [];
        for ($i = 0; $i < count($selangor); $i++) {
            $code = array_keys($selangor[$i])[0];
            $links[] = "http://" . ICRESS_URL . "/jadual/{$code}/{$code}.html";
        }

        $return_data = http\http_request($links, null, null, count($links));

        foreach ($return_data as $data) {

            # extract the required data only
            preg_match_all('/\\\\(.*)\\\\(.*)\.html/', $data, $out);

            # save into arrays
            $courses_referer[$out[1][0]] = $out[2];
            $courses = array_merge($courses, $out[2]);
        }

        # cache selangor's faculties
        file_put_contents("./cache/SELANGOR_REFERER.dat", json_encode($courses_referer));

        return json_encode($courses);

    } else { # for non-selangor uitm

        $get = file_get_contents("http://" . ICRESS_URL . "/jadual/{$faculty}/{$faculty}.html");
        $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

        preg_match_all('/>(.*)<\//', $get, $out);
        return json_encode($out[1]);
    }
    
}

function icress_getSubject($faculty, $subject) {

    $subjects_output = [];

    # if UiTM inside selangor, then find the correct faculty
    if ($faculty == 'B') {

        $referer = json_decode(file_get_contents("./cache/SELANGOR_REFERER.dat"), true);
        foreach ($referer as $faculties => $subjects) {
            if (in_array($subject, $subjects)) {
                $curr = icress_getSubject_wrapper($faculties, $subject);

                # if multiple faculties contain the same subject 
                # then choose one with the highest amount of classes
                if (count($curr) > count($subjects_output)) {
                    $subjects_output = $curr;
                }
            }
        }

    } else {
        $subjects_output = icress_getSubject_wrapper($faculty, $subject);
    }

    return json_encode($subjects_output);
}

function icress_getSubject_wrapper($faculty, $subject) {

    # start fetching the icress data
    $jadual = file_get_contents("http://" . ICRESS_URL . "/jadual/{$faculty}/{$subject}.html");
    $http_response_header or die("Alert_Error: Icress timeout! Please try again later."); 

    # parse the html to more neat representation about classes
    $jadual = str_replace(array("\r", "\n"), '', $jadual);
    preg_match_all('#<td>(.*?)</td>#i', $jadual, $outs);
    $splits = array_chunk(array_splice($outs[1], 7), 7);
    $new = [];

    foreach ($splits as $split) {
        $new[$split[0]][] = $split;
        foreach ($new[$split[0]] as &$each) {
            unset($each[0]);
        }
    }

    return $new;
}

?>
