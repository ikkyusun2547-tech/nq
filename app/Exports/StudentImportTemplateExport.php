<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'อีเมล', 'คณะ', 'สาขา', 'ชั้นปี', 'ปีที่เข้าศึกษา', 'ประเภทหลักสูตร'];
    }

    public function array(): array
    {
        return [
            ['69010101001', 'สมชาย ใจดี', 'somchai.j@srru.ac.th', 'คณะวิทยาศาสตร์และเทคโนโลยี', 'วิทยาการคอมพิวเตอร์', 1, 2569, 'ภาคปกติ'],
        ];
    }
}
