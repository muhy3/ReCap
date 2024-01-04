<?php
$target_dir = "/var/www/html/upload/uploads/";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["jpegFile"]) && $_FILES["jpegFile"]["error"] == 0) {
        $filename = $_FILES["jpegFile"]["name"];
        $filetype = $_FILES["jpegFile"]["type"];
        $filesize = $_FILES["jpegFile"]["size"];

        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext != "jpg" && $ext != "jpeg") {
            echo "Error: Please upload a JPEG file.";
        } else {
            // Generate unique ID for the file
            $newFilename = uniqid() . "." . $ext;
            $target_file = $target_dir . $newFilename;

            // Check file size (e.g., max 5MB)
            if ($filesize > 5000000) {
                echo "Error: File size is larger than the allowed limit.";
            } else {
                // Try to move the uploaded file to the target directory
                if (move_uploaded_file($_FILES["jpegFile"]["tmp_name"], $target_file)) {
                    echo "The file " . htmlspecialchars(basename($filename)) . " has been uploaded as " . $newFilename . "<br>";

                    // Print EXIF information
                    $exif = exif_read_data($target_file);
                    if ($exif && isset($exif['COMPUTED']) && isset($exif['COMPUTED']['Html'])) {
                        echo "EXIF Information:<br>";
                        echo $exif['COMPUTED']['Html'];
                    } else {
                        echo "No EXIF data found.";
                    }
                } else {
                    echo "Error: There was an error uploading your file.";
                }
            }
        }
    } else {
        echo "Error: " . $_FILES["jpegFile"]["error"];
    }
}
?>
