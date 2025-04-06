<?php
session_start();
require_once 'api_helper.php';

$data = json_decode(file_get_contents('php://input'), true);
$farmerId = $data['id'] ?? null;

$method = $farmerId ? 'PUT' : 'POST';
$url = $farmerId ? 'farmers/' . $farmerId : 'farmers';

$response = callApi($method, $url, $data);

header('Content-Type: application/json');
echo json_encode($response);
?>
