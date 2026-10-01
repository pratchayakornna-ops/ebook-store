<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ Admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$current_page = 'sql';

// ค่าเริ่มต้นสำหรับ SQL Query
$default_query = "SELECT 
    DATE(o.order_date) AS order_day,
    COUNT(o.order_id) AS total_orders,
    SUM(o.total_amount) AS total_revenue,
    ROUND(AVG(o.total_amount), 2) AS avg_order_value
FROM orders o
WHERE o.status = 'approved'
GROUP BY DATE(o.order_date)
ORDER BY order_day DESC;";

$sql_query = isset($_POST['sql_query']) ? trim($_POST['sql_query']) : (isset($_GET['preset']) ? trim($_GET['preset']) : $default_query);

$query_result = null;
$error_message = '';
$affected_rows = 0;
$execution_time = 0;
$columns = [];
$rows = [];

// จัดการการรัน Query
if (!empty($sql_query) && isset($conn)) {
    $start_time = microtime(true);
    try {
        // ใช้ multi_query หรือ query
        $res = $conn->query($sql_query);
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        if ($res === false) {
            $error_message = $conn->error;
        } elseif ($res === true) {
            $affected_rows = $conn->affected_rows;
            $query_result = 'non_select';
        } else {
            $query_result = 'select';
            while ($field = $res->fetch_field()) {
                $columns[] = $field->name;
            }
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// ดึงรายชื่อตารางทั้งหมดในฐานข้อมูลสำหรับแถบ Quick Inspector
$tables_list = [];
try {
    $tbl_res = $conn->query("SHOW TABLES");
    if ($tbl_res) {
        while ($t = $tbl_res->fetch_row()) {
            $tables_list[] = $t[0];
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Query Console // NEXTREAD Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased bg-slate-950 text-slate-100">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto max-h-screen">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-3">
                        <i class="fa-solid fa-terminal text-indigo-400"></i>
                        SQL Query Console & Database Analytics
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        หน้าต่างเขียนคำสั่ง SQL สำหรับดึงข้อมูลและสร้างรายงานวิเคราะห์ธุรกิจ E-Book แบบเรียลไทม์
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        DB Connected (MySQL)
                    </span>
                </div>
            </div>

            <!-- PRESET QUERY SHORTCUTS -->
            <div class="glass-card rounded-2xl p-4 border border-slate-800/80">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-xs"></i>
                    <span class="text-xs font-bold text-slate-200">คำสั่ง SQL รายงานวิเคราะห์มาตรฐาน (ตามเกณฑ์โครงงาน Mini Project):</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                    <button type="button" onclick="setQuery(1)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">📊 1. ยอดขายตามช่วงเวลา</span>
                        <span class="text-[10px] text-slate-500">GROUP BY วันที่ & ยอดเฉลี่ย</span>
                    </button>
                    <button type="button" onclick="setQuery(2)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">🏆 2. อันดับ E-Book ขายดี</span>
                        <span class="text-[10px] text-slate-500">TOP 5 Books + LIMIT</span>
                    </button>
                    <button type="button" onclick="setQuery(3)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">📑 3. ยอดขายตามหมวดหมู่</span>
                        <span class="text-[10px] text-slate-500">JOIN หลายตาราง & สัดส่วน %</span>
                    </button>
                    <button type="button" onclick="setQuery(4)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">👥 4. ลูกค้า Top Spenders</span>
                        <span class="text-[10px] text-slate-500">HAVING ซื้อมากกว่า 2 ครั้ง</span>
                    </button>
                    <button type="button" onclick="setQuery(5)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">📦 5. รายการ 30 คำสั่งซื้อ</span>
                        <span class="text-[10px] text-slate-500">Orders + Payments + Users</span>
                    </button>
                    <button type="button" onclick="setQuery(6)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">📚 6. ข้อมูล E-Book ทั้งหมด</span>
                        <span class="text-[10px] text-slate-500">E-Books + Authors + Categories</span>
                    </button>
                    <button type="button" onclick="setQuery(7)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">📋 7. แสดงตารางทั้งหมด</span>
                        <span class="text-[10px] text-slate-500">SHOW TABLES in Database</span>
                    </button>
                    <button type="button" onclick="setQuery(8)" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-slate-800 text-left transition group">
                        <span class="font-semibold text-slate-200 block group-hover:text-indigo-300">👤 8. สรุปสมาชิกและบทบาท</span>
                        <span class="text-[10px] text-slate-500">Users list & Roles</span>
                    </button>
                </div>
            </div>

            <!-- SQL EDITOR FORM -->
            <div class="glass-card rounded-3xl p-6 border border-slate-800/80 shadow-2xl">
                <form action="admin_sql.php" method="POST" id="sqlForm" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label for="sql_query" class="text-xs font-bold text-slate-300 flex items-center gap-2">
                            <i class="fa-solid fa-code text-indigo-400"></i>
                            พิมพ์คำสั่ง SQL ด้านล่าง (SELECT, JOIN, GROUP BY, INSERT, UPDATE, DELETE):
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="clearQuery()" class="px-2.5 py-1 text-[11px] text-slate-400 hover:text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-lg transition">
                                <i class="fa-solid fa-eraser mr-1"></i> ล้างคำสั่ง
                            </button>
                            <button type="button" onclick="copyQuery()" class="px-2.5 py-1 text-[11px] text-slate-400 hover:text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-lg transition">
                                <i class="fa-solid fa-copy mr-1"></i> คัดลอก SQL
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <textarea name="sql_query" id="sql_query" rows="6" 
                                  class="w-full bg-slate-950 border border-slate-800 rounded-2xl p-4 font-mono text-xs md:text-sm text-indigo-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 leading-relaxed transition resize-y"
                                  placeholder="พิมพ์ SQL Query เช่น SELECT * FROM ebooks WHERE price > 300;" required><?php echo htmlspecialchars($sql_query); ?></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                        <div class="text-[11px] text-slate-500 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-slate-400"></i>
                            <span>รองรับคำสั่ง SQL มาตรฐาน MySQL/MariaDB และ Aggregate Functions ทุกชนิด</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                                <i class="fa-solid fa-play"></i> รันคำสั่ง SQL (Execute)
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- QUERY EXECUTION RESULT -->
            <?php if (!empty($error_message)): ?>
                <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-2">
                    <div class="font-bold flex items-center gap-2 text-rose-400">
                        <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        เกิดข้อผิดพลาดในการประมวลผลคำสั่ง SQL:
                    </div>
                    <p class="font-mono bg-slate-950/80 p-3 rounded-xl border border-rose-500/20 overflow-x-auto text-rose-200">
                        <?php echo htmlspecialchars($error_message); ?>
                    </p>
                </div>
            <?php elseif ($query_result === 'non_select'): ?>
                <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs space-y-1">
                    <div class="font-bold flex items-center gap-2 text-emerald-400 text-sm">
                        <i class="fa-solid fa-circle-check text-base"></i>
                        คำสั่ง SQL ดำเนินการสำเร็จเรียบร้อย!
                    </div>
                    <p class="text-slate-300">
                        จำนวนแถวที่ได้รับผลกระทบ: <span class="font-bold text-emerald-400"><?php echo $affected_rows; ?></span> แถว (เวลาที่ใช้: <?php echo $execution_time; ?> ms)
                    </p>
                </div>
            <?php elseif ($query_result === 'select'): ?>
                <div class="glass-card rounded-3xl p-6 border border-slate-800/80 shadow-2xl space-y-4">
                    
                    <!-- RESULT STATS & EXPORT -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-table-list text-indigo-400"></i> ผลลัพธ์ข้อมูล (Query Results)
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 text-[11px] font-semibold border border-slate-700">
                                พบทั้งหมด <?php echo count($rows); ?> รายการ
                            </span>
                            <span class="text-slate-500 text-[11px]">
                                (<?php echo $execution_time; ?> ms)
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="exportTableToCSV('query_results.csv')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium text-xs transition border border-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-csv text-emerald-400"></i> Export เป็น CSV
                            </button>
                            <button type="button" onclick="window.print()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium text-xs transition border border-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-print text-indigo-400"></i> พิมพ์รายงาน
                            </button>
                        </div>
                    </div>

                    <!-- DATA TABLE -->
                    <?php if (count($rows) > 0): ?>
                        <div class="overflow-x-auto rounded-2xl border border-slate-800">
                            <table class="w-full text-left border-collapse text-xs" id="resultsTable">
                                <thead>
                                    <tr class="bg-slate-900/90 text-slate-400 font-semibold border-b border-slate-800">
                                        <th class="py-3 px-4 w-12 text-center text-slate-600">#</th>
                                        <?php foreach ($columns as $col): ?>
                                            <th class="py-3 px-4 tracking-wider uppercase whitespace-nowrap text-indigo-300">
                                                <?php echo htmlspecialchars($col); ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60 font-mono">
                                    <?php foreach ($rows as $index => $r): ?>
                                        <tr class="hover:bg-indigo-600/5 transition">
                                            <td class="py-2.5 px-4 text-center text-slate-600 text-[11px] font-sans">
                                                <?php echo $index + 1; ?>
                                            </td>
                                            <?php foreach ($columns as $col): 
                                                $val = $r[$col];
                                            ?>
                                                <td class="py-2.5 px-4 text-slate-200 whitespace-nowrap">
                                                    <?php if ($val === null): ?>
                                                        <span class="text-slate-600 italic">NULL</span>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($val); ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="py-12 text-center text-slate-500">
                            <i class="fa-solid fa-box-open text-3xl mb-2 text-slate-600"></i>
                            <p class="text-xs">คำสั่ง SQL ทำงานสำเร็จ แต่ไม่พบข้อมูลที่ตรงกับเงื่อนไข (0 แถว)</p>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- JAVASCRIPT PRESET SCRIPTS -->
    <script>
        const queryPresets = {
            1: `SELECT 
    DATE(o.order_date) AS order_day,
    COUNT(o.order_id) AS total_orders,
    SUM(o.total_amount) AS total_revenue,
    ROUND(AVG(o.total_amount), 2) AS avg_order_value
FROM orders o
WHERE o.status = 'approved'
GROUP BY DATE(o.order_date)
ORDER BY order_day DESC;`,

            2: `SELECT 
    e.ebook_id,
    e.title AS book_title,
    a.author_name,
    c.category_name,
    COUNT(oi.order_item_id) AS copies_sold,
    SUM(oi.price_at_purchase) AS total_sales_amount
FROM order_items oi
JOIN orders o ON oi.order_id = o.order_id
JOIN ebooks e ON oi.ebook_id = e.ebook_id
LEFT JOIN authors a ON e.author_id = a.author_id
LEFT JOIN categories c ON e.category_id = c.category_id
WHERE o.status = 'approved'
GROUP BY e.ebook_id, e.title, a.author_name, c.category_name
ORDER BY copies_sold DESC, total_sales_amount DESC
LIMIT 5;`,

            3: `SELECT 
    c.category_id,
    c.category_name,
    COUNT(DISTINCT e.ebook_id) AS total_books_in_category,
    COUNT(oi.order_item_id) AS total_items_sold,
    SUM(oi.price_at_purchase) AS category_revenue,
    ROUND((SUM(oi.price_at_purchase) / (SELECT SUM(total_amount) FROM orders WHERE status = 'approved')) * 100, 2) AS revenue_percentage
FROM categories c
LEFT JOIN ebooks e ON c.category_id = e.category_id
LEFT JOIN order_items oi ON e.ebook_id = oi.ebook_id
LEFT JOIN orders o ON oi.order_id = o.order_id AND o.status = 'approved'
GROUP BY c.category_id, c.category_name
ORDER BY category_revenue DESC;`,

            4: `SELECT 
    u.user_id,
    u.name AS customer_name,
    u.email,
    COUNT(o.order_id) AS approved_orders_count,
    SUM(o.total_amount) AS total_spent,
    ROUND(AVG(o.total_amount), 2) AS avg_spent_per_order
FROM users u
JOIN orders o ON u.user_id = o.user_id
WHERE o.status = 'approved'
GROUP BY u.user_id, u.name, u.email
HAVING COUNT(o.order_id) >= 2
ORDER BY total_spent DESC;`,

            5: `SELECT 
    o.order_id,
    u.name AS customer_name,
    o.total_amount,
    o.status,
    p.payment_method,
    p.slip_url,
    o.order_date
FROM orders o
LEFT JOIN users u ON o.user_id = u.user_id
LEFT JOIN payments p ON o.order_id = p.order_id
ORDER BY o.order_id DESC
LIMIT 30;`,

            6: `SELECT 
    e.ebook_id,
    e.title,
    a.author_name,
    c.category_name,
    e.price,
    e.is_active,
    e.created_at
FROM ebooks e
LEFT JOIN authors a ON e.author_id = a.author_id
LEFT JOIN categories c ON e.category_id = c.category_id
ORDER BY e.ebook_id ASC;`,

            7: `SHOW TABLES;`,

            8: `SELECT 
    u.user_id,
    u.name,
    u.email,
    u.role,
    u.created_at,
    (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) AS order_count
FROM users u
ORDER BY u.user_id ASC;`
        };

        function setQuery(id) {
            if (queryPresets[id]) {
                document.getElementById('sql_query').value = queryPresets[id];
                document.getElementById('sqlForm').submit();
            }
        }

        function clearQuery() {
            document.getElementById('sql_query').value = '';
            document.getElementById('sql_query').focus();
        }

        function copyQuery() {
            const queryText = document.getElementById('sql_query').value;
            navigator.clipboard.writeText(queryText).then(() => {
                alert('คัดลอกคำสั่ง SQL เรียบร้อยแล้ว!');
            });
        }

        function exportTableToCSV(filename) {
            const table = document.getElementById("resultsTable");
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll("tr");

            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                // ข้ามคอลัมน์ลำดับที่ 0 (#)
                for (let j = 1; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(","));
            }

            const csvFile = new Blob(["\uFEFF" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
            const downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
