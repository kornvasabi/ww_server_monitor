<?php
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    // ถ้าเข้าสู่ระบบแล้ว ให้ไปหน้าแดชบอร์ด
    header('Location: dashboard.php');
    exit();
} else {
    // ถ้าไม่เข้าสู่ระบบ ให้ไปหน้า login
    header('Location: login.php');
    exit();
}
?>