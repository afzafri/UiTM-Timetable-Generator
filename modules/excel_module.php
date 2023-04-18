<?php

require 'vendor/autoload.php';
require_once('uploader_module.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @deprecated
 */
class Excel {

  private $msg = null;

  public function exportExcel($data) {
      $timetable = json_decode($data, true);

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      $count = 2;

      $sheet->setCellValue('A1', 'DAY');
      $sheet->setCellValue('B1', 'SUBJECT');
      $sheet->setCellValue('C1', 'GROUP');
      $sheet->setCellValue('D1', 'PLACE');
      $sheet->setCellValue('E1', 'START TIME');
      $sheet->setCellValue('F1', 'END TIME');

      foreach ($timetable as $tb) {
        $day = $tb['day'];
        $subject = $tb['subject'];
        $group = $tb['group'];
        $classroom = $tb['classroom'];
        $class_start = $tb['class_start'];
        $class_end = $tb['class_end'];

        $sheet->setCellValue('A'.$count, $day);
        $sheet->setCellValue('B'.$count, $subject );
        $sheet->setCellValue('C'.$count, $group);
        $sheet->setCellValue('D'.$count, $classroom);
        $sheet->setCellValue('E'.$count, $class_start);
        $sheet->setCellValue('F'.$count, $class_end);

        $count++;
      }

      $writer = new Xlsx($spreadsheet);
      $filename = 'UiTM-Timetable-'.rand().'.xlsx';

      try {
        $writer->save('download/'.$filename);
        $server_link = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $msg = $server_link . '/download/' . $filename;

      }
      catch (Exception $e) {
        $msg = "Failed to export. ".$e->getMessage();
      }

      return trim($msg);
  }

  public function importExcel($data) {

    // upload file first
  	$obj = new Uploader();
  	$obj->dir = "./upload/"; //directory to store the image/file
  	$obj->files = $data; //receive from form
  	$obj->filetype = array('xlsx','xls'); //set the allowed image/file extensions
  	$obj->size = 1000000; //set file/image size limit. note: 100000 is 100KB
  	$stat = json_decode($obj->upload(), true);

    $updFilename = null;
    $result = [];

    if(array_key_exists('errors', $stat))
    {
      $msg = $stat['errors']['status'];
      $result['status'] = false;
      $result['message'] = $msg;

      return json_encode($result);
    }
    else
    {
      $updFilename = $stat['success']['filename'];
      $msg = $stat['success']['status'];
      $result['status'] = true;
      $result['message'] = $msg;

      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $spreadsheet = $reader->load("./upload/".$updFilename);
      $result['timetable'] = $spreadsheet->getActiveSheet()->toArray();

      return json_encode($result);
    }
    
    return false;
  }

}

 ?>
