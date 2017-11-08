<!DOCTYPE HTML>
<html>
<head>
    <style>.canvasjs-chart-credit {
            display: none;
        }</style>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.js"></script>
    <script type="text/javascript" src="charts.js"></script>
    <script type="text/javascript">
        window.onload = function () {
            var limit = <?php echo $_GET['h'] ?: '48'; ?>;
            charts(limit<?php echo (isset($_GET['d']) ?", [".$_GET['d']."]": ''); ?>).then(function (res) {
                for (key in res) {
                  var thiselem = document.getElementById(res[key].chart.container);
                  if(!thiselem) {
                    thiselem = document.createElement("div");
                    thiselem.id = res[key].chart.container;
                    thiselem.style.height = "33vH";
                    thiselem.style.width = "100%";
                    document.body.appendChild(thiselem);
                  }
                  res[key] = new CanvasJS.Chart(res[key].chart.container, res[key]);
                  res[key].render();
                }
            }, function (err) {
                console.error(err);
            });
        }
    </script>
</head>
<body>
<a href="?h=12">Last 12hrs</a> <a href="?h=24">Last 24hrs</a> <a href="?h=48">Last 48hrs</a> <a href="?h=168">Last
    week</a> <a href="?h=730">Last month</a> <a href="?h=4380">Last 6 months</a> <a href="?h=8760">Last year</a>
</body>
</html>
