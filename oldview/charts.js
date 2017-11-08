function charts(limit, devices) {

    return new Promise(function (resolve, reject) {

        zeroPad = function (nNum, nPad) {
            return ('' + (Math.pow(10, nPad) + nNum)).slice(1);
        };

        ymdHisDate = function (stamp) {
            var tempDate = {};
            tempDate['year'] = stamp.slice(0, 4);
            tempDate['month'] = stamp.slice(4, 6);
            tempDate['day'] = stamp.slice(6, 8);
            tempDate['hour'] = stamp.slice(8, 10);
            tempDate['minute'] = stamp.slice(10, 12);
            tempDate['second'] = stamp.slice(12, 14);
            tempDate = tempDate['year'] + "-" + tempDate['month'] + "-" + tempDate['day'] + " " + tempDate['hour'] + ":" + tempDate['minute'] + ":" + tempDate['second'];
            return new Date(tempDate);
        };

        // limit in x hours ago
        var originalLimit = limit;
        limit = new Date( (new Date())*1 -  (limit * 60 * 60 * 1000));

        var req = new XMLHttpRequest();
        var devicesGET = devices.map(function (x) {
            return "devices[]=" + encodeURIComponent(x);
        });
        devicesGET = devicesGET.join("&");
        req.open('GET', 'https://cdn.unreal-designs.co.uk/cont/pyspeedtest/dataexport.php?' + devicesGET);
        req.onload = function () {
            if (req.status == 200) {

                var data = JSON.parse(req.responseText);

                var speedData = {};

                var convertUnits = {
                    'download': function (data) {
                        return parseFloat(data) / 1000000;
                    },
                    'upload': function (data) {
                        return parseFloat(data) / 1000000;
                    },
                    'ping': function (data) {
                        return parseFloat(data);
                    }
                };

                // device
                for (var key in data) {
                    speedData[key] = {};

                    // entry
                    for (var key1 in data[key]) {
                        // type
                        for (var key2 in data[key][key1]) {

                            if (key2 != "datetime") {

                                // parse YmdHis
                                var today = ymdHisDate(data[key][key1]['datetime']);
                                if (today<limit) {
                                    continue;
                                }
                                var formattedToday = String(today).slice(0, 24);

                                // create type array if it doesn't exist
                                if (!speedData[key].hasOwnProperty(key2)) {
                                    speedData[key][key2] = [];
                                }

                                // construct and append datapoint
                                var tempData = {
                                    x: today,
                                    y: convertUnits[key2](data[key][key1][key2]),
                                    label: key + "<br/>&gt; " + key2 + " speed @ " + formattedToday,
                                    datetime: data[key][key1]['datetime']
                                };
                                speedData[key][key2].push(tempData);

                            }

                        }
                    }
                }

                var avgs = {};
                for (var key in speedData) {
                    avgs[key] = {};
                    for (var key1 in speedData[key]) {
                        var avgx = speedData[key][key1].map(function (obj) {
                            return obj.datetime
                        });
                        avgx.sort();
                        var avgy = speedData[key][key1].map(function (obj) {
                            return obj.y
                        }).reduce(function (a, b) {
                            return a + b
                        }) / speedData[key][key1].length;
                        avgs[key]['date_latest'] = ymdHisDate(avgx[avgx.length-1]);
                        avgs[key]['date_oldest'] = ymdHisDate(avgx[0]);
                        avgs[key]['date_diff_hours'] = avgs[key]['date_latest'].getTime() - avgs[key]['date_oldest'].getTime();
                        avgs[key]['date_diff_hours'] = avgs[key]['date_diff_hours'] / 1000 / 60 / 60;
                        avgs[key][key1] = avgy;
                    }
                }

                console.info("Data Averages: " + JSON.stringify(avgs, null, 4));

                var colors = [
                    "ED5565", "FFCE54", "48CFAD", "5D9CEC", "EC87C0",
                    "FC6E51", "A0D468", "4FC1E9", "AC92EC"
                ];
                var colorIndex = 0;

                var charts = {};
                charts['download'] = /*new CanvasJS.Chart("",*/
                    {
                        chart: {
                            container: "chartContainer_download",
                            creditHref: "",
                            creditText: ""
                        },
                        title: {
                            text: "UD Servers - Download Speeds Last " + originalLimit + "hrs (Mbps)"
                        },
                        data: []
                    }/*)*/;
                colorIndex = 0;
                for (var key in speedData) {
                    charts['download']/*.options*/.data.push({
                        type: "line",
                        legendText: key,
                        color: "#" + colors[colorIndex],
                        lineColor: "#" + colors[colorIndex],
                        dataPoints: speedData[key]['download']
                    });
                    colorIndex++;
                    if (colorIndex >= colors.length) {
                        colorIndex = 0;
                    }
                }

                charts['upload'] = /*new CanvasJS.Chart("",*/
                    {
                        chart: {
                            container: "chartContainer_upload",
                            creditHref: "",
                            creditText: ""
                        },
                        title: {
                            text: "UD Servers - Upload Speeds Last " + originalLimit + "hrs (Mbps)"
                        },
                        data: []
                    }/*)*/;
                colorIndex = 0;
                for (var key in speedData) {
                    charts['upload']/*.options*/.data.push({
                        type: "line",
                        legendText: key,
                        color: "#" + colors[colorIndex],
                        lineColor: "#" + colors[colorIndex],
                        dataPoints: speedData[key]['upload']
                    });
                    colorIndex++;
                    if (colorIndex >= colors.length) {
                        colorIndex = 0;
                    }
                }

                charts['ping'] = /*new CanvasJS.Chart("",*/
                    {
                        chart: {
                            container: "chartContainer_ping",
                            creditHref: "",
                            creditText: ""
                        },
                        title: {
                            text: "UD Servers - Ping Speeds Last " + originalLimit + "hrs (ms)"
                        },
                        data: []
                    }/*)*/;
                colorIndex = 0;
                for (var key in speedData) {
                    charts['ping']/*.options*/.data.push({
                        type: "line",
                        legendText: key,
                        color: "#" + colors[colorIndex],
                        lineColor: "#" + colors[colorIndex],
                        dataPoints: speedData[key]['ping']
                    });
                    colorIndex++;
                    if (colorIndex >= colors.length) {
                        colorIndex = 0;
                    }
                }

                resolve(charts);

                /*for (key in charts) {
                  thiselem = base.ownerDocument.getElementById(charts[key].options.chart.container);
                  if(!thiselem) {
                    thiselem = base.ownerDocument.createElement("div");
                    thiselem.id = charts[key].options.chart.container;
                    thiselem.style.height = "33vH";
                    thiselem.style.width = "100%";
                    base.appendChild(thiselem);
                  }
                  charts[key].render();
                }*/

            } else {
                reject(Error(req.statusText));
            }
        };

        req.onerror = function () {
            reject(Error("Network Error"));
        };

        req.send();

    });
}
