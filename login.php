<?php
require_once 'config.php';

$error = '';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirect = trim($_POST['redirect'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name']    = $user['name'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $user['role'];

                    if (!empty($redirect) && !str_starts_with($redirect, 'http')) {
                        header("Location: " . $redirect);
                    } elseif ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
                }
            } else {
                $error = 'ไม่พบบัญชีผู้ใช้งานอีเมลนี้ในระบบ';
            }
            $stmt->close();
        } else {
            $error = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        }
    } else {
        $error = 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ // NEXTREAD Store</title>
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
            <a href="index.php" class="inline-flex items-center gap-3 mb-3 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-500/30 group-hover:scale-105 group-hover:rotate-6 transition duration-300">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </a>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">เข้าสู่ระบบ NEXTREAD</h1>
            <p class="text-slate-400 text-xs mt-1">เข้าถึงคลังหนังสือดิจิทัลและรายการสั่งซื้อของคุณ</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base shrink-0"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

            <div>
                <label class="block font-medium text-slate-300 mb-1.5">อีเมลผู้ใช้งาน</label>
                <input type="email" name="email" required placeholder="name@example.com" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-3 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <div>
                <label class="block font-medium text-slate-300 mb-1.5">รหัสผ่าน</label>
                <input type="password" name="password" required placeholder="••••••••" 
                       class="w-full bg-slate-900/90 text-white placeholder-slate-500 px-4 py-3 rounded-xl border border-slate-700 focus:outline-none focus:border-indigo-500 text-xs transition">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition mt-2">
                เข้าสู่ระบบ
            </button>
        </form>

        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 space-y-1">
            <p class="font-semibold text-slate-300">🔑 ข้อมูลผู้ดูแลระบบ (Admin Demo):</p>
            <p>อีเมล: <span class="text-indigo-400 font-mono">admin@nextread.com</span></p>
            <p>รหัสผ่าน: <span class="text-indigo-400 font-mono">admin123</span></p>
        </div>

        <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
            ยังไม่มีบัญชีสมาชิก? <a href="register.php" class="text-indigo-400 hover:underline font-semibold">สมัครสมาชิกที่นี่</a>
        </div>
        <div class="text-center text-xs text-slate-500">
            <a href="index.php" class="hover:text-slate-300 transition"><i class="fa-solid fa-arrow-left"></i> กลับหน้าร้านค้าหลัก</a>
        </div>
    </div>

</body>
</html>