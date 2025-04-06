<?php
// This script fetches details of a single farmer

// Get the farmer ID
$farmerId = $_GET['id'] ?? null;

if (!$farmerId) {
    echo json_encode([
        'success' => false,
        'message' => 'Farmer ID is required'
    ]);
    exit;
}

// Build the API URL
$apiUrl = "http://172.20.10.3:8000/api/farmers/$farmerId";

// Initialize cURL session
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

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