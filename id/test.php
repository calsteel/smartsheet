<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['name']) && isset($_POST['id_number']) && isset($_FILES['photo'])) {
        $names = $_POST['name'];
        $id_numbers = $_POST['id_number'];
        $photos = $_FILES['photo'];
        $generated_files = [];

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $id_number = $id_numbers[$i];
            $photo = $photos['tmp_name'][$i];

            if ($photo) {
                $generated_file = generateIDCard($name, $id_number, $photo, $i);
                if ($generated_file) {
                    $generated_files[] = $generated_file;
                }
            }
        }

        if (count($generated_files) > 0) {
            createZipAndDownload($generated_files);
        } else {
            echo "Error: No ID cards generated.";
        }
    } else {
        echo "Error: Form data not set properly.";
    }
} else {
    echo "Error: No POST data received.";
}

function generateIDCard($name, $id_number, $photo_path, $index) {
    $width = 1011; // 3.37 inches * 300 dpi
    $height = 639; // 2.13 inches * 300 dpi
    $font = __DIR__ . '/arial.ttf'; // Make sure you have a .ttf font file in the same directory

    // Create the ID card image
    $image = imagecreatetruecolor($width, $height);
    if (!$image) {
        echo "Error: Unable to create image.";
        return false;
    }

    // Set background color to white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width, $height, $white);

    // Load and resize the photo
    $photo = @imagecreatefromjpeg($photo_path);
    if (!$photo) {
        echo "Error: Unable to load photo from $photo_path.";
        return false;
    }
    $photo_resized = imagescale($photo, 300, 300); // Assuming a square photo

    // Place the photo on the card
    imagecopy($image, $photo_resized, 50, 170, 0, 0, imagesx($photo_resized), imagesy($photo_resized));

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

    // Add name
    imagettftext($image, 24, 0, 400, 200, $black, $font, "Name: $name");

    // Add ID number
    imagettftext($image, 24, 0, 400, 300, $black, $font, "ID: $id_number");

    // Generate barcode
    $barcode_image = generateBarcode($id_number);
    if ($barcode_image) {
        imagecopy($image, $barcode_image, 400, 350, 0, 0, imagesx($barcode_image), imagesy($barcode_image));
    } else {
        echo "Error: Unable to generate barcode.";
    }

    // Save the image to a file
    $output_path = __DIR__ . "/id_card_$index.png";
    if (!imagepng($image, $output_path)) {
        echo "Error: Failed to save image to $output_path.";
        return false;
    }

    // Clean up
    imagedestroy($image);
    imagedestroy($photo);
    imagedestroy($photo_resized);
    if ($barcode_image) {
        imagedestroy($barcode_image);
    }

    return $output_path;
}

function generateBarcode($text) {
    $barcode_width = 300;
    $barcode_height = 100;
    $barcode_image = imagecreatetruecolor($barcode_width, $barcode_height);
    $white = imagecolorallocate($barcode_image, 255, 255, 255);
    $black = imagecolorallocate($barcode_image, 0, 0, 0);
    imagefilledrectangle($barcode_image, 0, 0, $barcode_width, $barcode_height, $white);

    // Use a simple barcode generator library
    $font = __DIR__ . '/Free3of9.ttf'; // Make sure you have a barcode font .ttf file
    if (!file_exists($font)) {
        echo "Error: Barcode font file not found.";
        return false;
    }

    imagettftext($barcode_image, 40, 0, 10, 60, $black, $font, "*$text*");

    return $barcode_image;
}

function createZipAndDownload($files) {
    $zip = new ZipArchive();
    $zip_file = 'id_cards.zip';

    if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {

        exit("Cannot open <$zip_file>\n");
    }

    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }

    $zip->close();

    // Set headers to force download
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zip_file);
    header('Content-Length: ' . filesize($zip_file));
    readfile($zip_file);

    // Clean up generated files and zip file
    foreach ($files as $file) {
        unlink($file);
    }
    unlink($zip_file);
}
?>
