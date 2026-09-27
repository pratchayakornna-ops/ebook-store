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

$ebook_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ebook_id <= 0) {
    header("Location: admin_books.php");
    exit();
}

// ดึงข้อมูลหนังสือปัจจุบัน
$stmt_curr = $conn->prepare("SELECT * FROM ebooks WHERE ebook_id = ?");
$stmt_curr->bind_param("i", $ebook_id);
$stmt_curr->execute();
$book = $stmt_curr->get_result()->fetch_assoc();

if (!$book) {
    header("Location: admin_books.php");
    exit();
}

// ดึงรายการหมวดหมู่และผู้แต่งสำหรับ Dropdown
$categories = [];
$cat_res = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

$authors = [];
$author_res = $conn->query("SELECT * FROM authors ORDER BY author_name ASC");
if ($author_res) {
    while ($row = $author_res->fetch_assoc()) {
        $authors[] = $row;
    }
}

// บันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $cover_url_input = trim($_POST['cover_url'] ?? '');
    $file_link = trim($_POST['file_link'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // จัดการ Author
    $author_id = intval($_POST['author_id'] ?? 0);
    $new_author = trim($_POST['new_author'] ?? '');
    if (!empty($new_author)) {
        $stmt_new_a = $conn->prepare("INSERT INTO authors (author_name) VALUES (?)");
        $stmt_new_a->bind_param("s", $new_author);
        if ($stmt_new_a->execute()) {
            $author_id = $stmt_new_a->insert_id;
        }
    }

    // จัดการ Category
    $category_id = intval($_POST['category_id'] ?? 0);
    $new_category = trim($_POST['new_category'] ?? '');
    if (!empty($new_category)) {
        $stmt_new_c = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt_new_c->bind_param("s", $new_category);
        if ($stmt_new_c->execute()) {
            $category_id = $stmt_new_c->insert_id;
        }
    }

    // จัดการรูปภาพปก (ถ้ามีอัปโหลดใหม่)
    $cover_path = !empty($cover_url_input) ? $cover_url_input : $book['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/covers/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'cover_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                $cover_path = $target_file;
            }
        }
    }

    // จัดการไฟล์ E-Book (PDF)
    $file_path = $book['file_path'];
    if (isset($_FILES['ebook_file']) && $_FILES['ebook_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/files/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['ebook_file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'epub'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'ebook_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['ebook_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            }
        }
    }

    if (!empty($title) && $price >= 0 && $author_id > 0 && $category_id > 0) {
        try {
            $stmt_up = $conn->prepare("UPDATE ebooks SET title = ?, author_id = ?, category_id = ?, price = ?, description = ?, cover_image = ?, file_link = ?, file_path = ?, is_active = ? WHERE ebook_id = ?");
            $stmt_up->bind_param("siidssssii", $title, $author_id, $category_id, $price, $description, $cover_path, $file_link, $file_path, $is_active, $ebook_id);
            
            if ($stmt_up->execute()) {
                header("Location: admin_books.php");
                exit();
            } else {
                $msg = "ไม่สามารถบันทึกข้อมูลได้: " . $conn->error;
                $msg_type = 'danger';
            }
        } catch (Exception $e) {
            $msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $msg = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $msg_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขหนังสือ // NEXTREAD Admin</title>
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
        <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i> แก้ไขข้อมูลหนังสือ E-Book
                </h1>
                <p class="text-xs text-slate-400 mt-1">แก้ไขรายละเอียด ราคา รูปปก หรือไฟล์เนื้อหาของ #EB-<?php echo str_pad($book['ebook_id'], 4, '0', STR_PAD_LEFT); ?></p>
            </div>
            
            <a href="admin_books.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs transition inline-flex items-center gap-2 border border-slate-700">
                <i class="fa-solid fa-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="p-4 rounded-2xl text-xs bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <!-- FORM CARD -->
        <div class="glass-card tilt-card rounded-3xl p-6 md:p-8 max-w-3xl shadow-2xl">
            <form action="edit_book.php?id=<?php echo $ebook_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-xs">
                
                <!-- TITLE -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1.5">ชื่อเรื่องหนังสือ <span class="text-rose-400">*</span></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs">
                </div>

                <!-- CATEGORY & AUTHOR -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1.5">หมวดหมู่หนังสือ <span class="text-rose-400">*</span></label>
                        <select name="category_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs mb-2">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $book['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_category" class="w-full bg-slate-900/60 border border-slate-800 rounded-xl p-2.5 text-slate-300 text-[11px] placeholder-slate-500" placeholder="+ หรือเปลี่ยนเป็นหมวดหมู่ใหม่...">
                    </div>

                    <!-- AUTHOR -->
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1.5">ผู้แต่ง / ผู้เขียน <span class="text-rose-400">*</span></label>
                        <select name="author_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs mb-2">
                            <?php foreach ($authors as $aut): ?>
                                <option value="<?php echo $aut['author_id']; ?>" <?php echo ($aut['author_id'] == $book['author_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aut['author_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_author" class="w-full bg-slate-900/60 border border-slate-800 rounded-xl p-2.5 text-slate-300 text-[11px] placeholder-slate-500" placeholder="+ หรือเปลี่ยนเป็นชื่อผู้แต่งใหม่...">
                    </div>
                </div>

                <!-- PRICE & STATUS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1.5">ราคาจำหน่าย (บาท) <span class="text-rose-400">*</span></label>
                        <input type="number" step="0.01" name="price" value="<?php echo $book['price']; ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs">
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-300 select-none">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($book['is_active'] == 1) ? 'checked' : ''; ?> class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700">
                            <span class="font-medium">เปิดวางจำหน่าย (Active)</span>
                        </label>
                    </div>
                </div>

                <!-- COVER IMAGE -->
                <div class="space-y-2">
                    <label class="block text-slate-300 font-semibold mb-1.5">รูปภาพปกหนังสือ</label>
                    <?php if (!empty($book['cover_image'])): ?>
                        <div class="flex items-center gap-3 mb-2 p-2 bg-slate-900/60 rounded-xl border border-slate-800">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="w-10 h-14 object-cover rounded-lg border border-slate-700">
                            <span class="text-[11px] text-slate-400 truncate">รูปปัจจุบัน: <?php echo htmlspecialchars($book['cover_image']); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" accept="image/*" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 text-xs">
                    <input type="text" name="cover_url" value="<?php echo filter_var($book['cover_image'], FILTER_VALIDATE_URL) ? htmlspecialchars($book['cover_image']) : ''; ?>" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-300 text-[11px] placeholder-slate-500" placeholder="หรือวาง URL รูปภาพปกออนไลน์">
                </div>

                <!-- E-BOOK FILE -->
                <div class="space-y-2">
                    <label class="block text-slate-300 font-semibold mb-1.5">ไฟล์ E-Book / ลิงก์ดาวน์โหลด</label>
                    <?php if (!empty($book['file_path']) || !empty($book['file_link'])): ?>
                        <p class="text-[11px] text-emerald-400">ไฟล์ปัจจุบัน: <?php echo htmlspecialchars($book['file_path'] ?: $book['file_link']); ?></p>
                    <?php endif; ?>
                    <input type="file" name="ebook_file" accept=".pdf,.epub" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 text-xs">
                    <input type="text" name="file_link" value="<?php echo htmlspecialchars($book['file_link'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-300 text-[11px] placeholder-slate-500" placeholder="หรือระบุลิงก์ดาวน์โหลดภายนอก">
                </div>

                <!-- DESCRIPTION -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1.5">รายละเอียดเรื่องย่อ / คำอธิบาย</label>
                    <textarea name="description" rows="4" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-indigo-500 text-xs"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-3 flex gap-3">
                    <button type="submit" class="flex-1 py-4 rounded-xl bg-gradient-to-r from-amber-600 to-indigo-600 hover:from-amber-500 hover:to-indigo-500 text-white font-bold transition text-xs shadow-lg shadow-amber-600/30 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึกการแก้ไข
                    </button>
                    <a href="admin_books.php" class="px-5 py-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition text-xs border border-slate-700">
                        ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
