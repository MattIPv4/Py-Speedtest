var dataUp, dataUpAvg, dataDl, dataDlAvg, dataPing, dataPingAvg;
var currentEndpoint = 3;
var endpoint = "<?php echo base64_encode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . "/fetch.php"); ?>";
var device = $("body").attr("data-device");

var chartUp = new CanvasJS.Chart("upGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: " Mbps",
        gridColor: "#CCD1D9",
        gridThickness: 1
    },
    data: [{
        type: "line",
        dataPoints: dataUp
    }, {
        type: "line",
        dataPoints: dataUpAvg
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent"
});
var chartDl = new CanvasJS.Chart("dlGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: " Mbps",
        gridColor: "#CCD1D9",
        gridThickness: 1
    },
    data: [{
        type: "line",
        dataPoints: dataDl
    }, {
        type: "line",
        dataPoints: dataDlAvg
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent"
});
var chartPing = new CanvasJS.Chart("pingGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: "ms",
        gridColor: "#CCD1D9",
        gridThickness: 1
    },
    data: [{
        type: "line",
        dataPoints: dataPing
    }, {
        type: "line",
        dataPoints: dataPingAvg
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent"
});

function round(data) {
    return Math.round(data * 10) / 10;
}
function bits(data) {
    return round(data);
}
function bytes(data) {
    return round(data / 8);
}
function speedTooltip(data) {
    return bits(data) + "Mbps (" + bytes(data) + " MB/s)";
}
function date(data) {
    return moment(data).toDate();
}
function average(data, type, end) {
    return ((data.slice(0, end + 1)).map(function (a) {
        return a[type];
    }).reduce(function (a, b) {
        return a + b;
    }, 0)) / (end + 1);
}

function addData(data) {
    dataUp = [];
    dataUpAvg = [];
    dataDl = [];
    dataDlAvg = [];
    dataPing = [];
    dataPingAvg = [];
    var avgTemp;
    data.reverse(); // fetch gives datatime desc, we need asc for avg calcs
    for (var i = 0; i < data.length; i++) {
        dataUp.push({
            x: date(data[i].datetime),
            y: bits(data[i].upload),
            toolTipContent: speedTooltip(data[i].upload)
        });
        avgTemp = average(data, "upload", i);
        dataUpAvg.push({
            x: date(data[i].datetime),
            y: bits(avgTemp),
            toolTipContent: speedTooltip(avgTemp)
        });

        dataDl.push({
            x: date(data[i].datetime),
            y: bits(data[i].download),
            toolTipContent: speedTooltip(data[i].download)
        });
        avgTemp = average(data, "download", i);
        dataDlAvg.push({
            x: date(data[i].datetime),
            y: bits(avgTemp),
            toolTipContent: speedTooltip(avgTemp)
        });

        dataPing.push({
            x: date(data[i].datetime),
            y: round(data[i].ping)
        });
        avgTemp = average(data, "ping", i);
        dataPingAvg.push({
            x: date(data[i].datetime),
            y: round(avgTemp)
        });
    }
    chartUp.options.data[0].dataPoints = dataUp;
    chartUp.options.data[1].dataPoints = dataUpAvg;
    chartDl.options.data[0].dataPoints = dataDl;
    chartDl.options.data[1].dataPoints = dataDlAvg;
    chartPing.options.data[0].dataPoints = dataPing;
    chartPing.options.data[1].dataPoints = dataPingAvg;
    chartUp.render();
    chartDl.render();
    chartPing.render();
    $(".graph").removeClass("loading");
}

var endpointExt = "?device=" + window.atob(device) + "&time=";
var endpoints = [
    endpointExt + "12",
    endpointExt + "24",
    endpointExt + "48",
    endpointExt + "168", // 24 * 7
    endpointExt + "720", // 24 * 30
    endpointExt + "4380", // 24 * (365 / 2)
    endpointExt + "8760" // 24 * 365
];

function getEndpointURL(index) {
    var thisEndpoint = "";
    switch (index) {
        case "c":
            thisEndpoint = endpointExt + "current";
            break;
        case "a":
            thisEndpoint = endpointExt + "average";
            break;
        case "t":
            thisEndpoint = endpointExt + "top";
            break;
        case "b":
            thisEndpoint = endpointExt + "bottom";
            break;
        default:
            thisEndpoint = endpoints[index];
            break;
    }
    return window.btoa(window.atob(endpoint) + thisEndpoint + "&_=" + Date.now());
}

function getEndpoint(index) {
    $.getJSON(window.atob(getEndpointURL(index)), addData);
}

function changeEndpoint(index) {
    $(".graph").addClass("loading");
    currentEndpoint = index;
    getEndpoint(index);
    $("#endpoints .btn").removeClass("btn-primary").addClass("btn-info");
    $("#endpoint-" + index).removeClass("btn-info").addClass("btn-primary");
}

function updateCurrent() {
    $.getJSON(window.atob(getEndpointURL("c")), function (data) {
        var date = moment(data.datetime);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $("#last-time").html(date.hours() + ":" + ('0' + date.minutes()).slice(-2) + " " + months[date.month()] + ". " + date.date());
        $("#last-up").html(bits(data.upload) + " <sup>Mbps</sup><br/>(" + bytes(data.upload) + " <sup>MB/s</sup>)");
        $("#last-dl").html(bits(data.download) + " <sup>Mbps</sup><br/>(" + bytes(data.download) + " <sup>MB/s</sup>)");
        $("#last-ping").html(round(data.ping) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("a")), function (data) {
        var hours = Math.floor(data.datetimestamp / 3600);
        var minutes = Math.floor((data.datetimestamp - (hours * 3600)) / 60);
        $("#avg-time").html(hours + "<sup>hrs</sup> " + minutes + "<sup>mins</sup>");
        $("#avg-data").html(data.size + " <sup>datapoints</sup>");

        $("#avg-up").html(bits(data.upload) + " <sup>Mbps</sup><br/>(" + bytes(data.upload) + " <sup>MB/s</sup>)");
        $("#avg-dl").html(bits(data.download) + " <sup>Mbps</sup><br/>(" + bytes(data.download) + " <sup>MB/s</sup>)");
        $("#avg-ping").html(round(data.ping) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("t")), function (data) {
        $("#top-up").html(bits(data.upload) + " <sup>Mbps</sup><br/>(" + bytes(data.upload) + " <sup>MB/s</sup>)");
        $("#top-dl").html(bits(data.download) + " <sup>Mbps</sup><br/>(" + bytes(data.download) + " <sup>MB/s</sup>)");
        $("#top-ping").html(round(data.ping) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("b")), function (data) {
        $("#bottom-up").html(bits(data.upload) + " <sup>Mbps</sup><br/>(" + bytes(data.upload) + " <sup>MB/s</sup>)");
        $("#bottom-dl").html(bits(data.download) + " <sup>Mbps</sup><br/>(" + bytes(data.download) + " <sup>MB/s</sup>)");
        $("#bottom-ping").html(round(data.ping) + "<sup>ms</sup>");
    });
}

updateCurrent(); // Load the most recent data
changeEndpoint(currentEndpoint); // Set button to default pref, and load data

setInterval(function () {
    updateCurrent(); // Update the most recent data
    getEndpoint(currentEndpoint); // Update the graph based on last chosen pref
}, 60 * 1000);