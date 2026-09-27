<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น หรือเข้าสู่ระบบ';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt_ins->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt_ins->execute()) {
                $success = 'สมัครสมาชิกสำเร็จเรียบร้อย! กำลังพาคุณไปยังหน้าเข้าสู่ระบบ...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก // NEXTREAD Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <div class="page-container glass-card max-w-md w-full rounded-3xl p-8 shadow-2xl space-y-6">
        <div class="text-center">
            <a href="index.php" class="inline-flex items-center gap-2 mb-2 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-500/30 group-hover:scale-105 group-hover:rotate-6 transition duration-300">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </a>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">สมัครสมาชิกใหม่</h1>
            <p class="text-slate-400 text-xs mt-1">สร้างบัญชี NEXTREAD เพื่อเข้าถึงคลัง E-Book ของคุณ</p>
        </div>

        <?php if ($error): ?>
            <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base shrink-0"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base shrink-0"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 text-xs">
            <div>
                <label class="block font-medium text-slate-300 mb-1.5">ชื่อ - นามสกุล</label>
                <input type="text" name="name" required placeholder="สมชาย ใจดี" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <div>
                <label class="block font-medium text-slate-300 mb-1.5">อีเมล</label>
                <input type="email" name="email" required placeholder="name@example.com" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <div>
                <label class="block font-medium text-slate-300 mb-1.5">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••••" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <div>
                <label class="block font-medium text-slate-300 mb-1.5">ยืนยันรหัสผ่านอีกครั้ง</label>
                <input type="password" name="confirm_password" required minlength="6" placeholder="••••••••" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition mt-2">
                ยืนยันการสมัครสมาชิก
            </button>
        </form>

        <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
            มีบัญชีอยู่แล้ว? <a href="login.php" class="text-indigo-400 hover:underline font-semibold">เข้าสู่ระบบที่นี่</a>
        </div>
        <div class="text-center text-xs text-slate-500">
            <a href="index.php" class="hover:text-slate-300 transition"><i class="fa-solid fa-arrow-left"></i> กลับหน้าร้านค้า</a>
        </div>
    </div>

</body>
</html>