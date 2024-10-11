<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the images directory
$images_dir = __DIR__ . '/images/';

if (!is_dir($images_dir)) {
    mkdir($images_dir, 0777, true);
}

$response = ['success' => false, 'uploaded' => []];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['photos'])) {
    foreach ($_FILES['photos']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['photos']['name'][$index];
            $target_file = $images_dir . basename($file_name);

            if (move_uploaded_file($tmp_name, $target_file)) {
                $response['uploaded'][] = $file_name;
            }
        }
    }
    $response['success'] = !empty($response['uploaded']);
}

header('Content-Type: application/json');
echo json_encode($response);
