<?php

require_once('../basicUtils.php');
extract(setup_cdnRes($_SERVER));

require_once('config.php');
    
if(isset($_POST['device']) && isset($_POST['datetime']) && isset($_POST['ping']) && isset($_POST['download']) && isset($_POST['upload'])) {

    if(!in_array($_POST['device'], AUTHED_DEVICES)) {
        header('HTTP/1.0 401 Unauthorized');
        die('Data Upload: Device name not authorized.');
    }

    if(in_array(0, [round($_POST['download']/1000000), round($_POST['upload']/1000000)])) {
        header('HTTP/1.0 400 Bad Request');
        die('Data Upload: Zero value included, upload ignored.');
    }

    $pdo = new PDO(
        'mysql:host='.PDO_HOST.';dbname=server_speeds',
        PDO_USER,
        PDO_PASS);

    $data = [];
    foreach ($_POST as $key => $value) {$data[":".$key] = $value;}

    try {
        $stmt = $pdo->prepare("INSERT INTO speeds (device, datetime, ping, download, upload) VALUES (:device, :datetime, :ping, :download, :upload)");
        $stmt->execute($data);
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        die('Data Upload: Caught exception.');
    }

    header('HTTP/1.0 201 Created');
    die('Data Upload: Success');

} else {
    header('HTTP/1.0 400 Bad Request');
    die('Data Upload: Missing data.');
}

?>