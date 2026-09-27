<?php
require_once 'config.php';

$ebook_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$book = null;

if ($ebook_id > 0 && isset($conn)) {
    $stmt = $conn->prepare("SELECT e.*, a.author_name, c.category_name 
                            FROM ebooks e 
                            LEFT JOIN authors a ON e.author_id = a.author_id 
                            LEFT JOIN categories c ON e.category_id = c.category_id 
                            WHERE e.ebook_id = ?");
    $stmt->bind_param("i", $ebook_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
    }
}

if (!$book) {
    header("Location: index.php");
    exit;
}

// หนังสือที่เกี่ยวข้องในหมวดหมู่เดียวกัน
$related_books = [];
if (!empty($book['category_id'])) {
    $rel_stmt = $conn->prepare("SELECT e.*, a.author_name, c.category_name 
                                FROM ebooks e 
                                LEFT JOIN authors a ON e.author_id = a.author_id 
                                LEFT JOIN categories c ON e.category_id = c.category_id 
                                WHERE e.category_id = ? AND e.ebook_id != ? AND e.is_active = 1 
                                LIMIT 4");
    $rel_stmt->bind_param("ii", $book['category_id'], $ebook_id);
    $rel_stmt->execute();
    $related_res = $rel_stmt->get_result();
    if ($related_res) {
        while ($r = $related_res->fetch_assoc()) {
            $related_books[] = $r;
        }
    }
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
$img_url = !empty($book['cover_image']) ? $book['cover_image'] : $default_cover;
$cart_count = getCartCount();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - NEXTREAD</title>
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
                <a href="cart.php" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition relative flex items-center justify-center border border-slate-700/50">
                    <i class="fa-solid fa-cart-shopping text-sm"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 px-1.5 py-0.5 min-w-[18px] bg-indigo-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="index.php" class="px-3.5 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-2 border border-slate-700/50">
                    <i class="fa-solid fa-arrow-left"></i> ย้อนกลับหน้าร้านค้า
                </a>
            </div>
        </nav>

        <!-- DETAIL CONTENT -->
        <main class="max-w-5xl mx-auto w-full px-4 py-10 flex-1 space-y-10">
            <div class="glass-card tilt-card rounded-3xl p-6 md:p-10 grid grid-cols-1 md:grid-cols-12 gap-8 items-start shadow-2xl">
                
                <!-- BOOK COVER (3D Hover Effect) -->
                <div class="md:col-span-5 flex justify-center">
                    <div class="w-full max-w-sm aspect-[3/4] rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 shadow-2xl relative book-cover-3d">
                        <img src="<?php echo htmlspecialchars($img_url); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             onerror="this.onerror=null; this.src='<?php echo $default_cover; ?>';"
                             class="w-full h-full object-cover">
                        <span class="absolute top-3 right-3 bg-slate-900/90 backdrop-blur-md text-indigo-300 text-xs font-bold px-3 py-1 rounded-lg border border-slate-700">
                            <?php echo htmlspecialchars($book['category_name'] ?? 'E-Book'); ?>
                        </span>
                    </div>
                </div>

                <!-- BOOK DETAILS -->
                <div class="md:col-span-7 space-y-6">
                    <div>
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 text-xs font-semibold rounded-full border border-indigo-500/30">
                            <i class="fa-solid fa-file-pdf mr-1"></i> E-Book (Digital File)
                        </span>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-white mt-3 mb-2 leading-tight">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </h1>
                        <p class="text-xs text-slate-400">
                            <i class="fa-solid fa-pen-nib text-indigo-400 mr-1.5"></i> ผู้แต่ง: 
                            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($book['author_name'] ?: 'ไม่ระบุผู้แต่ง'); ?></span>
                        </p>
                    </div>

                    <!-- PRICE BOX -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-between shadow-inner">
                        <div>
                            <span class="text-xs text-slate-500 block">ราคาจำหน่ายพิเศษ</span>
                            <span class="text-3xl font-black text-emerald-400">฿<?php echo number_format($book['price'], 2); ?></span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-indigo-300 font-medium block"><i class="fa-solid fa-bolt text-amber-400 mr-1"></i> อ่านได้ทันทีหลังอนุมัติ</span>
                            <span class="text-[11px] text-slate-500">รับสิทธิ์เปิดอ่านตลอดชีพ</span>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div>
                        <h3 class="text-sm font-semibold text-slate-200 mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-align-left text-indigo-400"></i> รายละเอียด / เรื่องย่อ
                        </h3>
                        <div class="text-xs text-slate-300 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-800/60 min-h-[100px]">
                            <?php echo nl2br(htmlspecialchars($book['description'] ?: 'ไม่มีข้อมูลรายละเอียดเพิ่มเติมสำหรับหนังสือเล่มนี้')); ?>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="pt-2 flex flex-col sm:flex-row gap-3">
                        <a href="cart.php?action=add&id=<?php echo $book['ebook_id']; ?>" 
                           class="flex-1 py-3.5 px-6 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs text-center transition border border-slate-700 flex items-center justify-center gap-2 hover:border-indigo-500/50">
                            <i class="fa-solid fa-cart-plus text-indigo-400"></i> เพิ่มลงตะกร้าสินค้า
                        </a>
                        <a href="cart.php?action=add&id=<?php echo $book['ebook_id']; ?>&checkout=1" 
                           class="flex-1 py-3.5 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs text-center transition shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-bolt"></i> ซื้อทันที (Buy Now)
                        </a>
                    </div>
                </div>

            </div>

            <!-- RELATED BOOKS -->
            <?php if (!empty($related_books)): ?>
                <div class="pt-4">
                    <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-bookmark text-indigo-400"></i> หนังสืออื่นๆ ในหมวดหมู่นี้
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach ($related_books as $rb): 
                            $rb_cover = !empty($rb['cover_image']) ? $rb['cover_image'] : $default_cover;
                        ?>
                            <a href="book_detail.php?id=<?php echo $rb['ebook_id']; ?>" class="glass-card interactive-card p-3 rounded-2xl hover:border-indigo-500/50 transition group block">
                                <div class="w-full aspect-[3/4] rounded-xl overflow-hidden bg-slate-900 mb-2 border border-slate-800 book-cover-3d">
                                    <img src="<?php echo htmlspecialchars($rb_cover); ?>" onerror="this.src='<?php echo $default_cover; ?>';" class="w-full h-full object-cover">
                                </div>
                                <h3 class="font-semibold text-white text-xs truncate mb-0.5"><?php echo htmlspecialchars($rb['title']); ?></h3>
                                <p class="text-[10px] text-slate-400 truncate mb-1">โดย <?php echo htmlspecialchars($rb['author_name'] ?? '-'); ?></p>
                                <span class="text-xs font-bold text-emerald-400">฿<?php echo number_format($rb['price'], 2); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500 mt-10">
            <p>© <?php echo date('Y'); ?> NEXTREAD E-Book Store. All rights reserved.</p>
        </footer>

    </div>

</body>
</html>