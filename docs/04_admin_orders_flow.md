# 04. ขั้นตอนการตรวจสอบและอนุมัติคำสั่งซื้อ (Admin Orders Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [admin_orders.php](file:///c:/xampp/htdocs/ebook-store/admin_orders.php) : หน้าตรวจสอบคำสั่งซื้อ สลิป และปรับสถานะ
- [admin_dashboard.php](file:///c:/xampp/htdocs/ebook-store/admin_dashboard.php) : สรุปสถิติคำสั่งซื้อที่รอตรวจสอบ

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
graph TD
    A[Admin เข้าสู่ระบบ] --> B[เข้าหน้า admin_orders.php]
    B --> C[ดึงรายการคำสั่งซื้อทั้งหมดที่สถานะ = pending]
    C --> D[Admin คลิกดูรูปภาพสลิปที่ลูกค้าแนบมา]
    D --> E{ผลการตรวจสอบสลิป}
    E -- ยอดเงินถูกต้อง --> F[กดปุ่ม 'อนุมัติ' (Approve)]
    E -- ยอดเงินไม่ถูกต้อง / สลิปปลอม --> G[กดปุ่ม 'ปฏิเสธ' (Reject)]
    F --> H[UPDATE orders SET status='completed' WHERE id=?]
    G --> I[UPDATE orders SET status='rejected' WHERE id=?]
    H --> J[หนังสือจะปรากฏในหน้า 'ชั้นหนังสือของฉัน' (my_books.php) ของลูกค้าทันที]
    I --> K[แจ้งสถานะถูกปฏิเสธในหน้า orders.php ของลูกค้า]
```
