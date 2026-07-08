# ระบบบริหารจัดการและเช็กชื่อกิจกรรมนักศึกษา มหาวิทยาลัยราชภัฏสุรินทร์ (SRRU)

ระบบเช็กชื่อกิจกรรมนักศึกษาผ่าน "สามประสาน" (Dynamic QR Code + GPS Geolocation + Selfie) พร้อมระบบบริหารเกณฑ์ชั่วโมงกิจกรรมและออกรายงานพร้อมยื่นจบอัตโนมัติ สำหรับกองพัฒนานักศึกษา

**Tech stack:** Laravel 11 · MySQL · Tailwind CSS v4 · Alpine.js · Laravel Socialite (Google OAuth)

---

## ฟีเจอร์หลัก

### ฝั่งนักศึกษา

- **Login ด้วย Google** จำกัดเฉพาะบัญชีอีเมลโดเมนมหาวิทยาลัย (`@srru.ac.th`) เท่านั้น
- **กรอกโปรไฟล์ครั้งแรก**: คำนำหน้าชื่อ, ชื่อ-นามสกุลแยกช่อง, รหัสนักศึกษา 11 หลัก, ปีที่เข้าศึกษา, ชั้นปีปัจจุบัน (นักศึกษาเลือกเอง ไม่คำนวณอัตโนมัติ เพื่อรองรับกรณีซ้ำชั้น/ลาพัก), ประเภทหลักสูตร (ภาคปกติ/กศ.บป.), คณะ/สาขา (dropdown สาขากรองตามคณะที่เลือกแบบ dynamic)
- **เช็กชื่อกิจกรรมแบบสามประสาน**:
  1. สแกน QR Code ที่แสดงหน้างาน (QR หมุนรหัสอัตโนมัติทุก 15 วินาทีเพื่อป้องกันการแคปภาพไปใช้ซ้ำ)
  2. ตรวจสอบตำแหน่ง GPS ต้องอยู่ในรัศมีที่กำหนดของกิจกรรม
  3. ถ่ายเซลฟียืนยันตัวตน (บังคับใช้กล้องหน้าเท่านั้น ห้ามอัปโหลดจากคลังภาพ)
  - ระบบอนุมัติอัตโนมัติ (`auto_approved`) หากผ่านทุกเงื่อนไข หรือติดธง (`flagged`) ให้แอดมินตรวจสอบหากพบความผิดปกติ (เช่น GPS เกินรัศมี, สงสัยใช้อุปกรณ์ร่วมกัน)
- **แดชบอร์ด "Activity Passport"**: ชั่วโมง/จำนวนกิจกรรมสะสมเทียบเกณฑ์ที่ต้องผ่าน, แยกชั่วโมงตาม 5 หมวดหมู่ (ทำนุบำรุงศิลปวัฒนธรรม, วิชาการ, กีฬาและส่งเสริมสุขภาพ, จิตอาสา, คุณธรรมจริยธรรม), สถานะผ่าน/ไม่ผ่านเกณฑ์แบบเรียลไทม์
- **ยื่นคำร้องกิจกรรมภายนอก**: แนบหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม) ขอเทียบชั่วโมงกิจกรรมที่จัดนอกมหาวิทยาลัย รอแอดมินอนุมัติ/ปฏิเสธ

### ฝั่งกองพัฒนานักศึกษา (Admin)

- **จัดการกิจกรรม**: สร้าง/แก้ไข/ลบ กำหนดระดับ (มหาวิทยาลัย/คณะ), หมวดหมู่ (5 ด้าน), ลักษณะ (บังคับแกน/บังคับเลือก), ปีการศึกษาและภาคเรียน, ปักหมุดตำแหน่ง GPS พร้อมรัศมีที่อนุญาตบนแผนที่ (Leaflet/OpenStreetMap), จำกัดสิทธิ์ผู้เข้าร่วมตามคณะ/สาขา/ชั้นปี
- **ควบคุมหน้างานแบบเรียลไทม์** (Live Event Control): แสดง QR Code หมุนอัตโนมัติ, ตารางผู้เช็กชื่อพร้อมสถานะ/ระยะห่าง GPS, อนุมัติทั้งหมดที่ถูกต้องในคลิกเดียว, บังคับอนุมัติรายที่เลือก (สำหรับกรณี GPS คลาดเคลื่อนทั้งอาคาร), Export ผลเป็น Excel
- **อนุมัติคำร้องกิจกรรมภายนอก**: ดูรายละเอียด+หลักฐานแนบ, อนุมัติ/ปฏิเสธพร้อมระบุเหตุผล
- **ข้อมูลนักศึกษาในระบบ**: ค้นหา/กรองตามชื่อ, รหัสนักศึกษา, คณะ, สาขา (กรองตามคณะที่เลือกแบบ cascading), ชั้นปี — ดูข้อมูลรายบุคคลแบบเดียวกับที่นักศึกษาเห็นในแดชบอร์ดตัวเอง (ชั่วโมงสะสม, ประวัติเช็กชื่อ, ประวัติคำร้องภายนอก)
- **รายงานนักศึกษาพร้อมยื่นจบ (PDF)**: รายชื่อนักศึกษาชั้นปีที่ 4 ที่ผ่านเกณฑ์ครบ 100% ส่งต่อสำนักทะเบียน (รองรับฟอนต์ไทย Sarabun)

### ระบบรองรับทุกผู้ใช้

- **สลับภาษาไทย/อังกฤษ** ได้ทุกหน้า (ปุ่ม TH/EN มุมขวาบน) — ใช้ localization ของ Laravel (`__()` + `lang/en.json`) ครอบคลุมทั้ง UI, ข้อความแจ้งเตือน, และหัวตาราง Excel
- **Dark mode / Light mode** สลับได้อิสระไม่ผูกกับ OS (ปุ่มพระอาทิตย์/พระจันทร์), จำค่าไว้ใน localStorage ไม่ต้องตั้งใหม่ทุกครั้ง
- ดีไซน์พรีเมียมโทนม่วงเข้ม-เขียวมรกต (SRRU brand), responsive รองรับมือถือ/แท็บเล็ต/เดสก์ท็อป

---

## เกณฑ์การผ่านกิจกรรม (Business Rules)

กำหนดไว้ที่ `app/Services/ActivityEvaluationService.php`:

| ประเภทหลักสูตร | จำนวนกิจกรรมที่ต้องผ่าน | ชั่วโมงรวมที่ต้องผ่าน | เป้าหมายชั่วโมง/ปี (ปี 1–4) |
|---|---|---|---|
| ภาคปกติ (`normal`) | 25 กิจกรรม | 100 ชั่วโมง | 40 / 30 / 20 / 10 |
| กศ.บป. (`special`) | 4 กิจกรรม | 50 ชั่วโมง | 20 / 15 / 10 / 5 |

ชั่วโมงสะสมนับจากทั้งกิจกรรมภายใน (เช็กชื่อสถานะ `auto_approved`) และคำร้องกิจกรรมภายนอกที่อนุมัติแล้ว

---

## Requirements

- PHP 8.2+ (พร้อม extension: `gd`, `intl`)
- Composer
- Node.js 18+ / npm
- MySQL

## ติดตั้งครั้งแรก

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

แก้ `.env`:

- `DB_*` ให้ตรงกับ MySQL ของเครื่อง
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` จาก Google Cloud Console (ต้องตั้งค่า Authorized redirect URI ให้ตรงกัน)
- `APP_LOCALE=th` (ค่าเริ่มต้น, ผู้ใช้สลับเป็น `en` ได้เองที่ปุ่ม TH/EN)

```bash
php artisan migrate --seed
php artisan storage:link
npm run build   # หรือ npm run dev ระหว่างพัฒนา
php artisan serve
```

### ทดสอบโดยไม่ต้องใช้ Google OAuth จริง

เมื่อรันในโหมด local (`APP_ENV=local`) มี route ทางลัดสำหรับล็อกอินด้วย user ที่มีอยู่แล้วในฐานข้อมูลโดยไม่ต้องผ่าน Google:

```
GET /_test-login/{user_id}
```

---

## โครงสร้างระบบ (สรุปย่อ)

```
app/
├── Http/Controllers/
│   ├── Admin/              # ActivityController, AttendanceController,
│   │                         ExternalApprovalController, StudentController,
│   │                         ClearanceReportController
│   ├── Student/             # DashboardController, CheckInController,
│   │                         ExternalActivityController
│   ├── Auth/GoogleAuthController.php
│   └── ProfileSetupController.php
├── Services/
│   ├── AttendanceAutomationService.php   # ตรรกะเช็กชื่อสามประสาน + อนุมัติอัตโนมัติ
│   ├── ActivityEvaluationService.php     # คำนวณเกณฑ์ผ่าน/ไม่ผ่าน + รายงานยื่นจบ
│   ├── DynamicQrTokenGenerator.php       # สร้าง/ตรวจสอบ QR token ที่หมุนอัตโนมัติ
│   └── HaversineCalculator.php           # คำนวณระยะห่าง GPS
├── Models/                  # User, Activity, Attendance, ExternalActivityRequest,
│                               Faculty, Major, ActivityRestriction
└── Http/Middleware/          # EnsureSrruEmail, EnsureProfileCompleted, EnsureIsAdmin, SetLocale

resources/views/
├── auth/, profile-setup/, student/       # หน้าฝั่งนักศึกษา
├── admin/                                # หน้าฝั่งกองพัฒนานักศึกษา
├── layouts/                              # app.blade.php (pre-auth), dashboard.blade.php (มี nav)
└── partials/                             # locale-switch, theme-toggle

lang/en.json                              # คำแปลภาษาอังกฤษ (translate-by-string)
```

---

## ข้อควรทราบก่อนใช้งานจริง

- ข้อมูลคณะ/สาขาใน `database/seeders/MajorSeeder.php` เป็นข้อมูลอ้างอิงตามโครงสร้างทั่วไปของกลุ่มมหาวิทยาลัยราชภัฏ **ต้องตรวจสอบกับสำนักส่งเสริมวิชาการของ SRRU ก่อนใช้งานจริง**
- ต้องมี Google OAuth credentials จริงก่อนระบบ login จะใช้งานได้
- รายงาน PDF ยื่นจบเป็นเอกสารทางการ แสดงผลเป็นภาษาไทยเสมอ ไม่ผูกกับปุ่มสลับภาษาของระบบ
