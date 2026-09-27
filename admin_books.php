<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ Admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$current_page = 'books';
$msg = '';
$msg_type = 'success';

// ระบบลบหนังสือ
if (isset($_GET['delete_id'])) {
    $ebook_id = intval($_GET['delete_id']);
    
    $stmt = $conn->prepare("SELECT cover_image, file_path FROM ebooks WHERE ebook_id = ?");
    $stmt->bind_param("i", $ebook_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['cover_image']) && file_exists($row['cover_image'])) {
            @unlink($row['cover_image']);
        }
        if (!empty($row['file_path']) && file_exists($row['file_path'])) {
            @unlink($row['file_path']);
        }
    }
    
    $del = $conn->prepare("DELETE FROM ebooks WHERE ebook_id = ?");
    // เปลี่ยนจากคำสั่ง DELETE เป็น UPDATE เพื่อซ่อนสินค้า (Soft Delete)
    $del = $conn->prepare("UPDATE ebooks SET is_active = FALSE WHERE ebook_id = ?");
    $del->bind_param("i", $ebook_id);
    if ($del->execute()) {
    $msg = "ปิดการขายหนังสือเรียบร้อยแล้ว";
    $msg_type = 'success';
} else {
    $msg = "ไม่สามารถปิดการขายหนังสือได้";
    $msg_type = 'danger';
}
}

// สลับสถานะแสดง/ซ่อน (Active / Inactive)
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);
    $stmt_toggle = $conn->prepare("UPDATE ebooks SET is_active = IF(is_active=1, 0, 1) WHERE ebook_id = ?");
    $stmt_toggle->bind_param("i", $toggle_id);
    $stmt_toggle->execute();
    header("Location: admin_books.php");
    exit();
}

// ค้นหาและกรอง
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT e.*, a.author_name, c.category_name 
        FROM ebooks e 
        LEFT JOIN authors a ON e.author_id = a.author_id 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR a.author_name LIKE ? OR c.category_name LIKE ?)";
    $stmt_b = $conn->prepare($sql . " ORDER BY e.ebook_id DESC");
    $term = "%{$search}%";
    $stmt_b->bind_param("sss", $term, $term, $term);
    $stmt_b->execute();
    $books = $stmt_b->get_result();
} else {
    $sql .= " ORDER BY e.ebook_id DESC";
    $books = $conn->query($sql);
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหนังสือ E-Book // NEXTREAD Admin</title>
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

    <!-- MAIN CONTENT -->
    <main class="page-container flex-1 p-4 md:p-8 space-y-6 overflow-y-auto">

        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-book-bookmark text-indigo-400"></i> จัดการรายการหนังสือ E-Book
                </h1>
                <p class="text-xs text-slate-400 mt-1">เพิ่ม แก้ไข ลบ หรือจัดการสถานะการวางจำหน่ายหนังสือในระบบ</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="add_book.php" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition inline-flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-plus"></i> เพิ่มหนังสือใหม่
                </a>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="flex items-center justify-between gap-4">
            <form action="admin_books.php" method="GET" class="max-w-md w-full">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง หรือหมวดหมู่..." class="w-full bg-slate-900 border border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-indigo-500 placeholder-slate-500 shadow-inner">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                </div>
            </form>
            <?php if (!empty($search)): ?>
                <a href="admin_books.php" class="text-xs text-indigo-400 hover:underline">ล้างการค้นหา</a>
            <?php endif; ?>
        </div>

        <!-- NOTIFICATION MSG -->
        <?php if ($msg): ?>
            <div class="p-4 rounded-2xl text-xs flex items-center justify-between border shadow-lg <?php echo $msg_type == 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400'; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> text-base"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- BOOKS TABLE SECTION -->
        <div class="glass-card rounded-3xl p-5 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800/80 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4">รูปปก</th>
                            <th class="py-3 px-4">ชื่อเรื่อง & รหัส</th>
                            <th class="py-3 px-4">หมวดหมู่</th>
                            <th class="py-3 px-4">ผู้แต่ง</th>
                            <th class="py-3 px-4">ราคา</th>
                            <th class="py-3 px-4">สถานะ</th>
                            <th class="py-3 px-4 text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if ($books && $books->num_rows > 0): ?>
                            <?php while ($b = $books->fetch_assoc()): 
                                $cover = !empty($b['cover_image']) ? $b['cover_image'] : $default_cover;
                                $author = !empty($b['author_name']) ? $b['author_name'] : '-';
                                $category = !empty($b['category_name']) ? $b['category_name'] : 'ทั่วไป';
                                $is_active = $b['is_active'] ?? 1;
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="w-12 h-16 rounded-xl overflow-hidden bg-slate-900 border border-slate-700 shadow-md book-cover-3d">
                                            <img src="<?php echo htmlspecialchars($cover); ?>" alt="Cover" onerror="this.src='<?php echo $default_cover; ?>';" class="w-full h-full object-cover">
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <a href="book_detail.php?id=<?php echo $b['ebook_id']; ?>" target="_blank" class="font-semibold text-white text-sm block hover:text-indigo-400 transition truncate" title="<?php echo htmlspecialchars($b['title']); ?>">
                                            <?php echo htmlspecialchars($b['title']); ?>
                                        </a>
                                        <span class="text-[10px] text-slate-500 font-mono">#EB-<?php echo str_pad($b['ebook_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-md bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 text-[10px] font-medium">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-300">
                                        <?php echo htmlspecialchars($author); ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-emerald-400 text-sm">
                                        ฿<?php echo number_format($b['price'], 2); ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <a href="admin_books.php?toggle_id=<?php echo $b['ebook_id']; ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-medium transition shadow-sm <?php echo $is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-500 border border-slate-700 hover:text-slate-300'; ?>">
                                            <i class="fa-solid <?php echo $is_active ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                            <span><?php echo $is_active ? 'วางขาย' : 'ซ่อน'; ?></span>
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-1">
                                        <a href="edit_book.php?id=<?php echo $b['ebook_id']; ?>" class="px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500 hover:text-slate-950 font-medium transition inline-flex items-center gap-1 shadow-sm" title="แก้ไข">
                                            <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                        </a>
                                        <a href="admin_books.php?delete_id=<?php echo $b['ebook_id']; ?>" onclick="return confirm('ยืนยันการลบหนังสือเล่มนี้?')" class="px-3 py-1.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white font-medium transition inline-flex items-center gap-1 shadow-sm" title="ลบ">
                                            <i class="fa-solid fa-trash-can"></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-book-open text-4xl mb-3 text-slate-600 block"></i>
                                    <p class="text-sm">ไม่พบรายการหนังสือในระบบ</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>