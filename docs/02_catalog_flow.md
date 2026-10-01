# 02. ขั้นตอนการเลือกดูและค้นหาหนังสือ (Catalog Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [index.php](file:///c:/xampp/htdocs/ebook-store/index.php) : หน้าแรก แสดงหนังสือทั้งหมด, ตัวกรองหมวดหมู่ และช่องค้นหา
- [book_detail.php](file:///c:/xampp/htdocs/ebook-store/book_detail.php) : หน้ารายละเอียดหนังสือ

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
graph TD
    A[ผู้ใช้เข้าหน้าแรก index.php] --> B{ค้นหา / กรองหมวดหมู่?}
    B -- มีคำค้นหา --> C[SELECT * FROM books WHERE title LIKE ? OR category = ?]
    B -- ไม่มี --> D[SELECT * FROM books ORDER BY id DESC]
    C --> E[แสดงรายการการ์ดหนังสือ]
    D --> E
    E --> F[ผู้ใช้คลิกดูหนังสือเล่มที่สนใจ]
    F --> G[เปิดหน้า book_detail.php?id=X]
    G --> H[ดึงข้อมูลหนังสือ, เรื่องย่อ, ราคา, ปก]
    H --> I{ผู้ใช้กดปุ่ม}
    I -- เพิ่มลงตะกร้า --> J[ส่งคำขอ AJAX หรือ Form ไปยัง cart.php]
    I -- ซื้อทันที --> K[เพิ่มเข้าตะกร้าและไปที่หน้า checkout.php]
```
