<?php
// Printer details
$printer = $_POST['printer'];
$username = 'calsteel/scan';
$password = 'abcd1234!@';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['image_data'])) {
        $image_data = base64_decode($_POST['image_data']);
        $zpl_code = convertToZPL($image_data);

        if ($zpl_code) {
            // Create a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'zpl');
            file_put_contents($temp_file, $zpl_code);

            // Command to print the file using smbclient
            $cmd = "smbclient '$printer' -U '$username%$password' -c 'print $temp_file'";

            // Execute the command
            exec($cmd, $output, $return_var);

            // Clean up the temporary file
            unlink($temp_file);

            // Check if the command was successful
            if ($return_var === 0) {
                echo "Printed successfully!";
            } else {
                echo "Failed to print. Return code: $return_var\n";
                echo "Output:\n" . implode("\n", $output);
            }
        } else {
            echo "Error: Unable to generate ZPL code.";
        }
    } else {
        echo "Error: Image data not received.";
    }
} else {
    echo "Error: No POST data received.";
}

function convertToZPL($image_data) {
    $image = imagecreatefromstring($image_data);
    if (!$image) {
        echo "Error: Unable to create image from string<br>";
        return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $width_bytes = intdiv($width + 7, 8);
    $total_bytes = $width_bytes * $height;
    $hex_data = '';

    for ($y = 0; $y < $height; $y++) {
        $row_hex = '';
        for ($x = 0; $x < $width_bytes * 8; $x += 8) {
            $byte = 0;
            for ($b = 0; $b < 8; $b++) {
                $px = $x + $b;
                if ($px < $width) {
                    $color = imagecolorat($image, $px, $y);
                    if ($color == 0x000000) {
                        $byte |= (1 << (7 - $b));
                    }
                }
            }
            $row_hex .= sprintf('%02X', $byte);
        }
        $hex_data .= $row_hex . "\n";
    }

    $zpl = "^XA\n^FO0,0^GFA,$total_bytes,$total_bytes,$width_bytes, $hex_data^FS\n^XZ";
    imagedestroy($image);
    return $zpl;
}
?>
