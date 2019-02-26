<?php

require_once('./modules/istudent_module.php');
require_once('./modules/icress_module.php');
require_once('./modules/exportexcel_module.php');

CACHE_TYPE == 'file' ? require_once('./modules/file_module.php') : require_once('./modules/sqlite_module.php');

if(isset($_GET['getlist'])) {
    die(CACHE_TYPE == 'file' ? file_getJadual()
        : db_getJadual());
}

if(isset($_GET['getsubject'])) {
    if(!empty($_POST['faculty'])) {
        die(CACHE_TYPE == 'file' ? file_getFaculty($_POST['faculty'])
            : db_getFaculty($_POST['faculty']));
    }
}

if(isset($_GET['getgroup'])) {
    if(!empty($_POST['subject']) && !empty($_POST['faculty'])) {
        die(CACHE_TYPE == 'file' ? file_getSubject($_POST['faculty'], $_POST['subject'])
            : db_getSubject($_POST['faculty'], $_POST['subject']));
    }
}

if(isset($_GET['fetchDataMatrix'])) {
    if(!empty($_POST['studentId'])) {

        try {

            $obj = new IStudent($_POST['studentId']);

            $courses = $obj->getCourses();
            $uitmcode = $obj->getUiTMCode();

            if($courses === null || $uitmcode === false) {
                throw new Exception("Can't fetch resources for this student Id (" . $_POST['studentId'] . ") !");
            }

            die(json_encode(array(
                'Courses' => $courses,
                'UiTMCode' => $uitmcode)));

        } catch (Exception $e) {
            die('Alert_Error:' . htmlentities($e->getMessage()));
        }
    }
}

if(isset($_POST['exportTimetable'])) {
  if(!empty($_POST['timetableInfo'])) {
    $obj = new ExportExcel();
    $result = $obj->toExcel($_POST['timetableInfo']);
    print_r($result);
  }
}
