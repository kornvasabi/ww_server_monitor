<?php
session_start();
require_once '../config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit();
}

// รับข้อมูลจาก POST
$data = json_decode(file_get_contents('php://input'), true);
$serverId = isset($data['id']) ? intval($data['id']) : 0;
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 0;

if ($serverId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
    exit();
}

try {
    // อัปเดตสถานะ is_active ในฐานข้อมูล
    $stmt = $conn->prepare("UPDATE servers SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $isActive, $serverId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'อัปเดตสถานะสำเร็จ',
            'id' => $serverId,
            'is_active' => $isActive
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตได้']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>