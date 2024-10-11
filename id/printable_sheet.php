<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Define the temporary directory path
$temp_dir = __DIR__ . '/temp_cards/';

// Ensure the directory exists
if (!is_dir($temp_dir)) {
    echo "Error: Temporary directory not found.";
    exit;
}

// Check if session files are set
if (!isset($_SESSION['files']) || empty($_SESSION['files'])) {
    echo "No ID cards to display.";
    exit;
}

$files = $_SESSION['files'];
unset($_SESSION['files']);

// Set dimensions for the printable sheet (assuming 300 DPI)
$page_width = 2550; // 8.5 inches at 300 DPI
$page_height = 3300; // 11 inches at 300 DPI
$id_width = 639; // ID card width at 300 DPI (keeping original dimensions)
$id_height = 1011; // ID card height at 300 DPI (keeping original dimensions)
$columns = 3;
$rows = 3;
$margin_x = 50; // Horizontal margin between ID cards
$margin_y = 50; // Vertical margin between ID cards

// Create a new image for the printable sheet
$sheet = imagecreatetruecolor($page_width, $page_height);
$white = imagecolorallocate($sheet, 255, 255, 255);
imagefilledrectangle($sheet, 0, 0, $page_width, $page_height, $white);

$x = $margin_x;
$y = $margin_y;
$index = 0;
$page_index = 1;

foreach ($files as $file) {
    $file_path = $temp_dir . basename($file);

    if (!file_exists($file_path)) {
        echo "Error: File does not exist at path $file_path.<br>";
        continue;
    }

    $id_card = imagecreatefrompng($file_path);
    if (!$id_card) {
        echo "Error: Unable to load image from $file_path.<br>";
        continue;
    }

    // Ensure the dimensions are correct before copying
    $source_width = imagesx($id_card);
    $source_height = imagesy($id_card);

    if ($source_width == 0 || $source_height == 0) {
        echo "Error: Invalid source image dimensions for file $file_path.<br>";
        imagedestroy($id_card);
        continue;
    }

    // Place the ID card on the sheet without resizing, keeping original dimensions (639 x 1011)
    imagecopy($sheet, $id_card, $x, $y, 0, 0, $source_width, $source_height);
    imagedestroy($id_card);

    $index++;
    if ($index % $columns == 0) {
        $x = $margin_x;
        $y += $id_height + $margin_y;
    } else {
        $x += $id_width + $margin_x;
    }

    // If we reach the maximum rows per page (9 cards), create a new page
    if ($index % ($columns * $rows) == 0) {
        $output_path = $temp_dir . "printable_sheet_$page_index.png";
        if (!imagepng($sheet, $output_path)) {
            echo "Error: Failed to save image to $output_path.<br>";
        } else {
            echo "Info: Successfully saved printable sheet $output_path.<br>";
        }
        imagedestroy($sheet);

        // Start a new sheet for the next batch of cards
        $sheet = imagecreatetruecolor($page_width, $page_height);
        $white = imagecolorallocate($sheet, 255, 255, 255);
        imagefilledrectangle($sheet, 0, 0, $page_width, $page_height, $white);
        $x = $margin_x;
        $y = $margin_y;
        $page_index++;
    }
}

// Save the final page if it has any content
if ($index % ($columns * $rows) != 0) {
    $output_path = $temp_dir . "printable_sheet_$page_index.png";
    if (!imagepng($sheet, $output_path)) {
        echo "Error: Failed to save final image to $output_path.<br>";
    } else {
        echo "Info: Successfully saved final printable sheet $output_path.<br>";
    }
    imagedestroy($sheet);
    $page_index++; // Increment the page index to correctly display the final page
}

// Display all generated sheets
echo "<h2>Printable Sheets</h2>";
for ($i = 1; $i < $page_index; $i++) {
    $sheet_path = "temp_cards/printable_sheet_$i.png";
    if (file_exists($temp_dir . "printable_sheet_$i.png")) {
        echo "<div><img src='$sheet_path' alt='Printable Sheet' style='border: 1px solid #000; margin-bottom: 20px;'></div>";
    } else {
        echo "Error: Printable sheet not found at $sheet_path.<br>";
    }
}
?>
