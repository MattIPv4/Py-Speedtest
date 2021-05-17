<?php
require_once('config.php');

$request = trim(trim(explode("?", $_SERVER['REQUEST_URI'])[0], "/"), " ");
if (strtolower($request) == "speedtester" || strtolower($request) == "speedtester.py") {
    require_once "speedtester.py.php";
    die();
}
if (strtolower($request) == "authed") {
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?: ($_SERVER['HTTP_X_FORWARDE‌​D_FOR'] ?: $_SERVER['REMOTE_ADDR']);
    if (in_array($ip, [$_SERVER['SERVER_ADDR'], '::1', '127.0.0.1'])) {
        die(implode(", ", array_map(function ($x) {
            return "'" . $x . "'";
        }, AUTHED_DEVICES)));
    } else {
        header('HTTP/1.0 401 Unauthorized');
        die('This route is localhost only. Requested from \'' . $ip . '\'.');
    }
}
if (empty($request)) {
    header('HTTP/1.0 400 Bad Request');
    die('Please specify a device in the URI to load speed test data.');
}
if (!in_array(trim($request), AUTHED_DEVICES)) {
    header("HTTP/1.0 403 Forbidden");
    die("Device '" . $request . "' not known.");
}
$device = $request;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title><?php echo $device; ?> Speed Tests</title>
    <meta name="theme-color" content="#164d87"/>
    <meta name="title" content="<?php echo $device; ?> Speed Tests"/>
    <meta name="description"
          content="View the latest, average and historical speed test data for <?php echo $device; ?>."/>
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:site"
          content="<?php echo((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"]); ?>"/>
    <meta name="twitter:url"
          content="<?php echo((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"]); ?>"/>
    <meta name="twitter:creator" content="@Unreal_Designs"/>
    <meta name="twitter:title" content="<?php echo $device; ?> Speed Tests"/>
    <meta name="twitter:description"
          content="View the latest, average and historical speed test data for <?php echo $device; ?>."/>

    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css'/>
    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/css/bootstrap.min.css'/>
    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'/>
    <link rel='stylesheet prefetch'
          href='https://fonts.googleapis.com/css?family=Lato'/>
    <link rel="stylesheet" href="/css/style.css"/>
</head>

<body data-device="<?php echo base64_encode($device); ?>">
<div class="container">
    <div class="row">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Speed Test Logging <i class="fa fa-tachometer" aria-hidden="true"></i></h4>
                    <p class="card-text"><?php echo $device; ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Last Speed Test <i class="fa fa-clock-o" aria-hidden="true"></i></h4>
                    <p class="card-text" id="last-time"></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title" style="font-size:1.15rem;">Data provided by:</h4>
                    <p class="card-text">
                        <a href="https://unreal-designs.co.uk/" target="_blank">
                            <img src="https://unreal-designs.co.uk/assets/images/logo-text_scaled.png"
                                 style="height:4rem;"/>
                            <sup><i class="fa fa-external-link" aria-hidden="true"></i></sup>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body warn text-center">
                    <h4 class="card-title">Upload <i class="fa fa-upload" aria-hidden="true"></i></h4>
                    <p class="card-text" id="last-up"></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body success text-center">
                    <h4 class="card-title">Download <i class="fa fa-download" aria-hidden="true"></i></h4>
                    <p class="card-text" id="last-dl"></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body info text-center">
                    <h4 class="card-title">Ping <i class="fa fa-heartbeat" aria-hidden="true"></i></h4>
                    <p class="card-text" id="last-ping"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <p>
                This is a private speed testing and logging service.
                It uses the 'speedtest-cli' Python library for gathering speed data.
                You can view most of the source code on <a href="https://github.com/MattIPv4/Py-Speedtest"
                                                           target="_blank">GitHub</a>.
                Zero values are excluded from logging to reduce false data logging.
            </p>
            <hr/>
        </div>
    </div>

    <div class="row justify-content-md-center">
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-body text-center" id="endpoints">
                    <h4 class="card-title">Graph Date/Time Range <i class="fa fa-calendar" aria-hidden="true"></i>
                    </h4>
                    <a href="javascript:changeEndpoint(0);" id="endpoint-0" class="btn btn-info">12hrs</a>
                    <a href="javascript:changeEndpoint(1);" id="endpoint-1" class="btn btn-info">Day</a>
                    <a href="javascript:changeEndpoint(2);" id="endpoint-2" class="btn btn-info">Two Days</a>
                    <a href="javascript:changeEndpoint(3);" id="endpoint-3" class="btn btn-info">Week</a>
                    <a href="javascript:changeEndpoint(4);" id="endpoint-4" class="btn btn-info">Month (30 days)</a>
                    <hr/>
                    <i>These will cause lag:</i><br/>
                    <a href="javascript:changeEndpoint(5);" id="endpoint-5" class="btn btn-info">Half Year</a>
                    <a href="javascript:changeEndpoint(6);" id="endpoint-6" class="btn btn-info">Year</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Show/Hide Graphs <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    </h4>
                    <a href="javascript:$('#graphsDiv').fadeToggle();$('#graphsToggle').toggleClass('btn-primary').toggleClass('btn-info');"
                       id="graphsToggle" class="btn btn-info">Toggle</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="graphsDiv">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Upload History <i class="fa fa-upload" aria-hidden="true"></i>
                    </h4>
                    <div id="upGraph" class="graph"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Download History <i class="fa fa-download" aria-hidden="true"></i></h4>
                    <div id="dlGraph" class="graph"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Ping History <i class="fa fa-heartbeat" aria-hidden="true"></i></h4>
                    <div id="pingGraph" class="graph"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <hr/>
        </div>
    </div>

    <div class="row justify-content-md-center">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Total Time Logged <i class="fa fa-clock-o" aria-hidden="true"></i></h4>
                    <p class="card-text" id="avg-time"></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Total Data Logged <i class="fa fa-clock-o" aria-hidden="true"></i></h4>
                    <p class="card-text" id="avg-data"></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="card-title">Show/Hide Info <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    </h4>
                    <a href="javascript:$('#infoDiv').fadeToggle();$('#infoToggle').toggleClass('btn-primary').toggleClass('btn-info');"
                       id="infoToggle" class="btn btn-info">Toggle</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="infoDiv">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body info text-center">
                    <h4 class="card-title">Average Upload <i class="fa fa-upload" aria-hidden="true"></i></h4>
                    <p class="card-text" id="avg-up"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body info text-center">
                    <h4 class="card-title">Average Download <i class="fa fa-download" aria-hidden="true"></i></h4>
                    <p class="card-text" id="avg-dl"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body info text-center">
                    <h4 class="card-title">Average Ping <i class="fa fa-heartbeat" aria-hidden="true"></i></h4>
                    <p class="card-text" id="avg-ping"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body success text-center">
                    <h4 class="card-title">Top Upload <i class="fa fa-upload" aria-hidden="true"></i></h4>
                    <p class="card-text" id="top-up"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body success text-center">
                    <h4 class="card-title">Top Download <i class="fa fa-download" aria-hidden="true"></i></h4>
                    <p class="card-text" id="top-dl"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body success text-center">
                    <h4 class="card-title">Top Ping <i class="fa fa-heartbeat" aria-hidden="true"></i></h4>
                    <p class="card-text" id="top-ping"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body warn text-center">
                    <h4 class="card-title">Bottom Upload <i class="fa fa-upload" aria-hidden="true"></i></h4>
                    <p class="card-text" id="bottom-up"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body warn text-center">
                    <h4 class="card-title">Bottom Download <i class="fa fa-download" aria-hidden="true"></i></h4>
                    <p class="card-text" id="bottom-dl"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body warn text-center">
                    <h4 class="card-title">Bottom Ping <i class="fa fa-heartbeat" aria-hidden="true"></i></h4>
                    <p class="card-text" id="bottom-ping"></p>
                    <p class="card-text small">(Data outside 3SD excluded)</p>
                </div>
            </div>
        </div>
    </div>

    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.0/moment.js'></script>
    <script src="/js/index.js.php"></script>

</body>
</html>