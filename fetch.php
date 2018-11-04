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

    $stmt = $pdo->prepare('SELECT datetime, ping, download, upload FROM speeds WHERE device = ? ORDER BY datetime DESC');
    $stmt->execute([$_GET['device']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['datetimestamp'] = (DateTime::createFromFormat('YmdHis', $row['datetime']))->getTimestamp();
        $row['datetime'] = gmdate('r', $row['datetimestamp']);
        $row['ping'] = floatval($row['ping']);
        $row['upload'] = floatval($row['upload']) / 1000000;
        $row['download'] = floatval($row['download']) / 1000000;

        if ($time == "current") {
            $data = $row;
            break;
        }

        if (in_array($time, ["all", "average", "top", "bottom"])) {
            $data[] = $row;
            continue;
        }

        if ($row['datetimestamp'] >= $time) {
            $data[] = $row;
        } else {
            break;
        }
    }

    if (in_array($time, ["average", "top", "bottom"])) {
        $keydata = [];
        foreach ($data as $item) {
            foreach ($item as $key => $var) {
                if (!array_key_exists($key, $keydata)) {
                    $keydata[$key] = [];
                }
                $keydata[$key][] = $var;
            }
        }
        asort($keydata['ping']); // low to high
        arsort($keydata['upload']); // high to low
        arsort($keydata['download']); // high to low

        $ping1per = round(count($keydata['ping']) * 0.01);
        $up1per = round(count($keydata['upload']) * 0.01);
        $dl1per = round(count($keydata['download']) * 0.01);

        $keydata['ping'] = array_slice($keydata['ping'],
            $ping1per,
            count($keydata['ping']) - $ping1per
        ); // 1% top/bottom excluded

        $keydata['upload'] = array_slice($keydata['upload'],
            $up1per,
            count($keydata['upload']) - $up1per
        ); // 1% top/bottom excluded

        $keydata['download'] = array_slice($keydata['download'],
            $dl1per,
            count($keydata['download']) - $dl1per
        ); // 1% top/bottom excluded
    }

    if ($time == "average") {
        $averages = $keydata;
        unset($averages['datetime']);
        $averages['size'] = count($data);
        $averages['ping'] = array_sum($averages['ping']) / count($averages['ping']);
        $averages['upload'] = array_sum($averages['upload']) / count($averages['upload']);
        $averages['download'] = array_sum($averages['download']) / count($averages['download']);
        $averages['datetimestamp'] = max($averages['datetimestamp']) - min($averages['datetimestamp']); // Seconds
        $data = $averages;
    }
    if ($time == "top" || $time == "bottom") {
        $topdata = $keydata;
        unset($topdata['datetime']);
        $topdata['size'] = count($data);

        $topdata['ping'] = ($time == "top" ? 'min' : 'max')($topdata['ping']);
        $topdata['upload'] = ($time == "top" ? 'max' : 'min')($topdata['upload']);
        $topdata['download'] = ($time == "top" ? 'max' : 'min')($topdata['download']);
        $data = $topdata;
    }

} catch (Exception $e) {
    //echo 'Data Export: Get Data: Caught exception: ',  $e->getMessage();
    die('Get failure');
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);

?>
