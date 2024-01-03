<?php
// Database configuration
$host = 'localhost';
$dbname = 'images';
$username = 'root';
$password = '6QV384sx$!';

// error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

function checkAndAddColumn($pdo, $columnName, $dataType = "VARCHAR(255)") {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM photos LIKE :column_name");
    $stmt->execute(['column_name' => $columnName]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE photos ADD `$columnName` $dataType");
    }
}

function isDateTimeExif($key, $value) {
    $dateTimePatterns = ['/DateTimeOriginal/', '/DateTimeDigitized/', '/DateTime/'];
    foreach ($dateTimePatterns as $pattern) {
        if (preg_match($pattern, $key)) {
            return true;
        }
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    $target_dir = "uploads/";
    $file = $_FILES["fileToUpload"];
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if ($imageFileType != "jpg" && $imageFileType != "jpeg") {
        echo "Sorry, only JPG & JPEG files are allowed.";
        exit;
    }

    $uniqueFileName = uniqid('', true) . '.' . $imageFileType;
    $target_file = $target_dir . basename($uniqueFileName);

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        echo "Sorry, there was an error uploading your file.";
        exit;
    }

    // Get image dimensions
    list($width, $height) = getimagesize($target_file);

    // Extract EXIF data
    $exif = exif_read_data($target_file);
    if ($exif === false) {
        echo "No EXIF data found.";
    }

    // File properties
    $fileSize = $file['size'];
    $originalFileName = $file['name'];

    // Start building SQL query
    $sql = "INSERT INTO photos (file_path, original_name, size, width, height";
    $valuesSql = " VALUES (:file_path, :original_name, :size, :width, :height";
    $data = [
        'file_path' => $target_file,
        'original_name' => $originalFileName,
        'size' => $fileSize,
        'width' => $width,
        'height' => $height
    ];

    // Check and add columns for file properties
    checkAndAddColumn($pdo, "original_name");
    checkAndAddColumn($pdo, "size", "INT");
    checkAndAddColumn($pdo, "width", "INT");
    checkAndAddColumn($pdo, "height", "INT");

    foreach ($exif as $key => $value) {
        $dataType = isDateTimeExif($key, $value) ? "DATETIME" : "VARCHAR(255)";
        $formattedValue = isDateTimeExif($key, $value) ? date('Y-m-d H:i:s', strtotime($value)) : $value;

        checkAndAddColumn($pdo, $key, $dataType);
        $sql .= ", `$key`";
        $valuesSql .= ", :$key";
        $data[$key] = $formattedValue;
    }

    $sql .= ")" . $valuesSql . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    echo "The file has been uploaded and its data stored in the database.";
}
?>

<form action="" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submit">
</form>
