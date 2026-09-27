<?php
require_once 'config.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// 2. ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลหนังสือในตะกร้า
$cart_ids = array_map('intval', array_keys($_SESSION['cart']));
$ids_string = implode(',', $cart_ids);

$cart_items = [];
$total_price = 0;

if (!empty($cart_ids)) {
    $result = $conn->query("SELECT e.*, a.author_name, c.category_name 
                            FROM ebooks e 
                            LEFT JOIN authors a ON e.author_id = a.author_id 
                            LEFT JOIN categories c ON e.category_id = c.category_id 
                            WHERE e.ebook_id IN ($ids_string)");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $qty = 1; // สำหรับ E-Book ซื้อ 1 เล่ม
            $subtotal = floatval($row['price']);
            $total_price += $subtotal;
            
            $row['qty'] = $qty;
            $row['subtotal'] = $subtotal;
            $cart_items[] = $row;
        }
    }
}

if (empty($cart_items)) {
    header("Location: index.php");
    exit();
}

$error_msg = '';

// 3. เมื่อส่งแบบฟอร์มยืนยันการชำระเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $payment_method = trim($_POST['payment_method'] ?? 'PromptPay QR Code');
    $slip_path = '';

    // จัดการอัปโหลดสลิปโอนเงิน (ถ้ามี)
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/slips/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'slip_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['slip']['tmp_name'], $target_file)) {
                $slip_path = $target_file;
            }
        }
    }

    // เริ่ม Transaction บันทึกลงฐานข้อมูล
    $conn->begin_transaction();

    try {
        // 1. บันทึกลงตาราง orders
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, order_date) VALUES (?, ?, 'pending', NOW())");
        $stmt_order->bind_param("id", $user_id, $total_price);
        $stmt_order->execute();
        $order_id = $stmt_order->insert_id;
        $stmt_order->close();

        // 2. บันทึกลงตาราง order_items (ตารางหลัก)
        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, ebook_id, price_at_purchase) VALUES (?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt_items->bind_param("iid", $order_id, $item['ebook_id'], $item['price']);
            $stmt_items->execute();
        }
        $stmt_items->close();

        // 3. บันทึกลงตาราง order_details (สำรองเพื่อความเข้ากันได้)
        try {
            $stmt_details = $conn->prepare("INSERT INTO order_details (order_id, ebook_id, price) VALUES (?, ?, ?)");
            if ($stmt_details) {
                foreach ($cart_items as $item) {
                    $stmt_details->bind_param("iid", $order_id, $item['ebook_id'], $item['price']);
                    $stmt_details->execute();
                }
                $stmt_details->close();
            }
        } catch (Exception $e_det) {}

        // 4. บันทึกลงตาราง payments
        $stmt_pay = $conn->prepare("INSERT INTO payments (order_id, payment_method, slip_url, payment_date) VALUES (?, ?, ?, NOW())");
        $stmt_pay->bind_param("iss", $order_id, $payment_method, $slip_path);
        $stmt_pay->execute();
        $stmt_pay->close();

        // ยืนยันการบันทึกข้อมูล
        $conn->commit();

        // ล้างตะกร้าสินค้า
        unset($_SESSION['cart']);

        // ส่งต่อไปยังหน้าแสดงรายการสั่งซื้อสำเร็จ
        header("Location: orders.php?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = 'เกิดข้อผิดพลาดในการทำรายการ: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงินและยืนยันคำสั่งซื้อ // NEXTREAD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col antialiased py-8 px-4 md:px-8">

    <!-- Ambient 3D Depth Background -->
    <div class="bg-ambient">
        <div class="ambient-orb-1"></div>
        <div class="ambient-orb-2"></div>
        <div class="ambient-orb-3"></div>
    </div>

    <div class="page-container max-w-4xl mx-auto w-full flex-1">
        <!-- TOP BAR -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
            <a href="cart.php" class="text-xs text-slate-400 hover:text-white transition flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700">
                <i class="fa-solid fa-arrow-left"></i> ย้อนกลับไปตะกร้า
            </a>
            <span class="px-3.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-semibold border border-emerald-500/20 flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-lock"></i> ระบบชำระเงินปลอดภัย
            </span>
        </div>

        <?php if ($error_msg): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-12 gap-8">
            
            <!-- LEFT: SUMMARY -->
            <div class="md:col-span-5 space-y-6">
                <div class="glass-card tilt-card rounded-3xl p-6 shadow-2xl">
                    <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <i class="fa-solid fa-receipt text-indigo-400"></i> สรุปรายการสั่งซื้อ
                    </h2>

                    <div class="space-y-3 mb-6 max-h-72 overflow-y-auto pr-1">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="flex justify-between items-center text-xs pb-3 border-b border-slate-800/60 gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-white truncate"><?php echo htmlspecialchars($item['title']); ?></p>
                                    <p class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($item['author_name'] ?? 'ผู้แต่ง'); ?></p>
                                </div>
                                <span class="font-bold text-emerald-400 shrink-0">฿<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-700/80 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>ราคารวมสินค้า (<?php echo count($cart_items); ?> เล่ม)</span>
                            <span>฿<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>ค่าจัดส่ง (E-Book Online)</span>
                            <span class="text-emerald-400">ฟรี</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-slate-800 text-sm">
                            <span class="font-bold text-slate-200">ยอดชำระสุทธิ</span>
                            <span class="text-3xl font-black text-emerald-400">฿<?php echo number_format($total_price, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- USER INFO CARD -->
                <div class="glass-card rounded-2xl p-4 text-xs text-slate-400 space-y-1.5 border border-slate-800">
                    <p class="text-slate-300 font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-user-check text-indigo-400"></i> ข้อมูลผู้สั่งซื้อ
                    </p>
                    <p>ชื่อ: <span class="text-white"><?php echo htmlspecialchars($_SESSION['name'] ?? 'ผู้ใช้งาน'); ?></span></p>
                    <p>อีเมล: <span class="text-white"><?php echo htmlspecialchars($_SESSION['email'] ?? '-'); ?></span></p>
                </div>
            </div>

            <!-- RIGHT: PAYMENT & SLIP UPLOAD -->
            <div class="md:col-span-7 space-y-6">
                <div class="glass-card rounded-3xl p-6 shadow-2xl space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            <i class="fa-solid fa-qrcode text-indigo-400"></i> สแกนจ่ายผ่านพร้อมเพย์ (PromptPay)
                        </h2>
                        <p class="text-xs text-slate-400">สแกน QR Code ผ่าน Mobile Banking ได้ทุกธนาคาร</p>
                    </div>

                    <!-- QR CODE DISPLAY -->
                    <div class="flex flex-col sm:flex-row items-center gap-6 p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-inner">
                        <div class="bg-white p-3 rounded-2xl shrink-0 shadow-xl hover:scale-105 transition duration-300">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=NEXTREAD_PAYMENT_<?php echo $total_price; ?>" 
                                 alt="PromptPay QR Code" class="w-36 h-36">
                        </div>
                        <div class="text-xs space-y-2 text-center sm:text-left">
                            <div>
                                <span class="text-slate-400 text-[11px] block">ชื่อบัญชี</span>
                                <span class="font-bold text-white text-sm">บริษัท เน็กซ์รีด อีบุ๊ค จำกัด</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">ธนาคารกสิกรไทย</span>
                                <span class="font-mono text-indigo-300 font-semibold">123-4-56789-0</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">ยอดชำระที่ต้องโอน</span>
                                <span class="text-xl font-black text-emerald-400">฿<?php echo number_format($total_price, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT METHOD SELECT -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">ช่องทางการชำระเงิน</label>
                        <select name="payment_method" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3 text-xs focus:outline-none focus:border-indigo-500">
                            <option value="PromptPay QR Code">PromptPay QR Code (พร้อมเพย์)</option>
                            <option value="โอนเงินผ่านธนาคารกสิกรไทย">โอนเงินผ่านธนาคารกสิกรไทย (KBANK)</option>
                            <option value="โอนเงินผ่านธนาคารไทยพาณิชย์">โอนเงินผ่านธนาคารไทยพาณิชย์ (SCB)</option>
                            <option value="บัตรเครดิต / เดบิต">บัตรเครดิต / เดบิต (Credit/Debit Card)</option>
                        </select>
                    </div>

                    <!-- SLIP UPLOAD -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            แนบสลิปการโอนเงิน (หลักฐานการชำระเงิน)
                        </label>
                        <p class="text-[11px] text-slate-400 mb-2">อัปโหลดรูปภาพสลิป เพื่อให้เจ้าหน้าที่อนุมัติคำสั่งซื้อได้รวดเร็วขึ้น</p>
                        <input type="file" name="slip" accept="image/*" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 text-xs">
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-check text-sm"></i> ยืนยันการชำระเงินและแจ้งโอน
                    </button>
                </div>
            </div>

        </form>
    </div>

</body>
</html>