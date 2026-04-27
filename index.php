<?php

$host = '10.1.1.100';
$username = 'bryce';
$password = '1234';
$database = 'final';
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);
if ($conn ->connect_error)
       die('Could not connect: ' . $conn->connect_error);
echo 'sucess';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Market</title>
<html>
