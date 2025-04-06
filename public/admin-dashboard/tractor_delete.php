<?php
// This script handles tractor deletion

// Get the tractor ID
$tractorId = $_GET['id'] ?? null;

if (!$tractorId) {
    echo json_encode([
        'success' => false,
        'message' => 'Tractor ID is required'
    ]);
    exit;
}

// Build the API URL
$apiUrl = "http://172.20.10.3:8000/api/admin/tractors/$tractorId";

// Initialize cURL session
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

// Get token from request and forward it
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $headers['Authorization']
    ]);
}

// Execute cURL session
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'message' => 'cURL Error: ' . curl_error($ch)
    ]);
    exit;
}

// Close cURL session
curl_close($ch);

// Pass the API response directly back to the client
header('Content-Type: application/json');
echo $response;