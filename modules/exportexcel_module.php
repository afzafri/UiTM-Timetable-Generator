<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportExcel {

  private $msg = null;

  public function toExcel($data) {
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
        $server_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
        $msg = $server_link.'download/'.$filename;

      }
      catch (Exception $e) {
        $msg = "Failed to export. ".$e->getMessage();
      }

      return $msg;
  }

}

 ?>
