<?php
// config/db.php

$localDbName = 'team_1_db';

// Remote credentials (your original)
$remoteHost = 'team_1_db';
$remoteUser = 'team_1';
$remotePass = 'team1pass';
$remoteDb   = 'team_1_db';

// Local XAMPP credentials (default)
$localHost = 'localhost';
$localUser = 'root';
$localPass = '';

// Detect environment by host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);

// Pick credentials
if ($isLocal) {
    $dbHost = $localHost;
    $dbUser = $localUser;
    $dbPass = $localPass;
    $dbName = $localDbName;
} else {
    $dbHost = $remoteHost;
    $dbUser = $remoteUser;
    $dbPass = $remotePass;
    $dbName = $remoteDb;
}

// Create mysqli connection
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($mysqli->connect_error) {
    die('DB connection failed');
}

// Important for Japanese text etc.
$mysqli->set_charset('utf8mb4');
