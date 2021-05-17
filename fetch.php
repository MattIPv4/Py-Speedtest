<?php

require_once('config.php');

if (!isset($_GET['device']) || !isset($_GET['time'])) {
    die('Missing url params');
}

$pdo = new PDO(
    'mysql:host=' . PDO_HOST . ';dbname=server_speeds',
    PDO_USER,
    PDO_PASS);

try {

    $stmt = $pdo->prepare('SELECT DISTINCT device FROM speeds');
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    //echo 'Data Export: Get Unique: Caught exception: ',  $e->getMessage();
    die('Device DB fetch failure');
}

if (!in_array($_GET['device'], $devices)) {
    die('Device not known');
}

try {
    $data = [];
    $time = (ctype_digit($_GET['time']) ? strtotime('-' . $_GET['time'] . ' hours') : $_GET['time']);

    $datetimestamp = 'UNIX_TIMESTAMP(STR_TO_DATE(datetime, \'%Y%m%d%H%i%s\'))';

    $cols = <<<SQL
    $datetimestamp AS datetimestamp,
    DATE_FORMAT(STR_TO_DATE(datetime, '%Y%m%d%H%i%s'), '%Y-%m-%dT%TZ') AS datetime,
    CAST(ping AS DECIMAL(10, 6)) AS ping,
    CAST(download AS DECIMAL(20, 6)) / 1000000 AS download,
    CAST(upload AS DECIMAL(20, 6)) / 1000000 AS upload
SQL;

    $cols_cast = [
        ['ping', 'CAST(ping AS DECIMAL(10, 6))'],
        ['download', 'CAST(download AS DECIMAL(20, 6)) / 1000000'],
        ['upload', 'CAST(upload AS DECIMAL(20, 6)) / 1000000']
    ];

    // Handle time range
    if (ctype_digit($time)) {
        $query = "SELECT $cols FROM speeds WHERE device = ? AND $datetimestamp > ? ORDER BY datetimestamp DESC";
        echo $query;
        echo $time;
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['device'], $time]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle current
    else if ($time == "current") {
        $stmt = $pdo->prepare("SELECT $cols FROM speeds WHERE device = ? ORDER BY datetimestamp DESC LIMIT 1");
        $stmt->execute([$_GET['device']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle top/bottom/average
    else if (in_array($time, ["average", "top", "bottom"])) {
        $operator = ($time == "average" ? "AVG" : ($time == "top" ? "MAX" : "MIN"));
        foreach ($cols_cast as &$col) {
            $query = <<<SQL
SELECT
       COUNT(1) AS size,
       $operator($col[1]) AS $col[0],
       MAX($datetimestamp) - MIN($datetimestamp) AS datetimestamp
FROM speeds
WHERE device = ?
  AND $col[1] < (
        (SELECT AVG($col[1]) FROM speeds WHERE device = ?)
        + (SELECT STD($col[1]) * 3 FROM speeds WHERE device = ?)
    )
  AND $col[1] > (
        (SELECT AVG($col[1]) FROM speeds WHERE device = ?)
        - (SELECT STD($col[1]) * 3 FROM speeds WHERE device = ?)
    );
SQL;
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_GET['device'], $_GET['device'], $_GET['device'], $_GET['device'], $_GET['device']]);
            $col_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['size'] = array_key_exists('size', $data) ? max($data['size'], $col_data['size']) : $col_data['size'];
            $data['datetimestamp'] = array_key_exists('datetimestamp', $data) ? max($data['datetimestamp'], $col_data['datetimestamp']) : $col_data['datetimestamp'];
            $data[$col[0]] = $col_data[$col[0]];
        }
    }
} catch (Exception $e) {
    echo 'Data Export: Get Data: Caught exception: ',  $e->getMessage();
    die('Get failure');
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);

?>
