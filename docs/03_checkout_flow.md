# 03. ขั้นตอนตะกร้าสินค้าและการสั่งซื้อ/ชำระเงิน (Cart & Checkout Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [cart.php](file:///c:/xampp/htdocs/ebook-store/cart.php) : จัดการสินค้าในตะกร้า (เก็บใน Session `$_SESSION['cart']`)
- [checkout.php](file:///c:/xampp/htdocs/ebook-store/checkout.php) : คำนวณยอดเงินรวม, แสดง QR Code พร้อมเพย์, อัปโหลดสลิปหลักฐาน

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
sequenceDiagram
    autonumber
    actor Customer as ลูกค้า
    participant Cart as cart.php
    participant Checkout as checkout.php
    participant Uploads as uploads/slips/
    participant DB as ฐานข้อมูล MySQL

    Customer->>Cart: เพิ่มหนังสือเข้าตะกร้า ($_SESSION['cart'])
    Customer->>Cart: ตรวจสอบรายการและกดยืนยันชำระเงิน
    Cart->>Checkout: เปลี่ยนเส้นทางไปยังหน้า checkout.php
    Checkout-->>Customer: แสดงยอดรวม และ QR Code พร้อมเพย์
    Customer->>Checkout: อัปโหลดรูปภาพสลิปโอนเงิน (Slip Image)
    Checkout->>Uploads: บันทึกไฟล์สลิปลงโฟลเดอร์ uploads/slips/
    Checkout->>DB: INSERT ลงตาราง orders (สถานะ: 'pending')
    Checkout->>DB: INSERT รายการหนังสือลงตาราง order_items
    Checkout->>Cart: เคลียร์สินค้าใน Session ตะกร้า unset($_SESSION['cart'])
    Checkout-->>Customer: เปลี่ยนเส้นทางไปหน้า orders.php แจ้ง "รอผู้ดูแลระบบตรวจสอบ"
```
