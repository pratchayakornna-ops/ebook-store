<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ Admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$current_page = 'orders';
$msg = '';
$msg_type = 'success';

// ระบบเปลี่ยนสถานะ (อนุมัติ / ปฏิเสธ / ลบ)
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'approved' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $msg = "อนุมัติคำสั่งซื้อ #ORD-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " เรียบร้อยแล้ว (ผู้ซื้อสามารถเปิดอ่านหนังสือได้ทันที)";
            $msg_type = 'success';
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'rejected' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $msg = "ปฏิเสธคำสั่งซื้อ #ORD-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " เรียบร้อยแล้ว";
            $msg_type = 'warning';
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $msg = "ลบคำสั่งซื้อ #ORD-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " เรียบร้อยแล้ว";
            $msg_type = 'success';
        }
    }
}

// กรองตามสถานะ
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email, p.payment_method, p.slip_url, p.payment_date 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN payments p ON o.order_id = p.order_id 
        WHERE 1=1";

if ($filter_status !== 'all' && in_array($filter_status, ['pending', 'approved', 'rejected', 'cancelled'])) {
    $sql .= " AND o.status = '" . $conn->real_escape_string($filter_status) . "'";
}

$sql .= " ORDER BY o.order_id DESC";
$orders_result = $conn->query($sql);

$orders = [];
if ($orders_result) {
    while ($ord = $orders_result->fetch_assoc()) {
        $items_sql = "SELECT oi.*, e.title, e.cover_image 
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
        $orders[] = $ord;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการและอนุมัติคำสั่งซื้อ // NEXTREAD Admin</title>
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
                    <i class="fa-solid fa-receipt text-indigo-400"></i> จัดการคำสั่งซื้อและอนุมัติสลิป
                </h1>
                <p class="text-xs text-slate-400 mt-1">ตรวจสอบหลักฐานการโอนเงิน และอนุมัติสิทธิ์การอ่าน E-Book ให้ลูกค้า</p>
            </div>

            <!-- STATUS FILTER TABS -->
            <div class="flex items-center gap-1.5 bg-slate-900/80 p-1.5 rounded-2xl border border-slate-800 text-xs self-start sm:self-auto overflow-x-auto shadow-inner">
                <a href="admin_orders.php?status=all" class="px-3.5 py-1.5 rounded-xl transition <?php echo ($filter_status === 'all') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'; ?>">
                    ทั้งหมด
                </a>
                <a href="admin_orders.php?status=pending" class="px-3.5 py-1.5 rounded-xl transition <?php echo ($filter_status === 'pending') ? 'bg-amber-500 text-slate-950 font-bold shadow-md shadow-amber-500/30' : 'text-slate-400 hover:text-white'; ?>">
                    รอตรวจสอบ
                </a>
                <a href="admin_orders.php?status=approved" class="px-3.5 py-1.5 rounded-xl transition <?php echo ($filter_status === 'approved') ? 'bg-emerald-600 text-white font-medium shadow-md shadow-emerald-600/30' : 'text-slate-400 hover:text-white'; ?>">
                    อนุมัติแล้ว
                </a>
                <a href="admin_orders.php?status=rejected" class="px-3.5 py-1.5 rounded-xl transition <?php echo ($filter_status === 'rejected') ? 'bg-rose-600 text-white font-medium shadow-md shadow-rose-600/30' : 'text-slate-400 hover:text-white'; ?>">
                    ปฏิเสธ
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="p-4 rounded-2xl text-xs flex items-center justify-between border shadow-lg <?php 
                echo ($msg_type == 'success') ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (($msg_type == 'warning') ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400'); 
            ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> text-base"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- ORDERS TABLE -->
        <div class="glass-card rounded-3xl p-5 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800/80 uppercase text-[10px] tracking-wider">
                            <th class="py-3.5 px-4">รหัส / วันที่</th>
                            <th class="py-3.5 px-4">ลูกค้า</th>
                            <th class="py-3.5 px-4">รายการ E-Book</th>
                            <th class="py-3.5 px-4">ยอดชำระ / สลิป</th>
                            <th class="py-3.5 px-4">สถานะ</th>
                            <th class="py-3.5 px-4 text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (!empty($orders)): 
                            $ord_row = 0;
                        ?>
                            <?php foreach ($orders as $ord): 
                                $ord_row++;
                                $order_id = $ord['order_id'];
                                $status = $ord['status'] ?? 'pending';
                                $total = $ord['total_amount'] ?? 0;
                                $cust_name = $ord['customer_name'] ?? 'User #'.$ord['user_id'];
                                $cust_email = $ord['customer_email'] ?? '';
                                $date = $ord['order_date'] ? date('d/m/Y H:i', strtotime($ord['order_date'])) : '-';
                                $slip = $ord['slip_url'] ?? '';
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <!-- ID & Date -->
                                    <td class="py-4 px-4 align-top">
                                        <span class="font-bold text-slate-200 text-sm block">#ORD-<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?></span>
                                        <span class="text-[10px] text-slate-400"><?php echo $date; ?></span>
                                    </td>

                                    <!-- Customer -->
                                    <td class="py-4 px-4 align-top">
                                        <span class="font-semibold text-white block"><?php echo htmlspecialchars($cust_name); ?></span>
                                        <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($cust_email); ?></span>
                                    </td>

                                    <!-- Items -->
                                    <td class="py-4 px-4 align-top max-w-xs">
                                        <?php if (!empty($ord['items'])): ?>
                                            <ul class="space-y-1">
                                                <?php foreach ($ord['items'] as $it): ?>
                                                    <li class="text-slate-300 truncate flex items-center gap-1.5" title="<?php echo htmlspecialchars($it['title']); ?>">
                                                        <i class="fa-solid fa-book text-[10px] text-indigo-400"></i>
                                                        <span><?php echo htmlspecialchars($it['title']); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-slate-500">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Amount & Slip -->
                                    <td class="py-4 px-4 align-top">
                                        <span class="font-bold text-emerald-400 text-sm block">฿<?php echo number_format($total, 2); ?></span>
                                        <span class="text-[10px] text-slate-400 block mb-1"><?php echo htmlspecialchars($ord['payment_method'] ?? 'PromptPay'); ?></span>
                                        
                                        <?php if (!empty($slip)): ?>
                                            <button onclick="openSlipModal('<?php echo htmlspecialchars($slip); ?>', '<?php echo $order_id; ?>')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 hover:bg-indigo-500/20 text-[10px] transition shadow-sm">
                                                <i class="fa-solid fa-image"></i> ดูสลิปโอนเงิน
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-500 italic">ไม่มีรูปสลิป</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-4 px-4 align-top">
                                        <?php if ($status === 'pending'): ?>
                                            <span class="px-2.5 py-1 text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/30 rounded-full font-medium inline-block shadow-sm">
                                                <i class="fa-solid fa-hourglass-half mr-1 animate-spin"></i> รอตรวจสอบ
                                            </span>
                                        <?php elseif ($status === 'approved'): ?>
                                            <span class="px-2.5 py-1 text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-full font-medium inline-block shadow-sm">
                                                <i class="fa-solid fa-circle-check mr-1"></i> อนุมัติแล้ว
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 rounded-full font-medium inline-block shadow-sm">
                                                <i class="fa-solid fa-circle-xmark mr-1"></i> ปฏิเสธ
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-4 align-top text-right space-y-1 sm:space-y-0 sm:space-x-1">
                                        <?php if ($status !== 'approved'): ?>
                                            <a href="admin_orders.php?action=approve&order_id=<?php echo $order_id; ?>&status=<?php echo $filter_status; ?>" 
                                               onclick="return confirm('ยืนยันอนุมัติคำสั่งซื้อ #ORD-<?php echo $order_id; ?> ใช่หรือไม่?')" 
                                               class="px-2.5 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white font-medium transition inline-flex items-center gap-1 shadow-sm"
                                               title="อนุมัติคำสั่งซื้อ">
                                                <i class="fa-solid fa-check"></i> อนุมัติ
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($status !== 'rejected'): ?>
                                            <a href="admin_orders.php?action=reject&order_id=<?php echo $order_id; ?>&status=<?php echo $filter_status; ?>" 
                                               onclick="return confirm('ยืนยันปฏิเสธคำสั่งซื้อ #ORD-<?php echo $order_id; ?> ใช่หรือไม่?')" 
                                               class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500 hover:text-slate-950 font-medium transition inline-flex items-center gap-1 shadow-sm"
                                               title="ปฏิเสธคำสั่งซื้อ">
                                                <i class="fa-solid fa-xmark"></i> ปฏิเสธ
                                            </a>
                                        <?php endif; ?>

                                        <a href="admin_orders.php?action=delete&order_id=<?php echo $order_id; ?>&status=<?php echo $filter_status; ?>" 
                                           onclick="return confirm('ยืนยันลบคำสั่งซื้อ #ORD-<?php echo $order_id; ?> ถาวร?')" 
                                           class="px-2 py-1.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white font-medium transition inline-flex items-center shadow-sm"
                                           title="ลบคำสั่งซื้อ">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-receipt text-4xl mb-3 text-slate-600 block"></i>
                                    <p class="text-sm font-medium text-slate-300">ไม่พบรายการคำสั่งซื้อตามสถานะนี้</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- SLIP PREVIEW MODAL -->
    <div id="slipModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden items-center justify-center p-4 transition duration-300">
        <div class="glass-card max-w-md w-full rounded-3xl p-6 relative border border-slate-700 shadow-2xl">
            <button onclick="closeSlipModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 id="slipTitle" class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-image text-indigo-400"></i> หลักฐานการโอนเงิน (สลิป)
            </h3>
            <div class="rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center max-h-[70vh]">
                <img id="slipImage" src="" alt="Slip" class="w-full h-auto object-contain">
            </div>
        </div>
    </div>

    <script>
        function openSlipModal(url, orderId) {
            document.getElementById('slipImage').src = url;
            document.getElementById('slipTitle').innerHTML = '<i class="fa-solid fa-image text-indigo-400"></i> สลิปคำสั่งซื้อ #ORD-' + orderId;
            const modal = document.getElementById('slipModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSlipModal() {
            const modal = document.getElementById('slipModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>

</body>
</html>