<?php

class ExportExcel {

  public function toExcel($data) {
      $timetable = json_decode($data, true);

      $day = $timetable[0]['day'];
      $subject = $timetable[0]['subject'];
      $group = $timetable[0]['group'];
      $classroom = $timetable[0]['classroom'];
      $class_start = $timetable[0]['class_start'];
      $class_end = $timetable[0]['class_end'];

      return $subject;
  }

}

 ?>
