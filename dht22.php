<?php
// Insert your keys/tokens
$consumerKey 	= '<your-key>';
$consumerSecret = '<your-key>';
$oAuthToken 	= '<your-key>';
$oAuthSecret 	= '<your-key>';


// Full path to twitteroauth.php (change oauth to your own path)
require_once($_SERVER['DOCUMENT_ROOT'].'/arduino-weather-station/twitterOAuth/twitteroauth.php');

// create new instance
$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthSecret);


function roundNum($n) { return round(10 * $n)/10; }



/* Create persistent DB connection */
$dbh = new PDO('mysql:host=localhost;dbname=arduino', 'root', '', array(
    PDO::ATTR_PERSISTENT => true
)); 

/* Insert values read from sensor DHT22 */
$stmt = $dbh->prepare("INSERT INTO dht22 (cdate, ctime, temperature, humidity) VALUES (:cd, :ct, :t, :h)");
$stmt->bindParam(':cd', $cd);
$stmt->bindParam(':ct', $ct);
$stmt->bindParam(':t', $t);
$stmt->bindParam(':h', $h);

$cd = date("Y-m-d");
$ct = date("H:i:s");
$t = $_POST['t'];
$h = $_POST['h'];

$stmt->execute();


/* Count rows inserted */
$collections = 0;
$stmt = $dbh->prepare("SELECT COUNT(id) FROM dht22");
if ($stmt->execute()) {
    $row = $stmt->fetch();
    $collections = (int)$row[0];
}

echo "{$collections} collections retrieved on database";


/* we send a tweet every 5000 data collected. Adjust this value at your need */
$collections_before_tweet = 5000;


if (($collections % $collections_before_tweet) === 0) {


    $dt_tweet = date("M j, Y");
    $hr_tweet = date("H:i");

    $nowquery  = "SELECT temperature, humidity ";
    $nowquery .= "FROM dht22 ORDER BY cdate DESC, ctime DESC ";
    $nowquery .= "LIMIT 1";
    $stmt = $dbh->prepare($nowquery);

    $nowt = 0;
    $nowh = 0;

    if ($stmt->execute()) {
        $row = $stmt->fetch();
        $nowt = roundNum(floatval($row[0]));
        $nowh = roundNum(floatval($row[1]));
    }



    $nowt = number_format($nowt, 1, '.', '');
    $nowh = number_format($nowh, 1, '.', '');

    // Your Message
    $message  = "On {$dt_tweet} at {$hr_tweet} ";
    $message .= "temp. is {$nowt}C ";
    $message .= "and rel. humidity is {$nowh}% ";

    echo "\n$message";

    // Send tweet 
    $tweet->post('statuses/update', array('status' => "$message"));

}