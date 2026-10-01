# 06. ขั้นตอนการเข้าอ่าน E-Book และชั้นหนังสือของฉัน (Reader Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [my_books.php](file:///c:/xampp/htdocs/ebook-store/my_books.php) : หน้าชั้นหนังสือส่วนตัว แสดงเฉพาะหนังสือที่ซื้อสำเร็จแล้ว
- [read.php](file:///c:/xampp/htdocs/ebook-store/read.php) : ตัวเปิดอ่าน E-Book แบบออนไลน์ (PDF Viewer / Reader)
- [orders.php](file:///c:/xampp/htdocs/ebook-store/orders.php) : หน้าดูประวัติคำสั่งซื้อทั้งหมด

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
sequenceDiagram
    autonumber
    actor Customer as ลูกค้า
    participant MyBooks as my_books.php
    participant Reader as read.php
    participant DB as ฐานข้อมูล MySQL
    participant PDF as ไฟล์ PDF ใน uploads/pdfs/

    Customer->>MyBooks: เข้าหน้ารายการ 'หนังสือของฉัน'
    MyBooks->>DB: ดึงรายการหนังสือจาก orders (status = 'completed') ของ user_id ปัจจุบัน
    MyBooks-->>Customer: แสดงรายการหนังสือทั้งหมดที่ครอบครอง
    Customer->>MyBooks: คลิกปุ่ม "อ่านหนังสือ" (Open Reader)
    MyBooks->>Reader: ส่งต่อไปยัง read.php?id=X
    Reader->>DB: ตรวจสอบสิทธิ์ว่าผู้ใช้รายนี้ซื้อหนังสือเล่มนี้จริงหรือไม่
    alt ไม่มีสิทธิ์ครอบครอง
        Reader-->>Customer: แจ้งเตือนสิทธิ์ไม่ถูกต้อง และเด้งกลับ
    else มีสิทธิ์ถูกต้อง
        Reader->>PDF: โหลดไฟล์ PDF มาแสดงผลในหน้าเว็บ
        Reader-->>Customer: แสดงหน้าต่างโปรแกรมอ่าน E-Book พร้อมฟังก์ชันเปลี่ยนหน้า/ซูม
    end
```
