<?php
require_once("connect.php");
echo "Database connection test: ";
if ($conn && !$conn->connect_error) {
    echo "SUCCESS";
} else {
    echo "FAILED - " . ($conn ? $conn->connect_error : "Connection variable not available");
}
?>