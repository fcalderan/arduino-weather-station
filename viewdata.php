<?php 
/* Create persistent DB connection */
$dbh = new PDO('mysql:host=localhost;dbname=arduino', 'root', ''); 


/* Select last data available */
$stmt = $dbh->prepare("
    SELECT cdate FROM dht22 ORDER BY cdate DESC LIMIT 1
");

if ($stmt->execute()) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC); 
    $lastdate = $row['cdate'];
}


/* Select one row every 20 rows from collection taken on last data available */
$stmt = $dbh->prepare("
    SELECT * FROM ( 
        SELECT @row := @row +1 AS rownum, id, cdate, ctime, temperature, humidity 
        FROM (SELECT @row :=0) r, dht22
    ) ranked 
    WHERE rownum % 20 = 0 AND cdate = '{$lastdate}' 
    ORDER BY ctime DESC LIMIT 30");

$hours = "";
$temp  = "";
$rh    = "";

if ($stmt->execute()) {
   
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        /* strip seconds from time */
        $hours .= "'". substr_replace($row['ctime'], "", -3) . "', ";
        $temp .= "'". $row['temperature'] . "', ";
        $rh .= "'". $row['humidity'] . "', ";
    }

    /* strip last comma for each array */
    $hours = substr_replace($hours, "", -2);
    $temp = substr_replace($temp , "", -2);
    $rh = substr_replace($rh , "", -2);
}


/* Get table cardinality */
$stmt = $dbh->prepare("SELECT COUNT(id) FROM dht22");
$stmt->execute();
$row = $stmt->fetch();
$collection = $row[0];


?>
<!DOCTYPE html>
<!--[if IEMobile 7 ]><html class="no-js iem7" lang="it"><![endif]-->
<!--[if lt IE 9]><html class="no-js lte-ie8" lang="it"><![endif]-->
<!--[if IE 9]><html class="no-js lte-ie9" lang="it"><![endif]-->
<!--[if (gt IE 9)|(gt IEMobile 7)|!(IEMobile)|!(IE)]><!--><html class="no-js" lang="it"><!--<![endif]-->
<head>


    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge"><![endif]-->

    <title>Arduino Weather station</title>

    <style>
        main > * {
            font-family: "Inconsolata", "Courier New";
            margin: 20px;
            color: #424242;
        }

      
        h2 {
            font-size: 1.8em;
            font-weight: normal;
            text-shadow: 0 1px 1px #ccc;
        }

    </style>

 </head>


<body>

    <main role="main">

        <p>
            <small><i>Data collected on DB: <?php echo $collection ?></i></small>
        </p>
        
        <section>
            <h2>Temperature (C)</h2>
            <canvas id="dht22temperature" width="820" height="560"></canvas>
        </section>

        <section>
            <h2>Relative Humidity (RH%)</h2>
            <canvas id="dht22humidity" width="820" height="560"></canvas>
        </section>

    </main>


    <script src="chart/chart.min.js"></script>    
    <script>

    var ctxt = document.getElementById("dht22temperature").getContext("2d"),
        ctxh = document.getElementById("dht22humidity").getContext("2d");


    /* Temperature settings */

    var dataTemp = {
        labels : [<?php echo $hours; ?>].reverse(),
        datasets : [
            {
                fillColor : "rgba(200,200,200,0.5)",
                strokeColor : "rgba(200,200,200,1)",
                pointColor : "rgba(200,200,200,1)",
                pointStrokeColor : "#fff",
                data : [<?php echo $temp; ?>].reverse()
            }
        ]
    }

    var optionsTemp = {
        scaleOverride: true,
        scaleSteps: 16,
        scaleStartValue: 18,
        scaleStepWidth: .5,
        pointDotRadius : 5,
        scaleFontFamily: "Courier",
        scaleFontSize : 13,
        scaleFontColor: "#999"
    };


    /* Humidity settings */

    var dataRH = {
        labels : [<?php echo $hours; ?>].reverse(),
        datasets : [

            {
                fillColor : "rgba(151,187,205,0.5)",
                strokeColor : "rgba(151,187,205,1)",
                pointColor : "rgba(151,187,205,1)",
                pointStrokeColor : "#fff",
                data : [<?php echo $rh; ?>].reverse()
            }
        ]
    };

    var optionsRH = {
        scaleOverride: true,
        scaleSteps: 30,
        scaleStartValue: 25,
        scaleStepWidth: 1,
        pointDotRadius : 5,
        scaleFontFamily: "Courier",
        scaleFontSize : 13,
        scaleFontColor: "#999"
    };


    new Chart(ctxt).Line(dataTemp, optionsTemp);
    new Chart(ctxh).Line(dataRH, optionsRH);

    </script>


</body>
</html>