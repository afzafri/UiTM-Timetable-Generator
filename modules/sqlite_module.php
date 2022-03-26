<?php

require_once('./config.php');

$pdo = null;

$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {

    if(!file_exists(CACHE_DBFILENAME)) {

        $pdo = new PDO('sqlite:' . CACHE_DBFILENAME, null, null, $opt);
        $create_query = file_get_contents('./modules/sqlite_template.sql');
        $pdo->exec($create_query);

    }

    if($pdo == null) {
        $pdo = new PDO('sqlite:' . CACHE_DBFILENAME, null, null, $opt);
    }

    function db_getJadual() {
        global $pdo;
        $data = $pdo->query("SELECT data, strftime('%s', date) FROM data WHERE name = 'jadual'")->fetchAll()[0];
        $cache = null;

        if (empty($data) || (isset($data['date']) && computeOldDate($data['date']) > CACHE_TIMELEFT)) {

            $cache = icress_getJadual();

            if(empty($data))
                $sth = $pdo->prepare("INSERT INTO data
                                      VALUES ('jadual', ?, datetime('now'))")->execute(array($cache));

            else if (computeOldDate($data['date']) > CACHE_TIMELEFT)
                $sth = $pdo->prepare("UPDATE data
                                      set data = ?, date = datetime('now')
                                      WHERE name = 'jadual'")->execute(array($cache));

        } else $cache = trim($data['data']);

        return $cache;
    }

    function db_getCampus($campus) {
        global $pdo;
        $sth = $pdo->prepare("SELECT data, strftime('%s', date) FROM data WHERE name = ?");
        $sth->execute([$campus]);
        $data = $sth->fetchAll()[0];

        $cache = null;

        if (empty($data) || (isset($data['date']) && computeOldDate($data['date']) > CACHE_TIMELEFT)) {

            $cache = icress_getCampus($campus);

            if(empty($data))
                $sth = $pdo->prepare("INSERT INTO data
                                      VALUES (?, ?, datetime('now'))")->execute(array($campus, $cache));

            else if (computeOldDate($data['date']) > CACHE_TIMELEFT)
                $sth = $pdo->prepare("UPDATE data
                                      set data = ?, date = datetime('now')
                                      WHERE name = ?")->execute(array($cache, $campus));

        } else $cache = trim($data['data']);

        return $cache;
    }

    function db_getSubject($campus, $subject) {
        global $pdo;
        $name = $campus . '-' . $subject;

        $sth = $pdo->prepare("SELECT data, strftime('%s', date) FROM data WHERE name = ?");
        $sth->execute([$name]);
        $data = $sth->fetchAll()[0];

        $cache = null;

        if (empty($data) || (isset($data['date']) && computeOldDate($data['date']) > CACHE_TIMELEFT)) {

            $cache = icress_getSubject($campus, $subject);

            if(empty($data))
                $sth = $pdo->prepare("INSERT INTO data
                                      VALUES (?, ?, datetime('now'))")->execute(array($name, $cache));

            else if (computeOldDate($data['date']) > CACHE_TIMELEFT)
                $sth = $pdo->prepare("UPDATE data
                                      set data = ?, date = datetime('now')
                                      WHERE name = ?")->execute(array($cache, $name));

        } else $cache = trim($data['data']);

        return $cache;
    }

} catch(PDOException $e) {
    /* If PDO fails we handle it here */
    echo "Your query failed: " . $e->getMessage();
}

function computeOldDate($date) {
    return (time() -  $date) / 60 / 60;
} 

?>

