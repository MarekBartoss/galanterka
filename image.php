<?php
require_once __DIR__ . '/includes/db_connect.php';

$id = (int)$_GET['id'];
$res = $conn->query("SELECT mime_type, data FROM pictures WHERE id = $id LIMIT 1");

if ($row = $res->fetch_assoc()) {
    header("Content-Type: " . $row['mime_type']);
    echo $row['data'];
} else {
    http_response_code(404);
    echo "Obrázek nenalezen.";
}
