<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$stmt_u = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// อัปเดตข้อมูลผู้ใช้ (ชื่อ / เปลี่ยนรหัสผ่าน)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    if (!empty($name)) {
        if (!empty($new_password)) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $up_stmt = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE user_id = ?");
                $up_stmt->bind_param("ssi", $name, $hashed, $user_id);
                $up_stmt->execute();
                $_SESSION['name'] = $name;
                $msg = "อัปเดตชื่อและรหัสผ่านใหม่เรียบร้อยแล้ว";
            } else {
                $msg = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
                $msg_type = 'danger';
            }
        } else {
            $up_stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $up_stmt->bind_param("si", $name, $user_id);
            $up_stmt->execute();
            $_SESSION['name'] = $name;
            $msg = "อัปเดตข้อมูลโปรไฟล์เรียบร้อยแล้ว";
        }
        $user['name'] = $name;
    }
}

// ดึงสถิติจำนวนหนังสือที่ซื้อ และจำนวนคำสั่งซื้อ
$purchased_count = 0;
$stmt_p = $conn->prepare("SELECT COUNT(DISTINCT oi.ebook_id) as total FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = ? AND o.status = 'approved'");
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$purchased_count = $stmt_p->get_result()->fetch_assoc()['total'] ?? 0;

$orders_count = 0;
$stmt_o = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt_o->bind_param("i", $user_id);
$stmt_o->execute();
$orders_count = $stmt_o->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีของฉัน // NEXTREAD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <div class="page-container flex flex-col min-h-screen">

        <!-- NAVBAR -->
        <nav class="glass-nav sticky top-0 z-50 px-4 md:px-8 py-3.5 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                    <i class="fa-solid fa-book-open text-lg"></i>
                </div>
                <div>
                    <span class="font-bold text-lg text-white tracking-wide block leading-none">NEXTREAD</span>
                    <span class="text-[10px] text-indigo-400 font-medium">E-BOOK STORE</span>
                </div>
            </a>
            <div class="flex items-center gap-3 text-xs">
                <a href="my_books.php" class="px-3.5 py-2 rounded-xl bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-600/30 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-book-bookmark"></i> <span>ชั้นหนังสือ</span>
                </a>
                <a href="index.php" class="px-3.5 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-1.5 border border-slate-700/50">
                    <i class="fa-solid fa-store"></i> <span>หน้าร้านค้า</span>
                </a>
            </div>
        </nav>

        <!-- PROFILE CONTENT -->
        <main class="max-w-4xl mx-auto w-full px-4 py-10 flex-1 space-y-6">
            
            <?php if ($msg): ?>
                <div class="p-4 rounded-2xl text-xs flex items-center gap-2 border shadow-lg <?php echo $msg_type == 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400'; ?>">
                    <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> text-base"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <div class="glass-card tilt-card rounded-3xl p-6 md:p-8 border border-slate-800 shadow-2xl space-y-8">
                
                <!-- USER HEADER -->
                <div class="flex flex-col md:flex-row items-center gap-6 pb-6 border-b border-slate-800/80">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-3xl font-bold shadow-xl shadow-indigo-600/30 shrink-0">
                        <?php echo mb_substr(strtoupper($user['name']), 0, 1, 'UTF-8'); ?>
                    </div>
                    <div class="text-center md:text-left flex-1 space-y-1">
                        <div class="flex items-center justify-center md:justify-start gap-2.5">
                            <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo ($user['role'] === 'admin') ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'; ?> uppercase">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400"><i class="fa-regular fa-envelope mr-1.5"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-[11px] text-slate-500"><i class="fa-regular fa-calendar mr-1.5"></i> สมาชิกตั้งแต่: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <?php if (isAdmin()): ?>
                            <a href="admin_dashboard.php" class="px-3.5 py-2 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30 rounded-xl text-xs font-semibold transition inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" onclick="return confirm('ยืนยันออกจากระบบ?')" class="px-3.5 py-2 bg-rose-600/10 text-rose-400 hover:bg-rose-600/20 border border-rose-500/20 rounded-xl text-xs font-semibold transition inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="my_books.php" class="glass-card interactive-card p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-indigo-500/40 transition block group shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-400">E-Book ที่เป็นเจ้าของ</span>
                            <i class="fa-solid fa-book-bookmark text-indigo-400 text-lg group-hover:scale-110 transition"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white"><?php echo $purchased_count; ?> <span class="text-xs font-normal text-slate-400">เล่ม</span></h3>
                        <span class="text-[11px] text-indigo-400 mt-2 inline-flex items-center gap-1">เปิดอ่านในชั้นหนังสือ →</span>
                    </a>

                    <a href="orders.php" class="glass-card interactive-card p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-emerald-500/40 transition block group shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-400">รายการสั่งซื้อทั้งหมด</span>
                            <i class="fa-solid fa-clock-rotate-left text-emerald-400 text-lg group-hover:scale-110 transition"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white"><?php echo $orders_count; ?> <span class="text-xs font-normal text-slate-400">คำสั่งซื้อ</span></h3>
                        <span class="text-[11px] text-emerald-400 mt-2 inline-flex items-center gap-1">ดูประวัติสั่งซื้อ →</span>
                    </a>
                </div>

                <!-- EDIT PROFILE FORM -->
                <div class="pt-4 border-t border-slate-800">
                    <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-gear text-indigo-400"></i> แก้ไขข้อมูลส่วนตัว
                    </h3>

                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block text-slate-300 font-medium mb-1.5">ชื่อ - นามสกุล</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block text-slate-300 font-medium mb-1.5">เปลี่ยนรหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                            <input type="password" name="new_password" minlength="6" placeholder="รหัสผ่านใหม่ อย่างน้อย 6 ตัวอักษร" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs placeholder-slate-500">
                        </div>

                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs transition shadow-lg shadow-indigo-600/30">
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>

        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500 mt-10">
            <p>© <?php echo date('Y'); ?> NEXTREAD E-Book Store. All rights reserved.</p>
        </footer>

    </div>

</body>
</html>