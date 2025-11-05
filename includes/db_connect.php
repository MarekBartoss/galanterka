<?php

$config = require __DIR__ . '/config.php';

// Always use localhost for now
$db = $config['localhost'];

/*
// Uncomment this to use hosting when not on localhost
if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    $db = $config['hosting'];
}
*/

$conn = new mysqli($db['host'], $db['username'], $db['password'], $db['dbname']);

if ($conn->connect_error) {
    die("❌ Chyba připojení: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
