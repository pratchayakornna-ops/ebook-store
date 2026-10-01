<?php
// เริ่มต้น Session และ Output Buffering เพื่อป้องกัน Headers Already Sent
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

// ป้องกัน PHP 8.1+ Fatal Error จาก MySQLi Exception
mysqli_report(MYSQLI_REPORT_OFF);

// ตรวจสอบสภาพแวดล้อมการรัน (Localhost XAMPP vs Online Web Hosting)
$is_localhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) || 
                in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) ||
                (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));

if ($is_localhost) {
    // 1. การตั้งค่าสำหรับรันบนเครื่อง Localhost (XAMPP)
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'ebook_store'; // หรือ 'if0_43025814_ebook_store'
} else {
    // 2. การตั้งค่าสำหรับรันบนเว็บโฮสติ้งจริง (InfinityFree / Hosting)
    // กรุณาตรวจสอบข้อมูลเหล่านี้จากหน้า cPanel > MySQL Details ของโฮสต์คุณ
    $host = 'sql308.infinityfree.com';
    $user = 'if0_43025814';
    $pass = 'ebookstore007x';
    $db   = 'if0_43025814_ebook_store';
}

// เชื่อมต่อฐานข้อมูลพร้อมกำหนด Timeout 5 วินาที (ป้องกัน Nginx 502 Bad Gateway)
$conn = mysqli_init();
if ($conn) {
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    // พยายามเชื่อมต่อฐานข้อมูล
    $connected = @$conn->real_connect($host, $user, $pass, $db);
    
    if (!$connected && $is_localhost) {
        // หากใน localhost ยังไม่มี db 'ebook_store' ให้ลองเชื่อมต่อกับ 'if0_43025814_ebook_store'
        $connected = @$conn->real_connect($host, $user, $pass, 'if0_43025814_ebook_store');
    }

    if (!$connected) {
        $db_error = mysqli_connect_error();
        // แสดงหน้าจอแนะนำวิธีแก้ไขอย่างสวยงาม แทนการปล่อยให้เกิด 502 Bad Gateway
        ?>
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>แจ้งเตือนการเชื่อมต่อฐานข้อมูล // NEXTREAD</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
            <div class="max-w-xl w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div class="text-center space-y-2">
                    <h1 class="text-xl font-bold text-white">ไม่สามารถเชื่อมต่อฐานข้อมูล MySQL ได้</h1>
                    <p class="text-xs text-slate-400">ระบบตรวจพบข้อผิดพลาดในการเชื่อมต่อกับ MySQL Server:</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-950 border border-rose-500/30 text-rose-300 text-xs font-mono break-all">
                    Error: <?php echo htmlspecialchars($db_error ?: 'Connection timed out or invalid credentials'); ?>
                </div>
                <div class="space-y-3 text-xs text-slate-300">
                    <p class="font-bold text-slate-200"><i class="fa-solid fa-screwdriver-wrench text-indigo-400 mr-1.5"></i> วิธีแก้ไขบน Hosting (InfinityFree cPanel):</p>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-400 leading-relaxed">
                        <li>เข้าสู่ระบบ <b>InfinityFree Control Panel</b> แล้วไปที่เมนู <b>MySQL Databases</b></li>
                        <li>ตรวจสอบค่า <b>MySQL Hostname</b> (เช่น <code class="text-amber-300">sql308.infinityfree.com</code> หรือ <code class="text-amber-300">sqlXXX.epizy.com</code>)</li>
                        <li>ตรวจสอบ <b>MySQL Username</b>, <b>Password</b> และ <b>Database Name</b> ให้ตรงกับในไฟล์ <code class="text-indigo-300">config.php</code></li>
                        <li>อย่าลืม Import ไฟล์ <code class="text-emerald-300">ebook_store_database.sql</code> เข้าไปใน phpMyAdmin บน Host</li>
                    </ol>
                </div>
                <div class="pt-2 text-center">
                    <a href="javascript:location.reload()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition shadow-lg shadow-indigo-600/30">
                        <i class="fa-solid fa-rotate-right"></i> ลองโหลดหน้าเว็บใหม่อีกครั้ง
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    } else {
        $conn->set_charset("utf8mb4");
    }
}

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