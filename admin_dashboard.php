<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ Admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$current_page = 'dashboard';

// 1. ดึงสถิติต่างๆ
$total_sales = 0;
try {
    $res_sales = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status = 'approved'");
    if ($res_sales) {
        $row = $res_sales->fetch_assoc();
        $total_sales = $row['total'] ?? 0;
    }
} catch (Exception $e) {}

// คำสั่งซื้อรออนุมัติ
$total_pending = 0;
try {
    $res_pending = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'");
    if ($res_pending) {
        $total_pending = $res_pending->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {}

// จำนวนหนังสือทั้งหมด
$total_books = 0;
try {
    $res_books = $conn->query("SELECT COUNT(*) AS total FROM ebooks");
    if ($res_books) {
        $total_books = $res_books->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {}

// จำนวนสมาชิก
$total_users = 0;
try {
    $res_users = $conn->query("SELECT COUNT(*) AS total FROM users");
    if ($res_users) {
        $total_users = $res_users->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {}

// 2. ดึงรายการสั่งซื้อล่าสุด 6 รายการ
$recent_orders = false;
try {
    $sql_recent = "SELECT o.*, u.name as customer_name, u.email as customer_email, p.payment_method 
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.user_id 
                   LEFT JOIN payments p ON o.order_id = p.order_id 
                   ORDER BY o.order_id DESC LIMIT 6";
    $recent_orders = $conn->query($sql_recent);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดภาพรวม // NEXTREAD Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <main class="page-container flex-1 p-4 md:p-8 overflow-y-auto space-y-8">
        
        <!-- Top Banner Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-chart-pie text-indigo-400"></i> ภาพรวมระบบ (Dashboard)
                </h1>
                <p class="text-xs text-slate-400 mt-1">ยินดีต้อนรับ ผู้ดูแลระบบ | สรุปข้อมูลยอดขายและสถิติล่าสุด</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-auto">
                <a href="add_book.php" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition inline-flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-plus"></i> เพิ่มหนังสือใหม่
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Total Sales -->
            <div class="glass-card interactive-card stagger-item stagger-1 p-5 rounded-3xl flex items-center gap-4 border-l-4 border-emerald-500 shadow-xl">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl shrink-0 border border-emerald-500/20 shadow-sm">
                    <i class="fa-solid fa-baht-sign"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-400">ยอดขายรวม (อนุมัติแล้ว)</p>
                    <h3 class="text-2xl font-black text-white mt-0.5">฿<?php echo number_format($total_sales, 2); ?></h3>
                </div>
            </div>

            <!-- 2. Pending Orders -->
            <div class="glass-card interactive-card stagger-item stagger-2 p-5 rounded-3xl flex items-center gap-4 border-l-4 border-amber-500 shadow-xl">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl shrink-0 border border-amber-500/20 shadow-sm">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-400">คำสั่งซื้อรอตรวจสอบ</p>
                    <h3 class="text-2xl font-black text-amber-400 mt-0.5"><?php echo number_format($total_pending); ?> รายการ</h3>
                </div>
            </div>

            <!-- 3. Total Books -->
            <div class="glass-card interactive-card stagger-item stagger-3 p-5 rounded-3xl flex items-center gap-4 border-l-4 border-indigo-500 shadow-xl">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl shrink-0 border border-indigo-500/20 shadow-sm">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-400">หนังสือในระบบทั้งหมด</p>
                    <h3 class="text-2xl font-black text-white mt-0.5"><?php echo number_format($total_books); ?> เล่ม</h3>
                </div>
            </div>

            <!-- 4. Total Users -->
            <div class="glass-card interactive-card stagger-item stagger-4 p-5 rounded-3xl flex items-center gap-4 border-l-4 border-sky-500 shadow-xl">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-400 flex items-center justify-center text-xl shrink-0 border border-sky-500/20 shadow-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-400">สมาชิกในระบบ</p>
                    <h3 class="text-2xl font-black text-white mt-0.5"><?php echo number_format($total_users); ?> คน</h3>
                </div>
            </div>
        </div>

        <!-- รายการสั่งซื้อล่าสุด -->
        <div class="glass-card rounded-3xl p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-indigo-400"></i> รายการสั่งซื้อล่าสุด
                </h2>
                <a href="admin_orders.php" class="text-xs text-indigo-400 hover:text-indigo-300 transition flex items-center gap-1">
                    จัดการคำสั่งซื้อทั้งหมด <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4">รหัสสั่งซื้อ</th>
                            <th class="py-3 px-4">ลูกค้า</th>
                            <th class="py-3 px-4">ยอดชำระ</th>
                            <th class="py-3 px-4">สถานะ</th>
                            <th class="py-3 px-4 text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                            <?php while ($order = $recent_orders->fetch_assoc()): 
                                $price = $order['total_amount'] ?? 0;
                                $cust = $order['customer_name'] ?? 'User #'.$order['user_id'];
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3.5 px-4 font-semibold text-slate-200">
                                        #ORD-<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-medium text-white block"><?php echo htmlspecialchars($cust); ?></span>
                                        <span class="text-[10px] text-slate-500"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-emerald-400 font-bold text-sm">
                                        ฿<?php echo number_format($price, 2); ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-medium">รอตรวจสอบ</span>
                                        <?php elseif ($order['status'] == 'approved'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-medium">อนุมัติแล้ว</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-medium">ปฏิเสธ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <a href="admin_orders.php" class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white transition text-xs inline-flex items-center gap-1 shadow-sm">
                                            <i class="fa-solid fa-eye"></i> ตรวจสอบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">ยังไม่มีรายการสั่งซื้อในระบบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>