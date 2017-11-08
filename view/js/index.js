var dataUp = [];
var dataDl = [];
var dataPing = [];
var currentEndpoint = 2;
var endpoint = "https://cdn.unreal-designs.co.uk/cont/pyspeedtest/view/fetch.php";
var device = $("body").attr("data-device");

var chartUp = new CanvasJS.Chart("upGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: " Mbps",
        gridColor: "#CCD1D9",
        gridThickness: 1,
    },
    data: [{
        type: "spline",
        dataPoints: dataUp
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent",
});
var chartDl = new CanvasJS.Chart("dlGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: " Mbps",
        gridColor: "#CCD1D9",
        gridThickness: 1,
    },
    data: [{
        type: "spline",
        dataPoints: dataDl
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent",
});
var chartPing = new CanvasJS.Chart("pingGraph", {
    theme: "light2",
    axisY: {
        includeZero: false,
        suffix: "ms",
        gridColor: "#CCD1D9",
        gridThickness: 1,
    },
    data: [{
        type: "spline",
        dataPoints: dataPing
    }],
    creditText: "",
    creditHref: "",
    backgroundColor: "transparent",
});

function addData(data) {
    dataUp = [];
    dataDl = [];
    dataPing = [];
    for (var i = 0; i < data.length; i++) {
        dataUp.push({
            x: moment(data[i].datetime).toDate(),
            y: Math.round(data[i].upload * 10) / 10
        });
        dataDl.push({
            x: moment(data[i].datetime).toDate(),
            y: Math.round(data[i].download * 10) / 10
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

endpoint = endpoint+"?device="+device;
var endpoints = [
    endpoint+"&time=12",
    endpoint+"&time=24",
    endpoint+"&time=48",
    endpoint+"&time=168",
    endpoint+"&time=all",
];

function getEndpoint(index) {
    $.getJSON(endpoints[index]+"&_="+Date.now(), addData);
}

function changeEndpoint(index) {
    currentEndpoint = index;
    getEndpoint(currentEndpoint);
    $("#endpoints .btn").removeClass("btn-primary").addClass("btn-info");
    $("#endpoint-"+currentEndpoint).removeClass("btn-info").addClass("btn-primary");
}

function updateCurrent() {
    $.getJSON(endpoint+"&time=current&_="+Date.now(), function (data) {
        var date = moment(data.datetime);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $("#last-time").html(date.hours() + ":" + ('0' + date.minutes()).slice(-2) + " " + months[date.month()] + ". " + date.date());
        $("#last-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup>");
        $("#last-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup>");
        $("#last-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(endpoint+"&time=average&_="+Date.now(), function (data) {
        var hours = Math.floor(data.datetimestamp / 3600);
        var minutes = Math.floor((data.datetimestamp-(hours*3600)) / 60);
        $("#avg-time").html(hours + "<sup>hrs</sup> " + minutes + "<sup>mins</sup>");
        $("#avg-data").html(data.size + " <sup>datapoints</sup>");

        $("#avg-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup>");
        $("#avg-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup>");
        $("#avg-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(endpoint+"&time=top&_="+Date.now(), function (data) {
        $("#top-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup>");
        $("#top-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup>");
        $("#top-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
    $.getJSON(endpoint+"&time=bottom&_="+Date.now(), function (data) {
        $("#bottom-up").html((Math.round(data.upload * 10) / 10) + " <sup>Mbps</sup>");
        $("#bottom-dl").html((Math.round(data.download * 10) / 10) + " <sup>Mbps</sup>");
        $("#bottom-ping").html((Math.round(data.ping * 10) / 10) + "<sup>ms</sup>");
    });
}

updateCurrent(); // Load the most recent data
changeEndpoint(currentEndpoint); // Set button to default pref, and load data

setInterval(function () {
    updateCurrent(); // Update the most recent data
    getEndpoint(currentEndpoint); // Update the graph based on last chosen pref
}, 60 * 1000);