# ระบบบริหารจัดการและเช็กชื่อกิจกรรมนักศึกษา มหาวิทยาลัยราชภัฏสุรินทร์ (SRRU)

ระบบเช็กชื่อกิจกรรมนักศึกษาผ่าน "สามประสาน" (Dynamic QR Code + GPS Geolocation + Selfie) พร้อมระบบบริหารเกณฑ์ชั่วโมงกิจกรรมและออกรายงานพร้อมยื่นจบอัตโนมัติ สำหรับกองพัฒนานักศึกษา

**Tech stack:** Laravel 11 · MySQL · Tailwind CSS v4 · Alpine.js

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

แก้ `.env`: ตั้งค่า `DB_*` ให้ตรงกับ MySQL ของเครื่อง และตั้งค่า Google OAuth
(`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` จาก Google Cloud Console — Authorized
redirect URI ต้องตรงกับ `GOOGLE_REDIRECT_URI`)

```bash
php artisan migrate --seed
php artisan storage:link
npm run build   # หรือ npm run dev ระหว่างพัฒนา
php artisan serve
```

## โครงสร้างระบบ (สรุปย่อ)

- **นักศึกษา**: Login ด้วย Google (จำกัดเฉพาะอีเมลโดเมนมหาวิทยาลัย) → กรอกโปรไฟล์ครั้งแรก → สแกน QR + เซลฟี + GPS เพื่อเช็กชื่อ → ดูความคืบหน้าชั่วโมงสะสมที่แดชบอร์ด → ยื่นคำร้องกิจกรรมภายนอกได้
- **กองพัฒนานักศึกษา (Admin)**: สร้าง/จัดการกิจกรรม กำหนดสิทธิ์ผู้เข้าร่วม แสดง QR หมุนอัตโนมัติหน้างาน มอนิเตอร์การเช็กชื่อแบบ real-time พร้อม bulk actions อนุมัติคำร้องภายนอก และออกรายงาน Excel/PDF

รายละเอียดเชิงลึก (schema, business rules, service classes) อยู่ในโค้ดของแต่ละ
`app/Services/*` — `AttendanceAutomationService`, `ActivityEvaluationService`,
`DynamicQrTokenGenerator`, `HaversineCalculator`

## ข้อควรทราบก่อนใช้งานจริง

- ข้อมูลคณะ/สาขาใน `database/seeders/MajorSeeder.php` เป็นข้อมูลอ้างอิงตามโครงสร้างทั่วไปของกลุ่มมหาวิทยาลัยราชภัฏ **ต้องตรวจสอบกับสำนักส่งเสริมวิชาการของ SRRU ก่อนใช้งานจริง**
- ต้องมี Google OAuth credentials จริงก่อนระบบ login จะใช้งานได้
