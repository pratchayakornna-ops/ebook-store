<?php
require_once 'config.php';

// ระบบค้นหาและเลือกหมวดหมู่
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

// ดึงรายการหมวดหมู่ทั้งหมดสำหรับทำแถบกรอง
$categories = [];
$cat_res = $conn->query("SELECT * FROM categories ORDER BY category_id ASC");
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

// ดึงรายการหนังสือ
$sql = "SELECT e.*, a.author_name, c.category_name 
        FROM ebooks e 
        LEFT JOIN authors a ON e.author_id = a.author_id 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        WHERE e.is_active = 1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR a.author_name LIKE ? OR c.category_name LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($category_id > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$sql .= " ORDER BY e.ebook_id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query($sql);
}

$cart_count = getCartCount();
$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXTREAD - แหล่งรวม E-Book ออนไลน์คุณภาพ</title>
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

        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white text-[11px] py-2 px-4 text-center font-medium tracking-wide flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20">
            <span class="shimmer-badge px-2 py-0.5 rounded-full text-[10px] uppercase font-bold tracking-wider">NEW</span>
            <span>ต้อนรับสู่ NEXTREAD คลังหนังสือดิจิทัล สั่งซื้อง่าย เปิดอ่านได้ทันทีทุกที่ทุกเวลา</span>
        </div>

        <!-- MAIN NAVBAR -->
        <nav class="glass-nav sticky top-0 z-50 px-4 md:px-8 py-3.5 flex items-center justify-between gap-4">
            <!-- LOGO -->
            <a href="index.php" class="flex items-center gap-3 shrink-0 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 group-hover:scale-105 group-hover:rotate-3 transition duration-300">
                    <i class="fa-solid fa-book-open text-lg"></i>
                </div>
                <div>
                    <span class="font-bold text-lg text-white tracking-wide block leading-none">NEXTREAD</span>
                    <span class="text-[10px] text-indigo-400 font-medium tracking-wider">E-BOOK STORE</span>
                </div>
            </a>

            <!-- SEARCH BAR (DESKTOP) -->
            <form action="index.php" method="GET" class="flex-1 max-w-md hidden md:block">
                <div class="relative group">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง หรือหมวดหมู่..." class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl py-2.5 pl-10 pr-4 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 placeholder-slate-500 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs group-focus-within:text-indigo-400 transition"></i>
                    <?php if ($category_id > 0): ?>
                        <input type="hidden" name="cat" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                </div>
            </form>

            <!-- USER / ADMIN ACTIONS -->
            <div class="flex items-center gap-2.5 text-xs">
                <!-- ตะกร้าสินค้า -->
                <a href="cart.php" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition relative flex items-center justify-center border border-slate-700/50 hover:border-indigo-500/40" title="ตะกร้าสินค้า">
                    <i class="fa-solid fa-cart-shopping text-sm"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 px-1.5 py-0.5 min-w-[18px] bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center shadow-lg shadow-indigo-500/50 animate-bounce">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                    <a href="my_books.php" class="px-3.5 py-2 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/30 font-medium transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-book-bookmark"></i> <span class="hidden sm:inline">หนังสือของฉัน</span>
                    </a>
                    <a href="orders.php" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition hidden md:inline-flex items-center gap-1.5 border border-slate-700/50">
                        <i class="fa-solid fa-clock-rotate-left"></i> <span>ประวัติสั่งซื้อ</span>
                    </a>
                    <a href="profile.php" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium transition inline-flex items-center gap-1.5 border border-slate-700/50">
                        <i class="fa-solid fa-user text-indigo-400"></i> <span class="hidden sm:inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'บัญชี'); ?></span>
                    </a>
                    <a href="logout.php" onclick="return confirm('ต้องการออกจากระบบหรือไม่?')" class="px-3 py-2 rounded-xl bg-rose-600/10 text-rose-400 hover:bg-rose-600/20 border border-rose-500/20 font-medium transition">
                        <i class="fa-solid fa-arrow-right-from-bracket sm:hidden"></i>
                        <span class="hidden sm:inline">ออกจากระบบ</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium transition border border-slate-700/50">
                        เข้าสู่ระบบ
                    </a>
                    <a href="register.php" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition shadow-lg shadow-indigo-600/30 hidden sm:inline-block">
                        สมัครสมาชิก
                    </a>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <a href="admin_dashboard.php" class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 font-medium transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved"></i> <span class="hidden sm:inline">Admin Panel</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- MOBILE SEARCH BAR -->
        <div class="px-4 pt-3 md:hidden">
            <form action="index.php" method="GET">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง หรือหมวดหมู่..." class="w-full bg-slate-900 border border-slate-800 rounded-xl py-2 pl-10 pr-4 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-2.5 text-slate-500 text-xs"></i>
                    <?php if ($category_id > 0): ?>
                        <input type="hidden" name="cat" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- HERO PROMO BANNER (3D Depth) -->
        <header class="max-w-7xl mx-auto w-full px-4 md:px-8 pt-6">
            <div class="glass-card tilt-card rounded-3xl p-6 md:p-10 bg-gradient-to-r from-indigo-950/80 via-purple-950/50 to-slate-900/90 border border-indigo-500/20 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 shadow-2xl">
                <div class="space-y-4 z-10 text-center md:text-left max-w-xl">
                    <span class="px-3.5 py-1 text-[11px] bg-indigo-500/20 text-indigo-300 rounded-full font-semibold border border-indigo-500/30 inline-block shadow-sm">
                        🚀 คลังหนังสือดิจิทัล NEXTREAD Store
                    </span>
                    <h2 class="text-2xl md:text-4xl font-extrabold text-white tracking-tight leading-tight">
                        เปิดโลกแห่งการเรียนรู้ อ่าน E-Book ได้ทุกที่ทุกเวลา
                    </h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        แหล่งรวม E-Book ครบทุกหมวดหมู่ จิตวิทยา ธุรกิจการเงิน ไอทีโปรแกรมมิ่ง และนิยาย ซื้อครั้งเดียวเปิดอ่านได้ตลอดชีพบนทุกอุปกรณ์
                    </p>
                    <div class="pt-2 flex items-center justify-center md:justify-start gap-3">
                        <a href="#book-list" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition inline-flex items-center gap-2 shadow-lg shadow-indigo-600/40">
                            เลือกดูหนังสือทั้งหมด <i class="fa-solid fa-arrow-down"></i>
                        </a>
                        <?php if (isLoggedIn()): ?>
                            <a href="my_books.php" class="px-5 py-2.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition inline-flex items-center gap-2 border border-slate-700">
                                <i class="fa-solid fa-book-bookmark text-indigo-400"></i> หนังสือของฉัน
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- STATS BADGE -->
                <div class="z-10 grid grid-cols-2 gap-3 shrink-0">
                    <div class="glass-card p-4 rounded-2xl text-center min-w-[120px] border border-indigo-500/20 hover:scale-105 transition duration-300">
                        <span class="block text-2xl font-extrabold text-indigo-400"><?php echo ($books) ? $books->num_rows : 0; ?>+</span>
                        <span class="text-[10px] text-slate-400">รายการหนังสือ</span>
                    </div>
                    <div class="glass-card p-4 rounded-2xl text-center min-w-[120px] border border-emerald-500/20 hover:scale-105 transition duration-300">
                        <span class="block text-2xl font-extrabold text-emerald-400">100%</span>
                        <span class="text-[10px] text-slate-400">อ่านได้ทันที</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main id="book-list" class="max-w-7xl mx-auto w-full px-4 md:px-8 py-8 flex-1">
            
            <!-- HEADER & CATEGORY TABS -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-indigo-400"></i> รายการหนังสือทั้งหมด
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <?php 
                            if (!empty($search)) {
                                echo 'ผลการค้นหาสำหรับ "' . htmlspecialchars($search) . '"';
                            } elseif ($category_id > 0) {
                                $cat_name = 'หมวดหมู่ที่เลือก';
                                foreach ($categories as $c) {
                                    if ($c['category_id'] == $category_id) {
                                        $cat_name = $c['category_name'];
                                        break;
                                    }
                                }
                                echo 'หมวดหมู่: ' . htmlspecialchars($cat_name);
                            } else {
                                echo 'เลือกดูและสั่งซื้อหนังสือดิจิทัลในระบบ';
                            }
                        ?>
                    </p>
                </div>

                <!-- CATEGORY CHIPS -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 text-xs">
                    <a href="index.php<?php echo !empty($search) ? '?search='.urlencode($search) : ''; ?>" 
                       class="px-4 py-2 rounded-xl font-medium whitespace-nowrap transition <?php echo ($category_id === 0) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/50'; ?>">
                        ทั้งหมด
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?cat=<?php echo $cat['category_id']; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium whitespace-nowrap transition <?php echo ($category_id === (int)$cat['category_id']) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/50'; ?>">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- BOOK GRID (Dynamic Motion Cards) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <?php if ($books && $books->num_rows > 0): 
                    $index = 0;
                ?>
                    <?php while ($b = $books->fetch_assoc()): 
                        $index++;
                        $stagger_class = 'stagger-' . min($index, 8);
                        $author = !empty($b['author_name']) ? $b['author_name'] : 'ไม่ระบุผู้แต่ง';
                        $category = !empty($b['category_name']) ? $b['category_name'] : 'ทั่วไป';
                        
                        $img_url = $default_cover;
                        if (!empty($b['cover_image'])) {
                            $img_url = $b['cover_image'];
                        }
                    ?>
                        <div class="glass-card interactive-card stagger-item <?php echo $stagger_class; ?> rounded-2xl p-3.5 flex flex-col justify-between group">
                            <div>
                                <!-- COVER IMAGE (3D Illusion) -->
                                <a href="book_detail.php?id=<?php echo $b['ebook_id']; ?>" class="block w-full aspect-[3/4] rounded-xl overflow-hidden bg-slate-900 mb-3 border border-slate-800 shadow-md relative book-cover-3d">
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" 
                                         alt="<?php echo htmlspecialchars($b['title']); ?>" 
                                         onerror="this.onerror=null; this.src='<?php echo $default_cover; ?>';"
                                         class="w-full h-full object-cover">
                                    <span class="absolute top-2 right-2 bg-slate-900/80 backdrop-blur-md text-indigo-300 text-[9px] font-bold px-2 py-0.5 rounded-md border border-slate-700">
                                        <?php echo htmlspecialchars($category); ?>
                                    </span>
                                </a>

                                <!-- TITLE & AUTHOR -->
                                <a href="book_detail.php?id=<?php echo $b['ebook_id']; ?>" class="block">
                                    <h3 class="font-semibold text-slate-100 text-xs line-clamp-1 mb-1 hover:text-indigo-400 transition" title="<?php echo htmlspecialchars($b['title']); ?>">
                                        <?php echo htmlspecialchars($b['title']); ?>
                                    </h3>
                                </a>
                                <p class="text-[11px] text-slate-400 mb-3 line-clamp-1">โดย <?php echo htmlspecialchars($author); ?></p>
                            </div>
                            
                            <!-- PRICE & ACTION -->
                            <div class="pt-2.5 border-t border-slate-800/80 flex items-center justify-between gap-1.5">
                                <div>
                                    <span class="text-[9px] text-slate-500 block leading-none">ราคา</span>
                                    <span class="text-xs font-bold text-emerald-400">฿<?php echo number_format($b['price'], 2); ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="cart.php?action=add&id=<?php echo $b['ebook_id']; ?>" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition border border-slate-700" title="เพิ่มลงตะกร้า">
                                        <i class="fa-solid fa-cart-plus text-xs"></i>
                                    </a>
                                    <a href="book_detail.php?id=<?php echo $b['ebook_id']; ?>" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-medium transition shadow-md shadow-indigo-600/20">
                                        สั่งซื้อ
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full py-16 text-center text-slate-500 glass-card rounded-2xl">
                        <i class="fa-solid fa-book-open text-4xl mb-3 block text-slate-600"></i>
                        <p class="text-sm font-medium text-slate-300">ไม่พบรายการหนังสือตรงกับเงื่อนไข</p>
                        <p class="text-xs text-slate-500 mt-1">ลองล้างการค้นหา หรือเลือกดูหมวดหมู่อื่น</p>
                        <a href="index.php" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-500 transition">
                            <i class="fa-solid fa-rotate-left"></i> ดูหนังสือทั้งหมด
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- FULL FOOTER -->
        <footer class="border-t border-slate-800 bg-slate-950/80 mt-12 py-10">
            <div class="max-w-7xl mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-3 gap-8 text-xs text-slate-400">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                            <i class="fa-solid fa-book-open text-xs"></i>
                        </div>
                        <span class="font-bold text-white text-sm">NEXTREAD</span>
                    </div>
                    <p class="text-slate-500 leading-relaxed">
                        ระบบร้านค้า E-Book ออนไลน์ สมบูรณ์แบบ รองรับการอ่านออนไลน์และดาวน์โหลดไฟล์สำหรับอุปกรณ์ดิจิทัลทุกประเภท
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-200 mb-3">เมนูลัด</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-indigo-400 transition">หน้าร้านค้าหลัก</a></li>
                        <li><a href="cart.php" class="hover:text-indigo-400 transition">ตะกร้าสินค้า</a></li>
                        <li><a href="my_books.php" class="hover:text-indigo-400 transition">หนังสือของฉัน (My Shelf)</a></li>
                        <li><a href="admin_dashboard.php" class="hover:text-indigo-400 transition">ระบบผู้ดูแลระบบ (Admin)</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-200 mb-3">ติดต่อและช่วยเหลือ</h4>
                    <p class="text-slate-500 leading-relaxed mb-2">ระบบบริการลูกค้า 24 ชม. หากพบปัญหาในการสั่งซื้อหรือการอ่านไฟล์</p>
                    <span class="text-indigo-400 font-medium">support@nextread.com</span>
                </div>
            </div>
            <div class="border-t border-slate-900 mt-8 pt-6 text-center text-[11px] text-slate-600">
                <p>© <?php echo date('Y'); ?> NEXTREAD E-Book Store. All rights reserved.</p>
            </div>
        </footer>

    </div>

</body>
</html>