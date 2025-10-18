<?php
require_once '../config.php';

if (!isLoggedIn()) {
    die("Access Denied.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM servers WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../dashboard.php");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}
?>