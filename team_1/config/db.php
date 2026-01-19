<?php
/**
 * Database configuration
 * 
 * Automatically detects environment (local XAMPP vs Docker) and uses appropriate credentials
 */

$localDbName = 'team_1_db';

// Remote Docker credentials
$remoteHost = 'team_1_db';
$remoteUser = 'team_1';
$remotePass = 'team1pass';
$remoteDb   = 'team_1_db';

// Local XAMPP credentials (default)
$localHost = 'localhost';
$localUser = 'root';
$localPass = '';

// Detect environment by HTTP host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);

// Select credentials based on environment
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

// Create database connection
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($mysqli->connect_error) {
    die('DB connection failed');
}

// Set charset for proper Japanese text handling
$mysqli->set_charset('utf8mb4');
