var dataUp = [];
var dataDl = [];
var dataPing = [];
var currentEndpoint = 2;
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
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent"
});

function addData(data) {
    dataUp = [];
    dataDl = [];
    dataPing = [];
    for (var i = 0; i < data.length; i++) {
        var bitsup = Math.round(data[i].upload * 10) / 10
        var bytesup = Math.round((data[i].upload / 8 ) * 10) / 10
        var bitsdown = Math.round(data[i].download * 10) / 10
        var bytesdown = Math.round((data[i].download / 8 ) * 10) / 10
        dataUp.push({
            x: moment(data[i].datetime).toDate(),
            y: bitsup,
            toolTipContent: bitsup + "Mbps (" + bytesup + " MB/s)"
        });
        dataDl.push({
            x: moment(data[i].datetime).toDate(),
            y: bitsdown,
            toolTipContent: bitsdown + "Mbps (" + bytesdown + " MB/s)"
        });
        dataPing.push({
            x: moment(data[i].datetime).toDate(),
            y: Math.round(data[i].ping * 10) / 10
        });
    }
    chartUp.options.data[0].dataPoints = dataUp;
    chartDl.options.data[0].dataPoints = dataDl;
    chartPing.options.data[0].dataPoints = dataPing;
    chartUp.render();
    chartDl.render();
    chartPing.render();
}

var endpointExt = "?device="+window.atob(device)+"&time=";
var endpoints = [
    endpointExt+"12",
    endpointExt+"24",
    endpointExt+"48",
    endpointExt+"168",
    endpointExt+"all"
];

function getEndpointURL(index) {
    var thisEndpoint = "";
    switch(index) {
        case "c":
            thisEndpoint = endpointExt+"current";
            break;
        case "a":
            thisEndpoint = endpointExt+"average";
            break;
        case "t":
            thisEndpoint = endpointExt+"top";
            break;
        case "b":
            thisEndpoint = endpointExt+"bottom";
            break;
        default:
            thisEndpoint = endpoints[index];
            break;
    }
    return window.btoa(window.atob(endpoint)+thisEndpoint+"&_="+Date.now());
}

function getEndpoint(index) {
    $.getJSON(window.atob(getEndpointURL(index)), addData);
}

function changeEndpoint(index) {
    getEndpoint(index);
    $("#endpoints .btn").removeClass("btn-primary").addClass("btn-info");
    $("#endpoint-"+index).removeClass("btn-info").addClass("btn-primary");
}

function updateCurrent() {
    $.getJSON(window.atob(getEndpointURL("c")), function (data) {
        var date = moment(data.datetime);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $("#last-time").html(date.hours() + ":" + ('0' + date.minutes()).slice(-2) + " " + months[date.month()] + ". " + date.date());
        $("#last-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.upload / 8) * 10) / 10) + " MB/s)");
        $("#last-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.download / 8) * 10) / 10) + " MB/s)");
        $("#last-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("a")), function (data) {
        var hours = Math.floor(data.datetimestamp / 3600);
        var minutes = Math.floor((data.datetimestamp-(hours*3600)) / 60);
        $("#avg-time").html(hours + "<sup>hrs</sup> " + minutes + "<sup>mins</sup>");
        $("#avg-data").html(data.size + " <sup>datapoints</sup>");

        $("#avg-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.upload / 8) * 10) / 10) + " MB/s)");
        $("#avg-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.download / 8) * 10) / 10) + " MB/s)");
        $("#avg-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("t")), function (data) {
        $("#top-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.upload / 8) * 10) / 10) + " MB/s)");
        $("#top-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.download / 8) * 10) / 10) + " MB/s)");
        $("#top-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(window.atob(getEndpointURL("b")), function (data) {
        $("#bottom-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.upload / 8) * 10) / 10) + " MB/s)");
        $("#bottom-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup> (" + (Math.round((data.download / 8) * 10) / 10) + " MB/s)");
        $("#bottom-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
}

updateCurrent(); // Load the most recent data
changeEndpoint(currentEndpoint); // Set button to default pref, and load data

setInterval(function () {
    updateCurrent(); // Update the most recent data
    getEndpoint(currentEndpoint); // Update the graph based on last chosen pref
}, 60 * 1000);