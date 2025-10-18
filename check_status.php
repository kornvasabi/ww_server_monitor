<?php
header('Content-Type: application/json'); // บอกให้ browser รู้ว่านี่คือไฟล์ JSON
require_once 'config.php';

// ตรวจสอบว่าล็อกอินอยู่หรือไม่ (เพื่อความปลอดภัย)
if (!isLoggedIn()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

/**
 * ฟังก์ชันสำหรับเช็คสถานะเซิร์ฟเวอร์
 * (คัดลอกมาจาก dashboard.php เพื่อให้ไฟล์นี้ทำงานได้ด้วยตัวเอง)
 */
function checkServerStatus($address) {
    if (filter_var($address, FILTER_VALIDATE_URL)) {
        $ch = curl_init($address);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($http_code >= 200 && $http_code < 400) ? 'Online' : 'Offline';
    } else {
        $fp = @fsockopen($address, 80, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return 'Online';
        } else {
            return 'Offline';
        }
    }
}

$servers_status = [];
$query = "SELECT id, address FROM servers";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($server = mysqli_fetch_assoc($result)) {
        $status = checkServerStatus($server['address']);
        $servers_status[] = [
            'id' => $server['id'],
            'status' => $status
        ];
    }
}

// ส่งผลลัพธ์กลับเป็น JSON
echo json_encode($servers_status);

mysqli_close($conn);
?>