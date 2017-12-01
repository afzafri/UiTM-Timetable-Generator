<?php

require_once("./modules/http_module.php");

class IStudent {

    private $url = "http://i-learn.uitm.edu.my";

    private $cookie = null;

    # array containing list of courses
    private $courses = null;

    # uitm campus string
    private $uitm = null;

    # cache bottom.php's result
    private $data = null;

    # <regex> => "Icress Code"
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

        # special case
        "Shah Alam|Dengkil|Kuala Selangor|Seksyen 17|Putra Jaya|Golden Hope|Sg. Buloh|Selayang|Puncak Alam|Puncak Perdana" => "B",

    );

    function __construct($student_id) {
        $this->login($student_id);
    }

    private function login($student_id) {

        # make initial request to ilearn
        $post_data = 'page=simsweb.uitm.edu.my&nopelajar=' . $student_id . '&login=' . $student_id . '&search.x=210&search.y=57';
        $inital_data = http\http_request($this->url . "/studentLogin.php", NULL, $post_data);

        if (empty($inital_data)) {
            throw new Exception("iStudent login failed! Server maybe currently down right now.");
        }

        preg_match("/Location: (.*)/", $inital_data, $out);

        # verify using its link
        $verify_data = http\http_request(trim($out[1]));

        if (empty($verify_data)) {
            throw new Exception("iStudent url verification failed! Please use manual method for now.");
        }

        # extract cookie
        preg_match("/Set-Cookie: (.*?);/", $verify_data, $out);
        $this->cookie = trim($out[1]);
    }

    private function requestData() {

        # only make request once 
        if($this->data == null) {
            $this->data = http\http_request($this->url . "/v3/users/profile", $this->cookie, NULL);
        }

        return $this->data;
    } 

    private function getUiTMStr() {

        if($this->uitm == null) {

            # extract uitm campus location
            preg_match("/<p style=\"color:#327FA8\">(.*?)<\/p>/", $this->requestData(), $uitm);
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

        # for first time
        # get the courses data from istudent
        if($this->courses == null) {

            # check if student has completed the SuFO
            if (preg_match("/blocked until SuFO/", $this->requestData())) {
                throw new Exception("Auto fetcher won't function well if you still not completed all your SuFO yet!");
            }

            preg_match_all("/AppModel\.AddSdbrCourseGroup\('(.*?)','[0-9]+','(.*?)'\);/", $this->requestData(), $courses);

            for($i = 0; $i < count($courses[1]); $i++) {
                $this->courses[$courses[1][$i]] = $courses[2][$i]; # [subject] = group
            }
        }

        return $this->courses;
    }
}

?>

