<?php

require_once("./modules/http_module.php");

class IStudent {

    private $url = "http://i-learn.uitm.edu.my";

    private $cookie = null;

    // array containing list of courses
    private $courses = null;

    // uitm campus string
    private $uitm = null;

    // cache bottom.php's result
    private $data = null;

    // <regex> => "Icress Code"
    private $referer = array(
        "Arau" => "AR", # perlis
        "Seri Iskandar|Ipoh|Teluk Intan|Tapah" => "SI", # perak
        "Jengka|Kuantan|Raub|Bukit Sekilau" => "JK", # pahang
        "Machang|Kota Bharu" => "MA", # kelantan
        "Segamat|Johor Bahru|Larkin|Pasir Gudang" => "SG", # johor
        "Sungai Petani|Alor Setar" => "SP", # kedah
        "Alor Gajah|Melaka|Jasin" => "AG", # melaka
        "Kuala Pilah|Seremban|Rembau" => "KP", # negeri sembilan
        "Bukit Mertajam|Bertam|Balik Pulau" => "BT", # pulau pinang 
        "Samarahan|Kuching|Mukah|Miri" => "SA", # sarawak
        "Kota Kinabalu|Tawau" => "KK", # sabah
        "Dungun|Marang|Kuala Terengganu|Bukit Besi" => "DU", # terengganu

        # special case (later i'll handle this case)
        "Shah Alam|Dengkil|Kuala Selangor|Seksyen 17|Putra Jaya|Golden Hope|Sg. Buloh|Selayang|Puncak Alam|Puncak Perdana" => "SELANGOR",

    );

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

    private function getUiTMStr() {

        if($this->uitm == null) {

            // extract uitm campus location
            preg_match('#<BR>Campus.*:.*<b>([A-Za-z0-9 ]+)<\/b>#',
                $this->requestData(), $uitm); 

            $this->uitm = $uitm[1];

        }

        return $this->uitm;
    }

    public function getUiTMCode() {

        foreach($this->referer as $refer => $kod) {
            if(preg_match("/" . $refer . "/i" , $this->getUiTMStr())) {
                return $kod;
            }
        }

        return false;
    }

    public function getCourses() {

        // for first time
        // get the courses data from istudent
        if($this->courses == null) {

            preg_match_all('/--\>\n.*title="(.*?)".*php\?cid=(.*?)&/', $this->requestData(), $courses);

            if(empty($courses[0])) {

                // if system can't fetch data in regular way
                // then there is an alternative ;)
                $this->getCoursesAlternative();

            } else {
                for($i = 0; $i < count($courses[1]); $i++) {
                    $this->courses[$courses[2][$i]] = $courses[1][$i]; // [subject] = group
                }
            }
        }

        return $this->courses;
    }

    private function getCoursesAlternative() {

        // if can't get courses & groups an easy way
        // then do it a long and time consuming way

        preg_match_all('#courseframe\.php\?cid=(.*?)&#', $this->requestData(), $courses);
        
        foreach($courses[1] as $course) {

            $get = http\http_request($this->url . "/Group/default.php?ttype=course&courseID=" . $course,
                $this->cookie, NULL);

            preg_match('#>(.*?)<\/a>#', $get, $group);
            $this->courses[$course] = $group[1];

        }

    }
}

?>

