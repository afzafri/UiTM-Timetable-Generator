<?php


require_once("./http.php");

class IStudent {

    private $url = "http://i-learn.uitm.edu.my";
    private $cookie = null;

    // array containing list of courses
    private $courses = null;


    // cache bottom.php's result
    private $data = null;

    function __construct($student_id) {
        $this->login($student_id);
    }

    private function login($student_id) {

        $get = http\http_request($this->url . "/studentLogin.php",
            NULL,
            'page=simsweb.uitm.edu.my&nopelajar=' . $student_id . '&login=' . $student_id . 
            '&search.x=210&search.y=57');

        preg_match('#Set-Cookie: (.*?);#', $get, $out); // extract cookie
        $this->cookie = $out[1];

    }

    private function requestData() {

        // only make request once 
        if($this->data == null) {
            $this->data = http\http_request($this->url . "/modules/main/bottom.php",
                                            $this->cookie, NULL);
        }

        return $this->data;
    } 

    public static function getUITMCode($str) {

        $referer = array(
            "Arau" => "AR"
        );

        foreach($referer as $refer => $kod) {
            if(strpos($str, $refer) !== FALSE) {
                return $refer;
            }
        }

        return FALSE;
    }

    public function getCourses() {

        // for first time
        // get the courses data from istudent
        if($this->courses == null) {

            preg_match_all('#title="(.*?)".*php\?cid=(.*?)&#', $this->requestData(), $courses);

            for($i = 0; $i < count($courses[1])-1; $i += 2) {
                $this->courses[$courses[1][$i]] = $courses[1][$i+1];
            }
        }

        return $this->courses;
    }

}

$a = new IStudent("2014423424");
var_dump($a->getCourses());

?>
