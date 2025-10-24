<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

// ฟังก์ชันตรวจสอบด้วย cURL (วิธีที่ดีที่สุด)
function checkServerStatusCurl($address, $timeout = 5) {
    if (empty($address)) {
        return 'Offline';
    }

    // เพิ่ม protocol ถ้าไม่มี
    if (!preg_match('~^https?://~i', $address)) {
        $address = 'http://' . $address;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $address,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_FAILONERROR => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Server-Monitor/1.0',
    ]);

    try {
        @curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
        curl_close($ch);

        // ถ้า HTTP code 200-399 ถือว่า Online
        if ($httpCode >= 200 && $httpCode < 400) {
            return 'Online';
        }
        // ถ้า HTTP code 401, 403, 500+ ถือว่า Online (เซิร์ฟเวอร์ตอบสนอง)
        elseif ($httpCode > 0) {
            return 'Online';
        }
        // ถ้า curl error ถือว่า Offline
        else {
            return 'Offline';
        }
    } catch (Exception $e) {
        if (is_resource($ch)) {
            curl_close($ch);
        }
        return 'Offline';
    }
}

// ฟังก์ชันตรวจสอบด้วย fsockopen (ทางเลือก)
function checkServerStatusSocket($address, $timeout = 5) {
    if (empty($address)) {
        return 'Offline';
    }

    // ถ้าเป็น IP address
    if (filter_var($address, FILTER_VALIDATE_IP)) {
        $host = $address;
        $port = 80;
    } else {
        // ถ้าเป็น URL/Domain
        $parsed = parse_url($address);
        
        if (!isset($parsed['host'])) {
            $address = 'http://' . $address;
            $parsed = parse_url($address);
        }
        
        $host = $parsed['host'] ?? $address;
        $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);
    }

    try {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return 'Online';
        }
    } catch (Exception $e) {
        // ข้อผิดพลาดในการเชื่อมต่อ
    }

    return 'Offline';
}

// ดึงข้อมูลเซิร์ฟเวอร์ทั้งหมด
header('Content-Type: application/json; charset=utf-8');

try {
    // ตรวจสอบการเชื่อมต่อ Database
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    $query = "SELECT id, name, address FROM servers ORDER BY id DESC";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Database query error: ' . $conn->error);
    }

    $servers = [];

    while ($row = $result->fetch_assoc()) {
        $address = trim($row['address']);
        
        // เลือกวิธีตรวจสอบตามว่า curl มีหรือไม่
        if (extension_loaded('curl')) {
            $status = checkServerStatusCurl($address);
        } else {
            // Fallback to fsockopen ถ้า curl ไม่มี
            $status = checkServerStatusSocket($address);
        }

        $servers[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'status' => $status
        ];
    }

    // print_r($servers); exit;
    
    // ถ้าไม่มีเซิร์ฟเวอร์ คืน array ว่าง
    if (empty($servers)) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(200);
        echo json_encode($servers, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>