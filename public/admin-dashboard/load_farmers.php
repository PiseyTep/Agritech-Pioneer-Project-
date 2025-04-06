<?php
// This script handles both fetching and updating farmer data

// Determine the request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET request - Fetch farmers
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';

    // Build the API URL
    $apiUrl = "http://172.20.10.3:8000/api/farmers-list?page=$page";
    if (!empty($search)) {
        $apiUrl .= "&search=" . urlencode($search);
    }

    // Initialize cURL session
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
} elseif ($method === 'POST' || $method === 'PUT') {
    // Handle POST/PUT request - Update farmer
    
    // Get the farmer ID and data
    $farmerId = $_GET['id'] ?? null;
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);
    
    if (!$farmerId) {
        echo json_encode([
            'success' => false,
            'message' => 'Farmer ID is required'
        ]);
        exit;
    }
    
    // Build the API URL for updating
    $apiUrl = "http://172.20.10.3:8000/api/farmers/$farmerId";
    
    // Initialize cURL session
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($inputData)
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