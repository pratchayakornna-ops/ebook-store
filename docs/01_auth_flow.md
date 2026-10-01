# 01. ขั้นตอนระบบยืนยันตัวตน (Authentication Flow)

## 📌 ไฟล์ที่เกี่ยวข้อง
- [register.php](file:///c:/xampp/htdocs/ebook-store/register.php) : หน้าสมัครสมาชิก
- [login.php](file:///c:/xampp/htdocs/ebook-store/login.php) : หน้าเข้าสู่ระบบ
- [logout.php](file:///c:/xampp/htdocs/ebook-store/logout.php) : หน้าออกจากระบบ
- [profile.php](file:///c:/xampp/htdocs/ebook-store/profile.php) : หน้าดูและแก้ไขข้อมูลส่วนตัว

---

## 🔄 ลำดับขั้นตอนการทำงาน (Flow Steps)

```mermaid
sequenceDiagram
    autonumber
    actor User as ผู้ใช้งาน (User)
    participant Register as register.php
    participant Login as login.php
    participant DB as ฐานข้อมูล MySQL
    participant Session as PHP Session

    Note over User,DB: การสมัครสมาชิก (Registration)
    User->>Register: กรอกข้อมูล (ชื่อ, อีเมล, รหัสผ่าน)
    Register->>DB: ตรวจสอบอีเมลซ้ำ
    alt อีเมลซ้ำ
        Register-->>User: แจ้งเตือนอีเมลนี้ถูกใช้งานแล้ว
    else อีเมลไม่ซ้ำ
        Register->>DB: INSERT ข้อมูล (รหัสผ่านเข้ารหัส password_hash)
        Register-->>User: สมัครสำเร็จ นำทางไปหน้า Login
    end

    Note over User,Session: การเข้าสู่ระบบ (Login)
    User->>Login: กรอกอีเมลและรหัสผ่าน
    Login->>DB: ค้นหาผู้ใช้จากอีเมล
    Login->>Login: ตรวจสอบ password_verify()
    alt รหัสผ่านถูกต้อง
        Login->>Session: บันทึก user_id, username, role ('user'/'admin')
        Login-->>User: เปลี่ยนหน้าไปยังหน้าแรก หรือ Dashboard (ถ้าเป็น Admin)
    else รหัสผ่านไม่ถูกต้อง
        Login-->>User: แสดงข้อความแจ้งเตือนข้อผิดพลาด
    end
```

---

## 🔒 การควบคุมสิทธิ์ (Access Control)
- ฟังก์ชัน `isLoggedIn()` ตรวจสอบค่า `$_SESSION['user_id']`
- ฟังก์ชัน `isAdmin()` ตรวจสอบค่า `$_SESSION['role'] === 'admin'`
