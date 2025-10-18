<?php
require_once '../config.php';

if (!isLoggedIn()) {
    die("Access Denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $address = $_POST['address'];

    // ตรวจสอบว่าเป็น IP หรือ URL
    $type = filter_var($address, FILTER_VALIDATE_URL) ? 'url' : 'ip';

    $stmt = $conn->prepare("INSERT INTO servers (name, address, type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $address, $type);

    if ($stmt->execute()) {
        header("Location: ../dashboard.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>