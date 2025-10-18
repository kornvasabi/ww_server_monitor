<?php
require_once '../config.php';

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบว่าใช้ POST method หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

// ตรวจสอบว่ามีข้อมูลที่ส่งมาครบหรือไม่
if (!isset($_POST['id']) || empty($_POST['id']) || 
    !isset($_POST['name']) || empty($_POST['name']) || 
    !isset($_POST['address']) || empty($_POST['address'])) {
    
    header("Location: ../dashboard.php?error=missing_fields");
    exit();
}

$server_id = intval($_POST['id']);
$name = trim($_POST['name']);
$address = trim($_POST['address']);

// ตรวจสอบความยาวของข้อมูล
if (strlen($name) < 2 || strlen($name) > 100) {
    header("Location: ../edit_server.php?id=" . $server_id . "&error=invalid_name");
    exit();
}

if (strlen($address) < 5 || strlen($address) > 255) {
    header("Location: ../edit_server.php?id=" . $server_id . "&error=invalid_address");
    exit();
}

// ตรวจสอบว่า server_id นี้มีอยู่จริง
$check_stmt = $conn->prepare("SELECT id FROM servers WHERE id = ?");
$check_stmt->bind_param("i", $server_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    header("Location: ../dashboard.php?error=server_not_found");
    exit();
}
$check_stmt->close();

// บันทึกการแก้ไข
$stmt = $conn->prepare("UPDATE servers SET name = ?, address = ? WHERE id = ?");

if (!$stmt) {
    die("ข้อผิดพลาดในการเตรียมคำสั่ง: " . $conn->error);
}

$stmt->bind_param("ssi", $name, $address, $server_id);

if ($stmt->execute()) {
    $stmt->close();
    // บันทึกสำเร็จ
    header("Location: ../dashboard.php?success=server_updated");
    exit();
} else {
    $stmt->close();
    // บันทึกล้มเหลว
    header("Location: ../edit_server.php?id=" . $server_id . "&error=update_failed");
    exit();
}
?>