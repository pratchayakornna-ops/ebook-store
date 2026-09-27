<?php
require_once 'config.php';

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$purchased_books = [];

// ดึงรายการหนังสือที่สั่งซื้อและได้รับการอนุมัติแล้ว
if (isset($conn)) {
    try {
        $sql = "SELECT DISTINCT e.*, a.author_name, c.category_name, MAX(o.order_date) as purchase_date 
                FROM ebooks e 
                JOIN order_items oi ON e.ebook_id = oi.ebook_id 
                JOIN orders o ON oi.order_id = o.order_id 
                LEFT JOIN authors a ON e.author_id = a.author_id 
                LEFT JOIN categories c ON e.category_id = c.category_id 
                WHERE o.user_id = ? AND o.status = 'approved'
                GROUP BY e.ebook_id
                ORDER BY purchase_date DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $purchased_books = $stmt->get_result();
        }
    } catch (Exception $e) {}
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หนังสือของฉัน - NEXTREAD Shelf</title>
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
                <a href="orders.php" class="px-3 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-1.5 border border-slate-700/50">
                    <i class="fa-solid fa-clock-rotate-left"></i> <span>ประวัติสั่งซื้อ</span>
                </a>
                <a href="index.php" class="px-3 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-1.5 border border-slate-700/50">
                    <i class="fa-solid fa-store"></i> <span>หน้าร้านค้า</span>
                </a>
                <a href="profile.php" class="px-3.5 py-2 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/30 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-user"></i> <span class="hidden sm:inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'บัญชี'); ?></span>
                </a>
            </div>
        </nav>

        <!-- CONTENT -->
        <main class="max-w-7xl mx-auto w-full px-4 md:px-8 py-8 flex-1">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-book-bookmark text-indigo-400"></i> ชั้นหนังสือของฉัน (My Library)
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">E-Book ทั้งหมดที่คุณเป็นเจ้าของ สามารถคลิกเพื่อเปิดอ่านได้ทันที</p>
                </div>
                <a href="index.php" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition inline-flex items-center gap-2 shadow-lg shadow-indigo-600/30 self-start sm:self-auto">
                    <i class="fa-solid fa-plus"></i> เลือกซื้อหนังสือเพิ่ม
                </a>
            </div>

            <?php if ($purchased_books && $purchased_books->num_rows > 0): 
                $b_idx = 0;
            ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                    <?php while ($b = $purchased_books->fetch_assoc()): 
                        $b_idx++;
                        $img = !empty($b['cover_image']) ? $b['cover_image'] : $default_cover;
                    ?>
                        <div class="glass-card interactive-card stagger-item stagger-<?php echo min($b_idx, 8); ?> rounded-2xl p-3.5 flex flex-col justify-between group">
                            <div>
                                <div class="w-full aspect-[3/4] rounded-xl overflow-hidden bg-slate-900 mb-3 border border-slate-800 relative book-cover-3d">
                                    <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.src='<?php echo $default_cover; ?>';" class="w-full h-full object-cover">
                                    <span class="absolute top-2 right-2 bg-emerald-500/90 backdrop-blur-md text-white text-[9px] font-bold px-2 py-0.5 rounded-md shadow">
                                        <i class="fa-solid fa-check"></i> เป็นเจ้าของ
                                    </span>
                                </div>
                                <h3 class="font-semibold text-slate-100 text-xs line-clamp-1 mb-1" title="<?php echo htmlspecialchars($b['title']); ?>">
                                    <?php echo htmlspecialchars($b['title']); ?>
                                </h3>
                                <p class="text-[11px] text-slate-400 mb-3 line-clamp-1">โดย <?php echo htmlspecialchars($b['author_name'] ?: 'ไม่ระบุผู้แต่ง'); ?></p>
                            </div>
                            
                            <div class="space-y-1.5 pt-2 border-t border-slate-800">
                                <a href="read.php?id=<?php echo $b['ebook_id']; ?>" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold text-center transition flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-600/30">
                                    <i class="fa-solid fa-book-open"></i> เปิดอ่าน E-Book
                                </a>
                                <?php if (!empty($b['file_link']) || !empty($b['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($b['file_link'] ?: $b['file_path']); ?>" target="_blank" class="w-full py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-[11px] font-medium text-center transition flex items-center justify-center gap-1.5 border border-slate-700">
                                        <i class="fa-solid fa-download"></i> ดาวน์โหลด PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="glass-card rounded-3xl p-12 text-center text-slate-500 max-w-xl mx-auto my-10 shadow-2xl">
                    <div class="w-20 h-20 rounded-3xl bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                        <i class="fa-solid fa-book-open-reader text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-200 mb-1">ยังไม่มีหนังสือในชั้นของคุณ</h3>
                    <p class="text-xs text-slate-400 mb-6 max-w-md mx-auto">
                        เมื่อคุณสั่งซื้อ E-Book และแอดมินทำการอนุมัติเรียบร้อยแล้ว หนังสือดิจิทัลทั้งหมดจะเข้ามาอยู่ในหน้านี้เพื่อให้คุณเปิดอ่านได้ทันที
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="index.php" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition inline-flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                            <i class="fa-solid fa-cart-shopping"></i> ไปเลือกซื้อหนังสือ
                        </a>
                        <a href="orders.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition inline-flex items-center gap-2 border border-slate-700">
                            <i class="fa-solid fa-clock-rotate-left"></i> เช็กสถานะคำสั่งซื้อ
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
            <p>© <?php echo date('Y'); ?> NEXTREAD E-Book Store. All rights reserved.</p>
        </footer>

    </div>

</body>
</html>