# 05. ขั้นตอนการจัดการหนังสือของผู้ดูแลระบบ (Admin Books Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [admin_books.php](file:///c:/xampp/htdocs/ebook-store/admin_books.php) : ตารางรายการหนังสือทั้งหมด, ปุ่มเพิ่ม/แก้ไข/ลบ
- [add_book.php](file:///c:/xampp/htdocs/ebook-store/add_book.php) : ฟอร์มเพิ่มหนังสือใหม่ + อัปโหลดหน้าปก & ไฟล์ PDF
- [edit_book.php](file:///c:/xampp/htdocs/ebook-store/edit_book.php) : ฟอร์มแก้ไขข้อมูลหนังสือ

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as ผู้ดูแลระบบ
    participant AdminBooks as admin_books.php
    participant AddEdit as add_book.php / edit_book.php
    participant Storage as uploads/ (covers & pdfs)
    participant DB as ฐานข้อมูล MySQL

    Admin->>AdminBooks: เปิดหน้ารายการหนังสือ
    alt เพิ่มหนังสือใหม่
        Admin->>AddEdit: เข้าหน้า add_book.php กรอกชื่อ, ราคา, รายละเอียด, หมวดหมู่
        Admin->>AddEdit: เลือกไฟล์รูปปก (.jpg/.png) และไฟล์เนื้อหา (.pdf)
        AddEdit->>Storage: ตรวจสอบและย้ายไฟล์ไปยัง uploads/covers/ และ uploads/pdfs/
        AddEdit->>DB: INSERT INTO books (...)
        AddEdit-->>AdminBooks: บันทึกสำเร็จ กลับสู่หน้ารายการหนังสือ
    else แก้ไขหนังสือ
        Admin->>AddEdit: คลิกแก้ไข edit_book.php?id=X
        Admin->>AddEdit: แก้ไขข้อความ และเลือกเปลี่ยนไฟล์ปก/PDF (ถ้าต้องการ)
        AddEdit->>DB: UPDATE books SET ... WHERE id = X
        AddEdit-->>AdminBooks: อัปเดตข้อมูลสำเร็จ
    else ลบหนังสือ
        Admin->>AdminBooks: กดปุ่มลบหนังสือ
        AdminBooks->>DB: DELETE FROM books WHERE id = X
        AdminBooks-->>Admin: แสดงผลรายการที่อัปเดต
    end
```
