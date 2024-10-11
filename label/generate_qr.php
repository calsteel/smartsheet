<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

try {
    $address = $_GET['address'] ?? 'No address provided';

    $writer = new PngWriter();
    $qrCode = QrCode::create($address);

    // Generate QR code image as a binary string
    $result = $writer->write($qrCode);
    $qrCodeImage = $result->getString();

    // Set the content type to PNG
    header('Content-Type: image/png');
    echo $qrCodeImage;
} catch (Exception $e) {
    error_log('Failed to generate QR code: '.$e->getMessage());
    header('Content-Type: text/plain');
    echo 'Failed to generate QR code. Check server logs for errors.';
}
