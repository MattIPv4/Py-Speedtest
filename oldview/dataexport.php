<?php
  require_once('../basicUtils.php');
  extract(setup_cdnRes($_SERVER));

require_once('../config.php');

    $safe = true;

    $pdo = new PDO(
    'mysql:host=localhost;dbname=server_speeds',
    PDO_USER,
    PDO_PASS);

    try {

        $check = (isset($_GET['devices'])?' WHERE device IN ('.join(", ", array_map(function(){return "?";}, $_GET['devices'])).')':'');
        $args = (isset($_GET['devices'])?$_GET['devices']:[]);
        $stmt = $pdo->prepare('SELECT DISTINCT device FROM speeds'.$check);
        $stmt->execute($args);
        $devices = $stmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (Exception $e) {
        echo 'Data Export: Get Unique: Caught exception: ',  $e->getMessage();
        $safe = false;
    }

    try {

        $data = [];

        foreach($devices as $device) {

            $stmt = $pdo->prepare('SELECT datetime, ping, download, upload FROM speeds WHERE device = ? ORDER BY datetime DESC');
            $stmt->execute([$device]);
            $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data[$device] = $stmt;

        }

    } catch (Exception $e) {
        echo 'Data Export: Get Data: Caught exception: ',  $e->getMessage();
        $safe = false;
    }

    if($safe) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

?>
