<?php
require_once 'config.php';

$email = 'admin@nextread.com';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'Admin NEXTREAD';
$role = 'admin';

// ตรวจสอบว่ามีอีเมลนี้อยู่แล้วหรือไม่
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // อัปเดตรหัสผ่านและ role
    $update = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
    $update->bind_param("ss", $hashed_password, $email);
    $update->execute();
    echo "<h2 style='color:green;'>รีเซ็ตรหัสผ่าน Admin เรียบร้อยแล้ว!</h2>";
} else {
    // เพิ่มบัญชีใหม่
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssss", $name, $email, $hashed_password, $role);
    $insert->execute();
    echo "<h2 style='color:green;'>สร้างบัญชี Admin เรียบร้อยแล้ว!</h2>";
}

echo "<p><b>อีเมล:</b> admin@nextread.com<br><b>รหัสผ่าน:</b> admin123</p>";
echo "<a href='login.php'>คลิกที่นี่เพื่อไปหน้าเข้าสู่ระบบ</a>";
?>  