<?php
// sidebar.php - เมนูด้านข้างสำหรับฝั่ง Admin
if (!isset($current_page)) $current_page = '';

// ดึงจำนวนคำสั่งซื้อรออนุมัติสำหรับแสดง Badge
$pending_count = 0;
if (isset($conn)) {
    try {
        $res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
        if ($res) { 
            $pending_count = $res->fetch_assoc()['total'] ?? 0; 
        }
    } catch (Exception $e) {}
}
?>

<aside class="w-full md:w-64 glass-card border-r-0 md:border-r border-b md:border-b-0 border-slate-800 shrink-0 p-5 flex flex-col justify-between min-h-screen">
    <div>
        <!-- BRAND LOGO -->
        <div class="flex items-center gap-3 px-2 py-3 mb-8 border-b border-slate-800/80">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </div>
            <div>
                <h2 class="font-bold text-base text-white tracking-wide">NEXTREAD</h2>
                <span class="px-2 py-0.5 text-[9px] bg-indigo-500/20 text-indigo-300 font-semibold rounded-full border border-indigo-500/30">ADMIN PANEL</span>
            </div>
        </div>

        <!-- NAVIGATION MENU -->
        <nav class="space-y-1.5 text-xs">
            <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-2">เมนูการจัดการระบบ</p>
            
            <!-- 1. แดชบอร์ดสรุปผล -->
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?php echo ($current_page == 'dashboard') ? 'nav-item-active font-semibold text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'; ?>">
                <i class="fa-solid fa-chart-pie text-sm w-4 text-center <?php echo ($current_page == 'dashboard') ? 'text-indigo-400' : ''; ?>"></i>
                <span>แดชบอร์ดภาพรวม</span>
            </a>

            <!-- 2. อนุมัติคำสั่งซื้อ -->
            <a href="admin_orders.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition <?php echo ($current_page == 'orders') ? 'nav-item-active font-semibold text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'; ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-receipt text-sm w-4 text-center <?php echo ($current_page == 'orders') ? 'text-indigo-400' : ''; ?>"></i>
                    <span>อนุมัติคำสั่งซื้อ</span>
                </div>
                <?php if ($pending_count > 0): ?>
                    <span class="px-2 py-0.5 text-[10px] bg-amber-500 text-slate-950 font-bold rounded-full shadow-lg shadow-amber-500/20 animate-pulse">
                        <?php echo $pending_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- 3. จัดการหนังสือ E-Book -->
            <a href="admin_books.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?php echo ($current_page == 'books') ? 'nav-item-active font-semibold text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'; ?>">
                <i class="fa-solid fa-book-bookmark text-sm w-4 text-center <?php echo ($current_page == 'books') ? 'text-indigo-400' : ''; ?>"></i>
                <span>จัดการรายการหนังสือ</span>
            </a>

            <!-- 4. เพิ่มหนังสือใหม่ -->
            <a href="add_book.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?php echo ($current_page == 'add_book') ? 'nav-item-active font-semibold text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'; ?>">
                <i class="fa-solid fa-plus-circle text-sm w-4 text-center <?php echo ($current_page == 'add_book') ? 'text-indigo-400' : ''; ?>"></i>
                <span>เพิ่มหนังสือใหม่</span>
            </a>

            <!-- 5. SQL Query Console & Analytics -->
            <a href="admin_sql.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?php echo ($current_page == 'sql') ? 'nav-item-active font-semibold text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'; ?>">
                <i class="fa-solid fa-terminal text-sm w-4 text-center <?php echo ($current_page == 'sql') ? 'text-indigo-400' : ''; ?>"></i>
                <span>SQL Console & รายงาน</span>
            </a>
        </nav>
    </div>

    <!-- BOTTOM ACTIONS -->
    <div class="pt-4 border-t border-slate-800/80 space-y-1.5 text-xs">
        <div class="px-3 py-2 bg-slate-900/60 rounded-xl border border-slate-800 mb-2">
            <p class="text-[10px] text-slate-500">ผู้ดูแลระบบ</p>
            <p class="text-xs font-semibold text-slate-200 truncate"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></p>
        </div>
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/50 transition">
            <i class="fa-solid fa-store text-sm w-4 text-center"></i>
            <span>กลับหน้าร้านค้า</span>
        </a>
        <a href="logout.php" onclick="return confirm('ยืนยันการออกจากระบบ?')" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-rose-400 hover:bg-rose-500/10 transition">
            <i class="fa-solid fa-arrow-right-from-bracket text-sm w-4 text-center"></i>
            <span>ออกจากระบบ</span>
        </a>
    </div>
</aside>