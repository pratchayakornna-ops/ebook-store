<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'sql308.infinityfree.com';
$user = 'if0_43025814';
$pass = 'ebookstore007x';
$db   = 'if0_43025814_ebook_store';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ตรวจสอบการเข้าสู่ระบบ
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ตรวจสอบสิทธิ์ Admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// นับจำนวนสินค้าในตะกร้า
function getCartCount() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return count($_SESSION['cart']);
    }
    return 0;
}
?>