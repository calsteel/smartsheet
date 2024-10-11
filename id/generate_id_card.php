<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'phpqrcode/qrlib.php'; // Include the QR code library

session_start();

// Define the temporary directory path for generated ID cards
$temp_dir = __DIR__ . '/temp_cards/';

// Ensure the directory exists, or create it if it doesn't
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Delete all files in the temp directory to start fresh
$files = glob($temp_dir . '*'); // Get all files in the temporary directory
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file); // Delete each file
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['name']) && isset($_POST['id_number']) && isset($_POST['scanco_id']) && isset($_FILES['photo'])) {
        $names = $_POST['name'];
        $id_numbers = $_POST['id_number'];
        $scanco_ids = $_POST['scanco_id'];
        $photos = $_FILES['photo'];
        $generated_files = [];

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $id_number = $id_numbers[$i];
            $scanco_id = $scanco_ids[$i];

            // If photo is provided and uploaded successfully
            $photo_path = (!empty($photos['tmp_name'][$i]) && $photos['error'][$i] == UPLOAD_ERR_OK) 
                            ? $photos['tmp_name'][$i] 
                            : false;

            $generated_file = generateIDCard($name, $id_number, $scanco_id, $photo_path, $i, $temp_dir);
            if ($generated_file) {
                $generated_files[] = $generated_file;
            }
        }

        if (count($generated_files) > 0) {
            $_SESSION['files'] = $generated_files;
            displayImages($generated_files);
        } else {
            echo "Error: No ID cards generated.";
        }
    } else {
        echo "Error: Form data not set properly.";
    }
} else {
    echo "Error: No POST data received.";
}

function generateIDCard($name, $id_number, $scanco_id, $photo_path, $index, $temp_dir) {
    $template_path = '/var/www/html/id/temp.png'; // Absolute path to your template image
    $font = '/var/www/html/id/arial.ttf'; // Absolute path to your font file
    $logo_path = '/var/www/html/id/logo.png'; // Absolute path to your logo image (now a PNG)

    // Load the template image
    $template = @imagecreatefrompng($template_path);
    if (!$template) {
        echo "Error: Unable to load template image from $template_path.";
        return false;
    }

    // Get the dimensions of the template image
    $width = imagesx($template);
    $height = imagesy($template);

    // Create a true color image
    $image = imagecreatetruecolor($width, $height);

    // Copy the template onto the new image
    imagecopy($image, $template, 0, 0, 0, 0, $width, $height);

    // If a photo is provided, place it on the card
    if ($photo_path) {
        // Determine the image type and create from appropriate function
        $image_info = getimagesize($photo_path);
        if ($image_info) {
            switch ($image_info['mime']) {
                case 'image/jpeg':
                    $photo = @imagecreatefromjpeg($photo_path);
                    break;
                case 'image/png':
                    $photo = @imagecreatefrompng($photo_path);
                    break;
                case 'image/webp':
                    $photo = @imagecreatefromwebp($photo_path);
                    break;
                default:
                    echo "Error: Unsupported image type for photo.";
                    return false;
            }

            // Resize the photo
            $photo_resized = imagescale($photo, 240, 320);

            // Place the photo on the card
            imagecopy($image, $photo_resized, 50, 220, 0, 0, imagesx($photo_resized), imagesy($photo_resized));

            // Clean up
            imagedestroy($photo);
            imagedestroy($photo_resized);
        } else {
            echo "Error: Unable to determine image type.";
            return false;
        }
    }

    // Load the logo image (now PNG)
    $logo = @imagecreatefrompng($logo_path);
    if (!$logo) {
        echo "Error: Unable to load logo image from $logo_path.";
        return false;
    }

    // Resize the logo to make it larger
    $desired_logo_width = 506; // Desired width of the logo
    $desired_logo_height = 145; // Desired height of the logo
    $logo_resized = imagescale($logo, $desired_logo_width, $desired_logo_height);

    // Place the resized logo on the card
    $logo_x = 50; // X-coordinate from the left edge of the ID card
    $logo_y = 10; // Y-coordinate from the top edge of the ID card
    imagecopy($image, $logo_resized, $logo_x, $logo_y, 0, 0, imagesx($logo_resized), imagesy($logo_resized));

    // Clean up the logo
    imagedestroy($logo);
    imagedestroy($logo_resized);

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

   

    // Generate QR code for Employee ID
    $qr_code_path = $temp_dir . "qrcode_$index.png";
    QRcode::png($id_number, $qr_code_path, QR_ECLEVEL_L, 10);
    $qr_code = imagecreatefrompng($qr_code_path);
    if ($qr_code) {
        imagecopy($image, $qr_code, 325, 550, 0, 0, imagesx($qr_code), imagesy($qr_code));
        imagedestroy($qr_code);
        unlink($qr_code_path); // Remove the temporary QR code image
    } else {
        echo "Error: Unable to generate QR code.";
    }

    // Generate QR code for Scanco ID
    $qr_code_scanco_path = $temp_dir . "qrcode_scanco_$index.png";
    QRcode::png($scanco_id, $qr_code_scanco_path, QR_ECLEVEL_L, 10);
    $qr_code_scanco = imagecreatefrompng($qr_code_scanco_path);
    if ($qr_code_scanco) {
        imagecopy($image, $qr_code_scanco, 325, 220, 0, 0, imagesx($qr_code_scanco), imagesy($qr_code_scanco));
        imagedestroy($qr_code_scanco);
        unlink($qr_code_scanco_path); // Remove the temporary QR code image
    } else {
        echo "Error: Unable to generate QR code for Scanco ID.";
    }

	
	 // Add name, ID number, and Scanco ID
    imagettftext($image, 24, 0, 70, 190, $black, $font, "Name: $name");
    imagettftext($image, 24, 0, 420, 250, $black, $font, "User ID");
    imagettftext($image, 24, 0, 440, 500, $black, $font, "$scanco_id");

    imagettftext($image, 24, 0, 380, 580, $black, $font, "Employee ID");

    imagettftext($image, 24, 0, 380, 830, $black, $font, "$id_number");
	
    // Save the image to a file in the temporary directory
    $output_path = $temp_dir . "id_card_$index.png";
    if (!imagepng($image, $output_path)) {
        echo "Error: Failed to save image to $output_path.";
        return false;
    }

    // Clean up
    imagedestroy($image);
    imagedestroy($template);

    return $output_path;
}

function displayImages($files) {
    echo "<h2>Generated ID Cards</h2>";
    foreach ($files as $file) {
        $filename = basename($file);
        echo "<div><img src='temp_cards/$filename' alt='ID Card'></div><br>";
    }
    echo "<a href='printable_sheet.php'>Generate Printable Sheet</a>";
}
?>
