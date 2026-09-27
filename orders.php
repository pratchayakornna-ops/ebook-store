<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการสั่งซื้อของผู้ใช้
$sql = "SELECT o.*, p.payment_method, p.slip_url 
        FROM orders o 
        LEFT JOIN payments p ON o.order_id = p.order_id 
        WHERE o.user_id = ? 
        ORDER BY o.order_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$orders_list = [];
if ($orders_result) {
    while ($ord = $orders_result->fetch_assoc()) {
        $items_sql = "SELECT oi.*, e.title, e.cover_image, e.price 
                      FROM order_items oi 
                      JOIN ebooks e ON oi.ebook_id = e.ebook_id 
                      WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($items_sql);
        $stmt_items->bind_param("i", $ord['order_id']);
        $stmt_items->execute();
        $items_res = $stmt_items->get_result();
        
        $items = [];
        if ($items_res) {
            while ($it = $items_res->fetch_assoc()) {
                $items[] = $it;
            }
        }
        $ord['items'] = $items;
        $orders_list[] = $ord;
    }
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติและสถานะคำสั่งซื้อ // NEXTREAD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col antialiased">

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
                    <i class="fa-solid fa-book-bookmark"></i> <span>ชั้นหนังสือของฉัน</span>
                </a>
                <a href="index.php" class="px-3.5 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-1.5 border border-slate-700/50">
                    <i class="fa-solid fa-store"></i> <span>ร้านค้า</span>
                </a>
            </div>
        </nav>

        <main class="max-w-4xl mx-auto w-full px-4 md:px-8 py-10 flex-1">
            <!-- HEADER -->
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-400"></i> ประวัติและสถานะคำสั่งซื้อ
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">ติดตามสถานะการชำระเงินและการอนุมัติ E-Book ของคุณ</p>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-4 rounded-3xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-3 shadow-xl">
                    <i class="fa-solid fa-circle-check text-2xl shrink-0"></i>
                    <div>
                        <strong class="block font-bold text-sm">บันทึกคำสั่งซื้อเรียบร้อยแล้ว!</strong>
                        <span>ระบบได้รับคำสั่งซื้อของคุณแล้ว แอดมินจะทำการตรวจสอบและอนุมัติรายการโดยเร็วที่สุด</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ORDERS LIST -->
            <?php if (!empty($orders_list)): ?>
                <div class="space-y-6">
                    <?php 
                    $ord_idx = 0;
                    foreach ($orders_list as $ord): 
                        $ord_idx++;
                        $order_id = $ord['order_id'];
                        $status = $ord['status'] ?? 'pending';
                        $total = $ord['total_amount'] ?? 0;
                        $date = $ord['order_date'] ?? '-';
                    ?>
                        <div class="glass-card interactive-card stagger-item stagger-<?php echo min($ord_idx, 8); ?> rounded-3xl p-6 border border-slate-800 hover:border-slate-700 transition shadow-xl space-y-4">
                            <!-- ORDER HEADER -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                                <div>
                                    <span class="font-bold text-white text-sm">รหัสคำสั่งซื้อ: #ORD-<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?></span>
                                    <span class="text-xs text-slate-400 block mt-0.5"><i class="fa-regular fa-clock mr-1"></i> วันที่สั่งซื้อ: <?php echo date('d/m/Y H:i', strtotime($date)); ?></span>
                                </div>
                                <div>
                                    <?php if ($status === 'approved'): ?>
                                        <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs border border-emerald-500/30 font-semibold inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-circle-check"></i> อนุมัติแล้ว (พร้อมอ่าน)
                                        </span>
                                    <?php elseif ($status === 'rejected'): ?>
                                        <span class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-400 text-xs border border-rose-500/30 font-semibold inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-circle-xmark"></i> รายการถูกปฏิเสธ
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 text-xs border border-amber-500/30 font-semibold inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-hourglass-half animate-spin"></i> รอการตรวจสอบจากแอดมิน
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ITEMS IN ORDER -->
                            <div class="space-y-3">
                                <?php if (!empty($ord['items'])): ?>
                                    <?php foreach ($ord['items'] as $item): 
                                        $cover = !empty($item['cover_image']) ? $item['cover_image'] : $default_cover;
                                    ?>
                                        <div class="flex items-center justify-between gap-3 text-xs bg-slate-900/60 p-3.5 rounded-2xl border border-slate-800/60">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="<?php echo htmlspecialchars($cover); ?>" onerror="this.src='<?php echo $default_cover; ?>';" class="w-10 h-14 object-cover rounded-lg border border-slate-700 shrink-0">
                                                <div class="min-w-0">
                                                    <h4 class="font-semibold text-white truncate"><?php echo htmlspecialchars($item['title']); ?></h4>
                                                    <span class="text-slate-400 text-[11px]">฿<?php echo number_format($item['price_at_purchase'], 2); ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($status === 'approved'): ?>
                                                <a href="read.php?id=<?php echo $item['ebook_id']; ?>" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[11px] transition shrink-0 inline-flex items-center gap-1.5 shadow-md shadow-emerald-600/30">
                                                    <i class="fa-solid fa-book-open"></i> เปิดอ่าน
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-xs text-slate-500">ไม่มีรายการสินค้าที่บันทึกไว้</p>
                                <?php endif; ?>
                            </div>

                            <!-- ORDER FOOTER -->
                            <div class="pt-3 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="text-slate-400">
                                    <span>ช่องทางชำระ: <strong class="text-slate-200"><?php echo htmlspecialchars($ord['payment_method'] ?? 'PromptPay'); ?></strong></span>
                                    <?php if (!empty($ord['slip_url'])): ?>
                                        <span class="ml-2 text-indigo-400 font-medium">✓ แนบสลิปแล้ว</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-slate-400">ยอดชำระ: <strong class="text-emerald-400 text-base font-black">฿<?php echo number_format($total, 2); ?></strong></span>
                                    <?php if ($status === 'approved'): ?>
                                        <a href="my_books.php" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-md shadow-indigo-600/30">
                                            <i class="fa-solid fa-book-bookmark"></i> ดูในชั้นหนังสือ
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass-card rounded-3xl p-12 text-center text-slate-400 max-w-lg mx-auto shadow-2xl">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                        <i class="fa-solid fa-box-open text-3xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-200">ยังไม่มีประวัติการสั่งซื้อ</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-6">เมื่อคุณสั่งซื้อ E-Book รายการจะปรากฏที่นี่เพื่อติดตามสถานะ</p>
                    <a href="index.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-lg shadow-indigo-600/30">
                        <i class="fa-solid fa-store"></i> ไปเลือกซื้อหนังสือ
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500 mt-10">
            <p>© <?php echo date('Y'); ?> NEXTREAD E-Book Store. All rights reserved.</p>
        </footer>

    </div>

</body>
</html>