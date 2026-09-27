<?php
require_once 'config.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// จัดการการเพิ่ม/ลบสินค้าในตะกร้า
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $ebook_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $go_checkout = isset($_GET['checkout']) && $_GET['checkout'] == '1';

    if ($action === 'add' && $ebook_id > 0) {
        $_SESSION['cart'][$ebook_id] = 1;
        
        if ($go_checkout) {
            header("Location: checkout.php");
            exit();
        }
        header("Location: cart.php");
        exit();
    }

    if ($action === 'remove' && $ebook_id > 0) {
        unset($_SESSION['cart'][$ebook_id]);
        header("Location: cart.php");
        exit();
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header("Location: cart.php");
        exit();
    }
}

// ดึงข้อมูลหนังสือที่อยู่ในตะกร้า
$cart_books = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $cart_ids = array_map('intval', array_keys($_SESSION['cart']));
    if (!empty($cart_ids)) {
        $ids_string = implode(',', $cart_ids);
        $sql = "SELECT e.*, a.author_name, c.category_name 
                FROM ebooks e
                LEFT JOIN authors a ON e.author_id = a.author_id
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.ebook_id IN ($ids_string)";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cart_books[] = $row;
                $total_price += $row['price'];
            }
        }
    }
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า // NEXTREAD</title>
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
            
            <a href="index.php" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition flex items-center gap-2 border border-slate-700/50">
                <i class="fa-solid fa-arrow-left text-indigo-400"></i> เลือกซื้อหนังสือเพิ่ม
            </a>
        </nav>

        <main class="max-w-5xl mx-auto px-4 md:px-8 py-10 w-full flex-grow">
            <!-- HEADER -->
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-cart-shopping text-indigo-400"></i> ตะกร้าสินค้าของคุณ
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">มีสินค้าทั้งหมด <?php echo count($cart_books); ?> รายการในตะกร้า</p>
                </div>

                <?php if (!empty($cart_books)): ?>
                    <a href="cart.php?action=clear" onclick="return confirm('ต้องการล้างตะกร้าสินค้าทั้งหมดใช่หรือไม่?')" 
                       class="text-xs text-rose-400 hover:text-rose-300 transition flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/10 border border-rose-500/20">
                        <i class="fa-regular fa-trash-can"></i> ล้างตะกร้าทั้งหมด
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($cart_books)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- รายการหนังสือในตะกร้า -->
                    <div class="lg:col-span-2 space-y-4">
                        <?php 
                        $item_idx = 0;
                        foreach ($cart_books as $book): 
                            $item_idx++;
                            $img = !empty($book['cover_image']) ? $book['cover_image'] : $default_cover;
                        ?>
                            <div class="glass-card interactive-card stagger-item stagger-<?php echo min($item_idx, 8); ?> p-4 rounded-2xl flex items-center gap-4 border border-slate-800 hover:border-slate-700 transition">
                                <div class="w-16 h-22 aspect-[3/4] rounded-xl overflow-hidden bg-slate-900 border border-slate-700 shrink-0 book-cover-3d">
                                    <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.src='<?php echo $default_cover; ?>';" class="w-full h-full object-cover">
                                </div>
                                
                                <div class="flex-grow min-w-0">
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        <?php echo htmlspecialchars($book['category_name'] ?? 'ทั่วไป'); ?>
                                    </span>
                                    <h3 class="font-bold text-white text-sm mt-1.5 truncate">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">โดย <?php echo htmlspecialchars($book['author_name'] ?? 'ไม่ระบุผู้แต่ง'); ?></p>
                                    <div class="text-emerald-400 font-bold text-sm mt-1">฿<?php echo number_format($book['price'], 2); ?></div>
                                </div>

                                <a href="cart.php?action=remove&id=<?php echo $book['ebook_id']; ?>" 
                                   class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 border border-slate-700 transition"
                                   title="ลบรายการนี้">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- สรุปยอดเงิน & ชำระเงิน -->
                    <div class="lg:col-span-1">
                        <div class="glass-card tilt-card p-6 rounded-3xl border border-slate-800 sticky top-24 space-y-5 shadow-2xl">
                            <h2 class="font-bold text-base text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                                <i class="fa-solid fa-receipt text-indigo-400"></i> สรุปคำสั่งซื้อ
                            </h2>
                            
                            <div class="space-y-2.5 text-xs text-slate-300">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">จำนวนสินค้า</span>
                                    <span class="font-semibold text-white"><?php echo count($cart_books); ?> เล่ม</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">รูปแบบสินค้า</span>
                                    <span class="text-indigo-400 font-medium">E-Book (ไฟล์ดิจิทัล)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">ค่าจัดส่ง</span>
                                    <span class="text-emerald-400 font-semibold">ฟรี (ออนไลน์)</span>
                                </div>
                            </div>

                            <div class="border-t border-slate-800 pt-4 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-slate-200">ยอดชำระทั้งหมด</span>
                                <span class="text-2xl font-black text-emerald-400">฿<?php echo number_format($total_price, 2); ?></span>
                            </div>

                            <a href="checkout.php" 
                               class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl text-center block shadow-lg shadow-indigo-500/30 transition text-xs">
                                ดำเนินการชำระเงิน <i class="fa-solid fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                </div>
            <?php else: ?>
                <div class="glass-card p-12 text-center rounded-3xl max-w-lg mx-auto my-8 shadow-2xl">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                        <i class="fa-solid fa-cart-shopping text-3xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-200">ไม่มีสินค้าในตะกร้าของคุณ</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-6">เลือกหนังสือที่คุณสนใจ แล้วเพิ่มลงในตะกร้าเพื่อดำเนินการสั่งซื้อ</p>
                    <a href="index.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-lg shadow-indigo-600/30">
                        <i class="fa-solid fa-store"></i> ไปเลือกดูหนังสือ
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