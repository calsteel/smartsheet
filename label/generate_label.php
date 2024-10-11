<?php
// Version 1.4

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/phpqrcode/qrlib.php'; // Include the QR code library

// generate_label.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['lot']) && isset($_POST['heat']) && isset($_POST['tag']) && 
        isset($_POST['grade']) && isset($_POST['thickness']) && isset($_POST['width']) && 
        isset($_POST['length']) && isset($_POST['type']) && isset($_POST['sales_order']) && 
        isset($_POST['purchase_order']) && isset($_POST['item_code']) && isset($_POST['description']) &&
        isset($_POST['customerno']) && isset($_POST['customername']) && isset($_POST['quantity']) &&
        isset($_POST['printer']) && isset($_POST['num_copies']) && isset($_POST['template'])) {
        
        $form_data = [
            "LOT" => $_POST['lot'],
            "Heat #" => $_POST['heat'],
            "Tag #" => $_POST['tag'],
            "Grade" => $_POST['grade'],
            "Thickness" => $_POST['thickness'],
            "Width" => $_POST['width'],
            "Length" => $_POST['length'],
            "Type" => $_POST['type'],
            "Sales Order" => $_POST['sales_order'],
            "Purchase Order" => $_POST['purchase_order'],
            "Item Code" => $_POST['item_code'],
            "Description" => $_POST['description'],
            "Customer Number" => $_POST['customerno'],
            "Customer Name" => $_POST['customername'],
            "Quantity" => $_POST['quantity']
        ];

        $json_data = json_encode($form_data);
        $template = $_POST['template'];
        $generated_image = '';

        switch ($template) {
            case 'template1':
                $generated_image = generateLabelTemplate1($form_data, $json_data);
                break;
            case 'template2':
                $generated_image = generateLabelTemplate2($form_data, $json_data);
                break;
			case 'template3':
                $generated_image = generateLabelTemplate3($form_data, $json_data);
                break;
			case 'template4':
                $generated_image = generateLabelTemplate4($form_data, $json_data);
                break;
            // Add more templates as needed
            default:
                echo "Error: Unknown template selected.";
                exit;
        }

        if ($generated_image) {
             displayLabel($generated_image, $_POST['printer'], $_POST['num_copies']);
        } else {
            echo "Error: Label not generated.";
        }
    } else {
        echo "Error: Form data not set properly.";
    }
} else {
    echo "Error: No POST data received.";
}


function generateLabelTemplate4($form_data, $json_data) {
    $width_px = 812; // 4 inches * 203 DPI
    $height_px = 1218; // 6 inches * 203 DPI
    $font = __DIR__ . '/assets/arial.ttf'; // Make sure you have a .ttf font file in the same directory
    $font2 = __DIR__ . '/assets/arialblack.ttf'; // Make sure you have a .ttf font file in the same directory
    $logo_path = __DIR__ . '/assets/logo.jpg'; // Path to your logo image
    $text_logo_path = __DIR__ . '/assets/text.png'; // Path to your text logo image
    $logo_percentage = 20; // Main logo size as a percentage of the width
    $text_logo_percentage = 70; // Text logo size as a percentage of the width

    // Create the label image
    $image = imagecreatetruecolor($width_px, $height_px);
    if (!$image) {
        echo "Error: Unable to create image<br>";
        return false;
    }

    // Set background color to white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width_px, $height_px, $white);

    // Load the logo image using Imagick
    try {
        $imagick = new Imagick($logo_path);
        $imagick->setImageFormat('png');
        $logo = imagecreatefromstring($imagick->getImageBlob());
        $imagick->destroy();
    } catch (Exception $e) {
        echo "Error: Unable to load logo image from $logo_path. " . $e->getMessage();
        return false;
    }

    // Load the text logo image
    $text_logo = imagecreatefrompng($text_logo_path);
    if (!$text_logo) {
        echo "Error: Unable to load text logo image from $text_logo_path.";
        return false;
    }

    // Resize the main logo to the specified percentage of the width
    $logo_new_width = ($width_px * $logo_percentage) / 100;
    $logo_new_height = ($logo_new_width / imagesx($logo)) * imagesy($logo);
    $logo_resized = imagecreatetruecolor($logo_new_width, $logo_new_height);
    imagecopyresampled($logo_resized, $logo, 0, 0, 0, 0, $logo_new_width, $logo_new_height, imagesx($logo), imagesy($logo));

    // Resize the text logo to the specified percentage of the width
    $text_logo_new_width = ($width_px * $text_logo_percentage) / 100;
    $text_logo_new_height = ($text_logo_new_width / imagesx($text_logo)) * imagesy($text_logo);
    $text_logo_resized = imagecreatetruecolor($text_logo_new_width, $text_logo_new_height);
    imagecopyresampled($text_logo_resized, $text_logo, 0, 0, 0, 0, $text_logo_new_width, $text_logo_new_height, imagesx($text_logo), imagesy($text_logo));

    // Calculate the position to center the logos at the top
    $total_logo_height = $logo_new_height + $text_logo_new_height + 10; // Add 10 pixels space between logos
    $logo_x = 20;
    $text_logo_x = 180;
    $logo_y = 10; // Top margin
    $text_logo_y = 30; // Position below the main logo with a gap

    // Place the resized main logo on the card
    imagecopy($image, $logo_resized, $logo_x, $logo_y, 0, 0, $logo_new_width, $logo_new_height);

    // Place the resized text logo on the card
    imagecopy($image, $text_logo_resized, $text_logo_x, $text_logo_y, 0, 0, $text_logo_new_width, $text_logo_new_height);

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

    // Add header information
    $header_text1 = "1212 S. Mountain View Ave. San Bernardino, CA 92408";
    $header_text2 = "P: (800) 323-7227 | F: (909) 796-8888 | W: calsteel.com";

    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 20, $black, $font, $header_text1);
    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 50, $black, $font, $header_text2);

    // Adjust starting position for text and QR codes to be below the header information
    $y_pos = $text_logo_y + $text_logo_new_height + 100;

    // Adding individual fields with option to position or not show, and to disable QR code
   
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Heat #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Heat #'], 'Heat #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Heat #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Tag #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Tag #'], 'Tag #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Tag #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Item Code:', 50, $y_pos, $font2, $black);
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Item Code'], 'Item Code', 50, $y_pos);
    addQRCode($image, $form_data['Item Code'], 650, $y_pos - 80);
    
    $y_pos += 50;
    addText($image, 'Description:', 50, $y_pos, $font2, $black);    
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Description'], 'Description', 50, $y_pos);
    $y_pos += 50;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 50;
    addText($image, 'Quantity:', 50, $y_pos, $font2, $black);    
    addField($image, $font, $black, $form_data['Quantity'], 'Quantity', 300, $y_pos);
    addQRCode($image, $form_data['Quantity'], 650, $y_pos - 65);
    
    $y_pos += 490;
		date_default_timezone_set('America/Los_Angeles');

	addText($image, date('m/d/Y g:i A'), 50, $y_pos, $font, $black); 
	
    
    

    // Capture the image output
    ob_start();
    imagepng($image);
    $image_data = ob_get_contents();
    ob_end_clean();

    // Clean up
    imagedestroy($image);
    imagedestroy($logo);
    imagedestroy($logo_resized);
    imagedestroy($text_logo);
    imagedestroy($text_logo_resized);

    return $image_data;
}


function generateLabelTemplate3($form_data, $json_data) {
    $width_px = 812; // 4 inches * 203 DPI
    $height_px = 1218; // 6 inches * 203 DPI
    $font = __DIR__ . '/assets/arial.ttf'; // Make sure you have a .ttf font file in the same directory
    $font2 = __DIR__ . '/assets/arialblack.ttf'; // Make sure you have a .ttf font file in the same directory
    $logo_path = __DIR__ . '/assets/logo.jpg'; // Path to your logo image
    $text_logo_path = __DIR__ . '/assets/text.png'; // Path to your text logo image
    $logo_percentage = 20; // Main logo size as a percentage of the width
    $text_logo_percentage = 70; // Text logo size as a percentage of the width

    // Create the label image
    $image = imagecreatetruecolor($width_px, $height_px);
    if (!$image) {
        echo "Error: Unable to create image<br>";
        return false;
    }

    // Set background color to white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width_px, $height_px, $white);

    // Load the logo image using Imagick
    try {
        $imagick = new Imagick($logo_path);
        $imagick->setImageFormat('png');
        $logo = imagecreatefromstring($imagick->getImageBlob());
        $imagick->destroy();
    } catch (Exception $e) {
        echo "Error: Unable to load logo image from $logo_path. " . $e->getMessage();
        return false;
    }

    // Load the text logo image
    $text_logo = imagecreatefrompng($text_logo_path);
    if (!$text_logo) {
        echo "Error: Unable to load text logo image from $text_logo_path.";
        return false;
    }

    // Resize the main logo to the specified percentage of the width
    $logo_new_width = ($width_px * $logo_percentage) / 100;
    $logo_new_height = ($logo_new_width / imagesx($logo)) * imagesy($logo);
    $logo_resized = imagecreatetruecolor($logo_new_width, $logo_new_height);
    imagecopyresampled($logo_resized, $logo, 0, 0, 0, 0, $logo_new_width, $logo_new_height, imagesx($logo), imagesy($logo));

    // Resize the text logo to the specified percentage of the width
    $text_logo_new_width = ($width_px * $text_logo_percentage) / 100;
    $text_logo_new_height = ($text_logo_new_width / imagesx($text_logo)) * imagesy($text_logo);
    $text_logo_resized = imagecreatetruecolor($text_logo_new_width, $text_logo_new_height);
    imagecopyresampled($text_logo_resized, $text_logo, 0, 0, 0, 0, $text_logo_new_width, $text_logo_new_height, imagesx($text_logo), imagesy($text_logo));

    // Calculate the position to center the logos at the top
    $total_logo_height = $logo_new_height + $text_logo_new_height + 10; // Add 10 pixels space between logos
    $logo_x = 20;
    $text_logo_x = 180;
    $logo_y = 10; // Top margin
    $text_logo_y = 30; // Position below the main logo with a gap

    // Place the resized main logo on the card
    imagecopy($image, $logo_resized, $logo_x, $logo_y, 0, 0, $logo_new_width, $logo_new_height);

    // Place the resized text logo on the card
    imagecopy($image, $text_logo_resized, $text_logo_x, $text_logo_y, 0, 0, $text_logo_new_width, $text_logo_new_height);

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

    // Add header information
    $header_text1 = "1212 S. Mountain View Ave. San Bernardino, CA 92408";
    $header_text2 = "P: (800) 323-7227 | F: (909) 796-8888 | W: calsteel.com";

    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 20, $black, $font, $header_text1);
    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 50, $black, $font, $header_text2);

    // Adjust starting position for text and QR codes to be below the header information
    $y_pos = $text_logo_y + $text_logo_new_height + 100;

    // Adding individual fields with option to position or not show, and to disable QR code
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Customer:', 50, $y_pos, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Customer Number'], 'Customer', 300, $y_pos - 20);
    $y_pos += 20;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'SO #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Sales Order'], 'Sales Order', 300, $y_pos + 10);
    addQRCode($image, $form_data['Sales Order'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'PO #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Purchase Order'], 'Purchase Order', 300, $y_pos + 10);
    addQRCode($image, $form_data['Purchase Order'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Heat #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Heat #'], 'Heat #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Heat #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Tag #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Tag #'], 'Tag #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Tag #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Item Code:', 50, $y_pos, $font2, $black);
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Item Code'], 'Item Code', 50, $y_pos);
    addQRCode($image, $form_data['Item Code'], 650, $y_pos - 80);
    
    $y_pos += 50;
    addText($image, 'Description:', 50, $y_pos, $font2, $black);    
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Description'], 'Description', 50, $y_pos);
    $y_pos += 50;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 50;
    addText($image, 'Quantity:', 50, $y_pos, $font2, $black);    
    addField($image, $font, $black, $form_data['Quantity'], 'Quantity', 300, $y_pos);
    addQRCode($image, $form_data['Quantity'], 650, $y_pos - 65);
    
    $y_pos += 180;
		date_default_timezone_set('America/Los_Angeles');

	addText($image, date('m/d/Y g:i A'), 50, $y_pos, $font, $black); 
	
    
    

    // Capture the image output
    ob_start();
    imagepng($image);
    $image_data = ob_get_contents();
    ob_end_clean();

    // Clean up
    imagedestroy($image);
    imagedestroy($logo);
    imagedestroy($logo_resized);
    imagedestroy($text_logo);
    imagedestroy($text_logo_resized);

    return $image_data;
}


function generateLabelTemplate1($form_data, $json_data) {
    $width_px = 812; // 4 inches * 203 DPI
    $height_px = 1218; // 6 inches * 203 DPI
    $font = __DIR__ . '/assets/arial.ttf'; // Make sure you have a .ttf font file in the same directory
    $font2 = __DIR__ . '/assets/arialblack.ttf'; // Make sure you have a .ttf font file in the same directory
    $logo_path = __DIR__ . '/assets/logo.jpg'; // Path to your logo image
    $text_logo_path = __DIR__ . '/assets/text.png'; // Path to your text logo image
    $logo_percentage = 20; // Main logo size as a percentage of the width
    $text_logo_percentage = 70; // Text logo size as a percentage of the width

    // Create the label image
    $image = imagecreatetruecolor($width_px, $height_px);
    if (!$image) {
        echo "Error: Unable to create image<br>";
        return false;
    }

    // Set background color to white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width_px, $height_px, $white);

    // Load the logo image using Imagick
    try {
        $imagick = new Imagick($logo_path);
        $imagick->setImageFormat('png');
        $logo = imagecreatefromstring($imagick->getImageBlob());
        $imagick->destroy();
    } catch (Exception $e) {
        echo "Error: Unable to load logo image from $logo_path. " . $e->getMessage();
        return false;
    }

    // Load the text logo image
    $text_logo = imagecreatefrompng($text_logo_path);
    if (!$text_logo) {
        echo "Error: Unable to load text logo image from $text_logo_path.";
        return false;
    }

    // Resize the main logo to the specified percentage of the width
    $logo_new_width = ($width_px * $logo_percentage) / 100;
    $logo_new_height = ($logo_new_width / imagesx($logo)) * imagesy($logo);
    $logo_resized = imagecreatetruecolor($logo_new_width, $logo_new_height);
    imagecopyresampled($logo_resized, $logo, 0, 0, 0, 0, $logo_new_width, $logo_new_height, imagesx($logo), imagesy($logo));

    // Resize the text logo to the specified percentage of the width
    $text_logo_new_width = ($width_px * $text_logo_percentage) / 100;
    $text_logo_new_height = ($text_logo_new_width / imagesx($text_logo)) * imagesy($text_logo);
    $text_logo_resized = imagecreatetruecolor($text_logo_new_width, $text_logo_new_height);
    imagecopyresampled($text_logo_resized, $text_logo, 0, 0, 0, 0, $text_logo_new_width, $text_logo_new_height, imagesx($text_logo), imagesy($text_logo));

    // Calculate the position to center the logos at the top
    $total_logo_height = $logo_new_height + $text_logo_new_height + 10; // Add 10 pixels space between logos
    $logo_x = 20;
    $text_logo_x = 180;
    $logo_y = 10; // Top margin
    $text_logo_y = 30; // Position below the main logo with a gap

    // Place the resized main logo on the card
    imagecopy($image, $logo_resized, $logo_x, $logo_y, 0, 0, $logo_new_width, $logo_new_height);

    // Place the resized text logo on the card
    imagecopy($image, $text_logo_resized, $text_logo_x, $text_logo_y, 0, 0, $text_logo_new_width, $text_logo_new_height);

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

    // Add header information
    $header_text1 = "1212 S. Mountain View Ave. San Bernardino, CA 92408";
    $header_text2 = "P: (800) 323-7227 | F: (909) 796-8888 | W: calsteel.com";

    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 20, $black, $font, $header_text1);
    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 50, $black, $font, $header_text2);

    // Adjust starting position for text and QR codes to be below the header information
    $y_pos = $text_logo_y + $text_logo_new_height + 100;

    // Adding individual fields with option to position or not show, and to disable QR code
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Customer:', 50, $y_pos, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Customer Number'], 'Customer', 300, $y_pos - 20);
    $y_pos += 20;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'SO #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Sales Order'], 'Sales Order', 300, $y_pos + 10);
    addQRCode($image, $form_data['Sales Order'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'PO #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Purchase Order'], 'Purchase Order', 300, $y_pos + 10);
    addQRCode($image, $form_data['Purchase Order'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Heat #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Heat #'], 'Heat #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Heat #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Tag #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Tag #'], 'Tag #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Tag #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Thickness', 40, $y_pos, $font2, $black);
    addText($image, 'Width', 260, $y_pos, $font2, $black);
    addText($image, 'Length', 400, $y_pos, $font2, $black);
    addText($image, 'Grade', 620, $y_pos, $font2, $black);
    $y_pos += 50;
    addCenteredText($image, $form_data['Thickness'], $y_pos, $font, $black, 250);
    addCenteredText($image, $form_data['Width'], $y_pos, $font, $black, 620);
    addCenteredText($image, $form_data['Length'], $y_pos, $font, $black, 920);
    addCenteredText($image, $form_data['Grade'], $y_pos, $font, $black, 1340);
    $y_pos += 60;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Item Code:', 50, $y_pos, $font2, $black);
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Item Code'], 'Item Code', 50, $y_pos);
    addQRCode($image, $form_data['Item Code'], 650, $y_pos - 80);
    
    $y_pos += 50;
    addText($image, 'Description:', 50, $y_pos, $font2, $black);    
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Description'], 'Description', 50, $y_pos);
    $y_pos += 50;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 50;
    addText($image, 'Quantity:', 50, $y_pos, $font2, $black);    
    addField($image, $font, $black, $form_data['Quantity'], 'Quantity', 300, $y_pos);
    addQRCode($image, $form_data['Quantity'], 650, $y_pos - 65);
    
    $y_pos += 55;
		date_default_timezone_set('America/Los_Angeles');

	addText($image, date('m/d/Y g:i A'), 50, $y_pos, $font, $black); 
	
    
    

    // Capture the image output
    ob_start();
    imagepng($image);
    $image_data = ob_get_contents();
    ob_end_clean();

    // Clean up
    imagedestroy($image);
    imagedestroy($logo);
    imagedestroy($logo_resized);
    imagedestroy($text_logo);
    imagedestroy($text_logo_resized);

    return $image_data;
}


function generateLabelTemplate2($form_data, $json_data) {
    $width_px = 812; // 4 inches * 203 DPI
    $height_px = 1218; // 6 inches * 203 DPI
    $font = __DIR__ . '/assets/arial.ttf'; // Make sure you have a .ttf font file in the same directory
    $font2 = __DIR__ . '/assets/arialblack.ttf'; // Make sure you have a .ttf font file in the same directory
    $logo_path = __DIR__ . '/assets/logo.jpg'; // Path to your logo image
    $text_logo_path = __DIR__ . '/assets/text.png'; // Path to your text logo image
    $logo_percentage = 20; // Main logo size as a percentage of the width
    $text_logo_percentage = 70; // Text logo size as a percentage of the width

    // Create the label image
    $image = imagecreatetruecolor($width_px, $height_px);
    if (!$image) {
        echo "Error: Unable to create image<br>";
        return false;
    }

    // Set background color to white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width_px, $height_px, $white);

    // Load the logo image using Imagick
    try {
        $imagick = new Imagick($logo_path);
        $imagick->setImageFormat('png');
        $logo = imagecreatefromstring($imagick->getImageBlob());
        $imagick->destroy();
    } catch (Exception $e) {
        echo "Error: Unable to load logo image from $logo_path. " . $e->getMessage();
        return false;
    }

    // Load the text logo image
    $text_logo = imagecreatefrompng($text_logo_path);
    if (!$text_logo) {
        echo "Error: Unable to load text logo image from $text_logo_path.";
        return false;
    }

    // Resize the main logo to the specified percentage of the width
    $logo_new_width = ($width_px * $logo_percentage) / 100;
    $logo_new_height = ($logo_new_width / imagesx($logo)) * imagesy($logo);
    $logo_resized = imagecreatetruecolor($logo_new_width, $logo_new_height);
    imagecopyresampled($logo_resized, $logo, 0, 0, 0, 0, $logo_new_width, $logo_new_height, imagesx($logo), imagesy($logo));

    // Resize the text logo to the specified percentage of the width
    $text_logo_new_width = ($width_px * $text_logo_percentage) / 100;
    $text_logo_new_height = ($text_logo_new_width / imagesx($text_logo)) * imagesy($text_logo);
    $text_logo_resized = imagecreatetruecolor($text_logo_new_width, $text_logo_new_height);
    imagecopyresampled($text_logo_resized, $text_logo, 0, 0, 0, 0, $text_logo_new_width, $text_logo_new_height, imagesx($text_logo), imagesy($text_logo));

    // Calculate the position to center the logos at the top
    $total_logo_height = $logo_new_height + $text_logo_new_height + 10; // Add 10 pixels space between logos
    $logo_x = 20;
    $text_logo_x = 180;
    $logo_y = 10; // Top margin
    $text_logo_y = 30; // Position below the main logo with a gap

    // Place the resized main logo on the card
    imagecopy($image, $logo_resized, $logo_x, $logo_y, 0, 0, $logo_new_width, $logo_new_height);

    // Place the resized text logo on the card
    imagecopy($image, $text_logo_resized, $text_logo_x, $text_logo_y, 0, 0, $text_logo_new_width, $text_logo_new_height);

    // Set text color to black
    $black = imagecolorallocate($image, 0, 0, 0);

    // Add header information
    $header_text1 = "1212 S. Mountain View Ave. San Bernardino, CA 92408";
    $header_text2 = "P: (800) 323-7227 | F: (909) 796-8888 | W: calsteel.com";

    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 20, $black, $font, $header_text1);
    imagettftext($image, 18, 0, 200, $text_logo_y + $text_logo_new_height + 50, $black, $font, $header_text2);

    // Adjust starting position for text and QR codes to be below the header information
    $y_pos = $text_logo_y + $text_logo_new_height + 100;

    // Adding individual fields with option to position or not show, and to disable QR code
   
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Heat #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Heat #'], 'Heat #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Heat #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 20;
    addText($image, 'Tag #:', 50, $y_pos + 30, $font2, $black);
    $y_pos += 20;
    addField($image, $font, $black, $form_data['Tag #'], 'Tag #', 300, $y_pos + 10);
    addQRCode($image, $form_data['Tag #'], 650, $y_pos - 55);
    $y_pos += 80;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Thickness', 40, $y_pos, $font2, $black);
    addText($image, 'Width', 260, $y_pos, $font2, $black);
    addText($image, 'Length', 400, $y_pos, $font2, $black);
    addText($image, 'Grade', 620, $y_pos, $font2, $black);
    $y_pos += 50;
    addCenteredText($image, $form_data['Thickness'], $y_pos, $font, $black, 250);
    addCenteredText($image, $form_data['Width'], $y_pos, $font, $black, 620);
    addCenteredText($image, $form_data['Length'], $y_pos, $font, $black, 920);
    addCenteredText($image, $form_data['Grade'], $y_pos, $font, $black, 1340);
    $y_pos += 60;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 30;
    addText($image, 'Item Code:', 50, $y_pos, $font2, $black);
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Item Code'], 'Item Code', 50, $y_pos);
    addQRCode($image, $form_data['Item Code'], 650, $y_pos - 80);
    
    $y_pos += 50;
    addText($image, 'Description:', 50, $y_pos, $font2, $black);    
    $y_pos += 50;
    addField($image, $font, $black, $form_data['Description'], 'Description', 50, $y_pos);
    $y_pos += 50;
    
    drawLine($image, 50, $y_pos - 20, $width_px - 50);
    $y_pos += 50;
    addText($image, 'Quantity:', 50, $y_pos, $font2, $black);    
    addField($image, $font, $black, $form_data['Quantity'], 'Quantity', 300, $y_pos);
    addQRCode($image, $form_data['Quantity'], 650, $y_pos - 65);
    
    $y_pos += 340;
	 date_default_timezone_set('America/Los_Angeles');
    addText($image, date('m/d/Y g:i A'), 50, $y_pos, $font, $black); 
	
    
   

    // Capture the image output
    ob_start();
    imagepng($image);
    $image_data = ob_get_contents();
    ob_end_clean();

    // Clean up
    imagedestroy($image);
    imagedestroy($logo);
    imagedestroy($logo_resized);
    imagedestroy($text_logo);
    imagedestroy($text_logo_resized);

    return $image_data;
}


function addField($image, $font, $black, $value, $label, $x_pos, $y_pos) {
    if (!empty($value)) {
        imagettftext($image, 24, 0, $x_pos, $y_pos, $black, $font, "$value");
    }
}

function addText($image, $text, $x_pos, $y_pos, $font, $black) {
    if (!empty($text)) {
        imagettftext($image, 24, 0, $x_pos, $y_pos, $black, $font, $text);
    }
}

function addCenteredText($image, $text, $y_pos, $font, $black, $image_width) {
    if (!empty($text)) {
        $bbox = imagettfbbox(24, 0, $font, $text);
        $text_width = $bbox[2] - $bbox[0];
        $x_pos = ($image_width - $text_width) / 2;
        imagettftext($image, 22, 0, $x_pos, $y_pos, $black, $font, $text);
    }
}

function addQRCode($image, $value, $qr_x, $qr_y) {
    if (!empty($value)) {
        // Generate QR code for individual field
        $qr_code_path = tempnam(sys_get_temp_dir(), 'qr_');
        QRcode::png($value, $qr_code_path, QR_ECLEVEL_L, 4);

        $qr_code = imagecreatefrompng($qr_code_path);
        if ($qr_code) {
            imagecopy($image, $qr_code, $qr_x, $qr_y, 0, 0, imagesx($qr_code), imagesy($qr_code));
            imagedestroy($qr_code);
            unlink($qr_code_path); // Remove the temporary QR code image
        } else {
            echo "Error: Unable to generate QR code.";
        }
    }
}

function drawLine($image, $x1, $y1, $x2) {
    $black = imagecolorallocate($image, 0, 0, 0);
    imageline($image, $x1, $y1, $x2, $y1, $black);
}

function displayLabel($image_data, $printer, $num_copies) {
    $base64_image = base64_encode($image_data);
    echo "
    
	
        <img src='data:image/png;base64,$base64_image' alt='Shipping Label' style='width:100%;'>
        <form id='printLabelForm' method='POST' action='print_label.php'>
            <input type='hidden' name='printer' value='$printer'>
			<input type='hidden' name='num_copies' value='$num_copies'>
            <input type='hidden' name='image_data' value='$base64_image'>
        </form>
        <div id='status' style='display:none;'></div>
 
   
    ";
}

?>
