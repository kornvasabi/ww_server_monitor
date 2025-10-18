<?php
// เริ่มต้น session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
define('DB_HOST', 'localhost'); // หรือ IP ของ MariaDB Server
define('DB_USER', 'root');      // ชื่อผู้ใช้ฐานข้อมูล
define('DB_PASS', 'p@ssword');          // รหัสผ่าน
define('DB_NAME', 'server_monitor');
define('DB_PORT', 3307); // *** เพิ่มการตั้งค่า PORT ที่นี่ *** (ค่าเริ่มต้นคือ 3306)

// เชื่อมต่อฐานข้อมูล
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตั้งค่า Character Set
mysqli_set_charset($conn, "utf8mb4");

// ฟังก์ชันสำหรับตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>