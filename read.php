<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ebook_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ebook_id <= 0) {
    header("Location: my_books.php");
    exit();
}

// ตรวจสอบสิทธิ์การเข้าถึง (Admin หรือ ผู้ที่สั่งซื้อแล้วได้รับอนุมัติ)
$has_access = false;

if (isAdmin()) {
    $has_access = true;
} else {
    $check_sql = "SELECT 1 
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.order_id 
                  WHERE o.user_id = ? AND oi.ebook_id = ? AND o.status = 'approved' 
                  LIMIT 1";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $ebook_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $has_access = true;
    }
}

if (!$has_access) {
    echo "<script>alert('คุณยังไม่มีสิทธิ์เข้าถึงหนังสือเล่มนี้ หรือคำสั่งซื้อยังไม่ได้รับการอนุมัติ'); window.location.href='index.php';</script>";
    exit();
}

// ดึงข้อมูลหนังสือ
$stmt_book = $conn->prepare("SELECT e.*, a.author_name, c.category_name 
                            FROM ebooks e 
                            LEFT JOIN authors a ON e.author_id = a.author_id 
                            LEFT JOIN categories c ON e.category_id = c.category_id 
                            WHERE e.ebook_id = ?");
$stmt_book->bind_param("i", $ebook_id);
$stmt_book->execute();
$book = $stmt_book->get_result()->fetch_assoc();

if (!$book) {
    header("Location: my_books.php");
    exit();
}

$default_cover = "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";
$cover = !empty($book['cover_image']) ? $book['cover_image'] : $default_cover;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อ่าน: <?php echo htmlspecialchars($book['title']); ?> // NEXTREAD Reader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
    <style>
        .reader-body { font-family: 'Sarabun', sans-serif; }
        .theme-dark { background-color: #0f172a; color: #e2e8f0; }
        .theme-sepia { background-color: #fbf0d9; color: #5f4b32; }
        .theme-light { background-color: #ffffff; color: #1e293b; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased bg-slate-950">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <!-- TOP READER TOOLBAR -->
    <header class="glass-nav sticky top-0 z-50 px-4 md:px-8 py-3.5 flex items-center justify-between gap-4">
        <!-- Back & Title -->
        <div class="flex items-center gap-3 min-w-0">
            <a href="my_books.php" class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition shrink-0" title="กลับชั้นหนังสือ">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div class="min-w-0">
                <h1 class="font-bold text-sm text-white truncate max-w-xs sm:max-w-md">
                    <?php echo htmlspecialchars($book['title']); ?>
                </h1>
                <p class="text-[11px] text-indigo-400 truncate">โดย <?php echo htmlspecialchars($book['author_name'] ?? 'ผู้แต่ง'); ?></p>
            </div>
        </div>

        <!-- Reader Controls -->
        <div class="flex items-center gap-2 text-xs">
            <!-- Theme buttons -->
            <div class="hidden sm:flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800 shadow-inner">
                <button onclick="setTheme('dark')" class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 text-[11px] font-medium transition" title="โหมดมืด">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button onclick="setTheme('sepia')" class="px-2.5 py-1 rounded-lg bg-[#fbf0d9] text-[#5f4b32] text-[11px] font-medium transition" title="โหมดถนอมสายตา">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <button onclick="setTheme('light')" class="px-2.5 py-1 rounded-lg bg-white text-slate-800 text-[11px] font-medium transition" title="โหมดสว่าง">
                    <i class="fa-solid fa-circle-half-stroke"></i>
                </button>
            </div>

            <!-- Font size controls -->
            <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800 shadow-inner">
                <button onclick="changeFontSize(-1)" class="w-7 h-7 rounded-lg hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold" title="ลดขนาดตัวอักษร">A-</button>
                <button onclick="changeFontSize(1)" class="w-7 h-7 rounded-lg hover:bg-slate-800 text-slate-300 flex items-center justify-center font-bold" title="เพิ่มขนาดตัวอักษร">A+</button>
            </div>

            <?php if (!empty($book['file_link']) || !empty($book['file_path'])): ?>
                <a href="<?php echo htmlspecialchars($book['file_link'] ?: $book['file_path']); ?>" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition inline-flex items-center gap-1.5 shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-download"></i> <span class="hidden md:inline">ดาวน์โหลด</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- READING PROGRESS BAR -->
    <div class="w-full bg-slate-800 h-1 sticky top-[57px] z-40">
        <div id="progress-bar" class="bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-400 h-full w-0 transition-all duration-150"></div>
    </div>

    <!-- MAIN READING CONTAINER -->
    <main class="page-container max-w-4xl mx-auto w-full px-4 md:px-8 py-10 flex-1">
        
        <div id="reader-container" class="theme-dark p-6 md:p-12 rounded-3xl shadow-2xl transition duration-300 space-y-8 border border-slate-800/80">
            
            <!-- BOOK HEADER INFO -->
            <div class="text-center pb-8 border-b border-current border-opacity-10 space-y-4">
                <div class="book-cover-3d inline-block">
                    <img src="<?php echo htmlspecialchars($cover); ?>" alt="Cover" class="w-32 h-44 object-cover rounded-2xl mx-auto shadow-2xl border border-current border-opacity-20">
                </div>
                <div>
                    <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight"><?php echo htmlspecialchars($book['title']); ?></h2>
                    <p class="text-sm opacity-80 mt-1">ผู้แต่ง: <?php echo htmlspecialchars($book['author_name'] ?: 'ไม่ระบุผู้แต่ง'); ?> | หมวดหมู่: <?php echo htmlspecialchars($book['category_name'] ?: 'ทั่วไป'); ?></p>
                </div>
            </div>

            <!-- CHAPTER / CONTENT -->
            <div id="reader-text" class="reader-body text-base md:text-lg leading-relaxed space-y-6">
                
                <h3 class="text-xl font-bold border-l-4 border-indigo-500 pl-3">บทนำ & ภาพรวมเนื้อหา</h3>
                
                <p>
                    <?php echo nl2br(htmlspecialchars($book['description'] ?: 'ยินดีต้อนรับสู่หนังสือเล่มนี้ นี่คือเนื้อหาจำลองสำหรับการเปิดอ่านหนังสือดิจิทัลระบบ NEXTREAD')); ?>
                </p>

                <h3 class="text-xl font-bold border-l-4 border-indigo-500 pl-3 pt-4">บทที่ 1: การเริ่มต้นและพื้นฐานที่สำคัญ</h3>
                
                <p>
                    ความสำเร็จในการเรียนรู้และต่อยอดเริ่มต้นจากความเข้าใจในหลักการพื้นฐานที่ถูกต้อง เมื่อเรามีเป้าหมายที่ชัดเจน การฝึกฝนและการลงมือทำอย่างต่อเนื่องจะช่วยเสริมสร้างทักษะและความเชี่ยวชาญได้อย่างก้าวกระโดด ไม่ว่าจะเป็นเรื่องของการพัฒนาตนเอง การบริหารการเงิน หรือการเขียนโค้ดและเทคโนโลยี
                </p>

                <p>
                    ในแต่ละก้าวที่เราเดิน การสังเกตและทบทวนสิ่งที่ได้เรียนรู้คือหัวใจสำคัญ หนังสือเล่มนี้ได้รับการออกแบบมาเพื่อให้ผู้อ่านสามารถนำข้อคิดและแนวทางปฏิบัติไปประยุกต์ใช้ในชีวิตจริงได้อย่างมีประสิทธิภาพสูงสุด
                </p>

                <div class="p-6 rounded-3xl bg-indigo-950/40 border border-indigo-500/20 my-6 shadow-inner">
                    <h4 class="font-bold text-sm text-indigo-300 mb-2 flex items-center gap-2"><i class="fa-solid fa-lightbulb"></i> ข้อคิดประจำบท</h4>
                    <p class="text-sm opacity-90 leading-relaxed italic">
                        "การเปลี่ยนแปลงที่ยิ่งใหญ่ ไม่ได้เกิดจากการกระทำครั้งเดียวที่ยิ่งใหญ่ แต่เกิดจากการสะสมสิ่งเล็กๆ ที่ทำอย่างสม่ำเสมอทุกวัน"
                    </p>
                </div>

                <h3 class="text-xl font-bold border-l-4 border-indigo-500 pl-3 pt-4">บทที่ 2: กลยุทธ์และการนำไปใช้จริง</h3>
                
                <p>
                    เมื่อเข้าใจแก่นแท้แล้ว ขั้นตอนต่อไปคือการวางโครงสร้างและแผนการทำงานที่เป็นรูปธรรม นำความรู้ที่ได้รับมาวิเคราะห์ร่วมกับสถานการณ์ปัจจุบันของคุณ เพื่อให้ได้ผลลัพธ์ที่ตรงจุดและยั่งยืน
                </p>

                <p>
                    ขอให้เพลิดเพลินกับการอ่านและการเรียนรู้ผ่าน NEXTREAD E-Book Store!
                </p>
            </div>

            <!-- BOTTOM PAGINATION -->
            <div class="pt-8 border-t border-current border-opacity-10 flex items-center justify-between text-xs">
                <a href="my_books.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> ปิดหน้าอ่าน
                </a>
                <span class="opacity-60">NEXTREAD Reader v2.0</span>
            </div>

        </div>

    </main>

    <script>
        // Reading Progress Script
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("progress-bar").style.width = scrolled + "%";
        };

        // Theme Switcher
        function setTheme(theme) {
            const container = document.getElementById('reader-container');
            container.classList.remove('theme-dark', 'theme-sepia', 'theme-light');
            container.classList.add('theme-' + theme);
        }

        // Font Size Adjuster
        let currentSize = 18;
        function changeFontSize(delta) {
            currentSize = Math.max(14, Math.min(28, currentSize + (delta * 2)));
            document.getElementById('reader-text').style.fontSize = currentSize + 'px';
        }
    </script>

</body>
</html>
