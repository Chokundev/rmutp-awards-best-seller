
# 🏆 RMUTP AWARDS 2026: Best Seller Portal

> ระบบจัดการและยืนยันยอดจำหน่ายสินค้า สำหรับผู้เข้าประกวด RMUTP AWARDS 2026 สะดวก รวดเร็ว และตรวจสอบได้แบบเรียลไทม์

## 📋 เกี่ยวกับโปรเจ็กต์ (About The Project)
โปรเจ็กต์นี้ถูกพัฒนาขึ้นเพื่อเป็นศูนย์กลาง (Portal) ในการจัดการระบบ "Best Seller Campaign" ของการประกวด RMUTP AWARDS โดยเน้นไปที่การทำงานของผู้ดูแลระบบ (Admin) เพื่ออำนวยความสะดวกในการตรวจสอบสลิปโอนเงิน การจัดการข้อมูลผู้เข้าประกวด และการดูสถิติยอดขายแบบ Real-time ผ่านแดชบอร์ดที่มีประสิทธิภาพ

## ✨ ฟีเจอร์หลัก (Key Features)
* **📊 แดชบอร์ดภาพรวม (Overview Dashboard):** สรุปยอดขายรวม จำนวนรายการรอตรวจสอบ และสถิติการเข้าสู่ระบบแบบเรียลไทม์
* **📈 การวิเคราะห์ข้อมูล (Data Analytics):** แสดงกราฟแนวโน้มรายได้ 7 วันย้อนหลัง และสัดส่วนผลิตภัณฑ์ที่จำหน่ายได้ (ใช้ Chart.js)
* **⏱️ บันทึกความเคลื่อนไหว (Live Activity Feed):** ติดตามสถานะการเข้าสู่ระบบและการส่งสลิปของผู้เข้าประกวดได้ทันที
* **✅ ตรวจสอบสลิป (Slip Verification):** ระบบจัดการและอนุมัติสลิปโอนเงิน (รอตรวจสอบ / อนุมัติแล้ว / ปฏิเสธ)
* **📋 ระบบรายงาน (Reports):** สร้างรายงานการจัดอันดับ Top 10, รายงานรายบุคคล และรายงานแยกตามผลิตภัณฑ์
* **🌟 หน้าจอแสดงผลสด (Live Score Overlay):** หน้าจอจัดอันดับยอดขายสำหรับใช้งานบนจอ LED หรือการถ่ายทอดสด

## 🛠 เทคโนโลยีที่ใช้ (Built With)
* **Frontend:**
  * HTML5 / CSS3
  * [Tailwind CSS](https://tailwindcss.com/) (สำหรับจัดการ UI และ Layout)
  * [Chart.js](https://www.chartjs.org/) (สำหรับสร้างกราฟสถิติ)
  * [AOS Animation](https://michalsnik.github.io/aos/) (สำหรับแอนิเมชันเลื่อนหน้าจอ)
  * ฟอนต์ [Prompt](https://fonts.google.com/specimen/Prompt) (Google Fonts)
* **Backend:**
  * PHP
  * PDO (PHP Data Objects) สำหรับเชื่อมต่อฐานข้อมูล MySQL[cite: 1]

## 📁 โครงสร้างโปรเจ็กต์ (Directory Structure)
โครงสร้างไดเรกทอรีหลักของระบบจัดเก็บไว้ดังนี้ :

```text
rmutp-awards-best-seller/
├── admin/                        # พื้นที่สำหรับผู้ดูแลระบบ
│   ├── api/                      # ระบบจัดการ API หลังบ้าน
│   │   ├── auth_admin.php        # API ยืนยันตัวตนแอดมิน
│   │   ├── contestant_api.php    # API จัดการข้อมูลผู้เข้าประกวด
│   │   ├── settings_api.php      # API จัดการการตั้งค่า
│   │   └── slip_api.php          # API จัดการสถานะสลิป
│   ├── admin_login_logs.php      # ประวัติการใช้งานของแอดมิน
│   ├── bestseller_overlay.php    # หน้าจอแสดงผลคะแนนสด (OBS/LED)
│   ├── dashboard.php             # แดชบอร์ดหลัก
│   ├── index.php                 # หน้าเข้าสู่ระบบ
│   ├── login_analytics.php       # สถิติการเข้าสู่ระบบ
│   ├── login_logs.php            # ประวัติการเข้าสู่ระบบของผู้ใช้งาน
│   ├── logout.php                # ออกจากระบบ
│   ├── manage_contestants.php    # จัดการผู้เข้าประกวด
│   ├── report_individual.php     # รายงานรายบุคคล
│   ├── report_products.php       # รายงานแยกตามผลิตภัณฑ์
│   ├── reports.php               # รายงานภาพรวม
│   ├── settings.php              # ตั้งค่าระบบ
│   ├── sidebar.php               # เมนูด้านข้าง (Navigation)
│   └── verify_slips.php          # ตรวจสอบสลิปโอนเงิน
├── assets/                       # ไฟล์ทรัพยากร (Static Files)
│   └── images/                   # รูปภาพที่ใช้ในระบบ (เช่น logo.png, product-gq.png)
├── .gitattributes                # การตั้งค่า Git
└── LICENSE                       # สิทธิ์การใช้งานของโปรเจ็กต์

```

## 🚀 การติดตั้งและใช้งานเบื้องต้น (Getting Started)

1.  **Clone Repository**
    
      
    
    Bash
    
    ```
    git clone [https://github.com/chokundev/rmutp-awards-best-seller.git](https://github.com/chokundev/rmutp-awards-best-seller.git)
    
    ```
    
2.  **ตั้งค่าฐานข้อมูล (Database Configuration)**
    
      
    -   นำเข้าไฟล์ฐานข้อมูล SQL (ถ้ามี) ไปยัง MySQL ของคุณ
        
          
        
    -   ตรวจสอบและตั้งค่าการเชื่อมต่อฐานข้อมูลในไฟล์ `config.php` (ซึ่งอยู่ใน Root Directory)
        
          
        
3.  **การรันระบบ (Run Application)**
    
      
    -   นำโปรเจ็กต์ไปวางในโฟลเดอร์ Web Server (เช่น `htdocs` สำหรับ XAMPP หรือ `www` สำหรับ MAMP)
        
          
        
    -   เปิดเบราว์เซอร์และเข้าไปที่ `http://localhost/rmutp-awards-best-seller/admin/index.php`
        

