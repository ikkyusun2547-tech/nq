<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            [
                'code' => 'SCI',
                'name_th' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
                'name_en' => 'Faculty of Science and Technology',
                'majors' => [
                    ['name_th' => 'วิทยาการคอมพิวเตอร์', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'ชีววิทยาเชิงสร้างสรรค์', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'เคมีประยุกต์', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'คณิตศาสตร์ประยุกต์และสถิติ', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'วิทยาศาสตร์สิ่งแวดล้อม', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'คหกรรมศาสตร์ประยุกต์', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'การส่งเสริมสุขภาพเด็กและผู้สูงอายุ', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'สาธารณสุขศาสตร์', 'degree_abbr' => 'ส.บ.'],
                    ['name_th' => 'การแพทย์แผนไทย', 'degree_abbr' => 'พท.บ.'],
                    ['name_th' => 'คณิตศาสตร์', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'เคมี', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ชีววิทยา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ฟิสิกส์', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'คอมพิวเตอร์ศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'วิทยาศาสตร์ทั่วไป', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ภาษาอังกฤษและเทคโนโลยีการศึกษา', 'degree_abbr' => 'ค.บ.'],
                ],
            ],
            [
                'code' => 'EDU',
                'name_th' => 'คณะครุศาสตร์',
                'name_en' => 'Faculty of Education',
                'majors' => [
                    ['name_th' => 'การศึกษาปฐมวัย', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'การประถมศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'พลศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'เทคโนโลยีดิจิทัลเพื่อการศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'การสอนภาษาจีน', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ภาษาอังกฤษ', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'วิทยาศาสตร์การกีฬา', 'degree_abbr' => 'วท.บ.'],
                ],
            ],
            [
                'code' => 'HUSO',
                'name_th' => 'คณะมนุษยศาสตร์และสังคมศาสตร์',
                'name_en' => 'Faculty of Humanities and Social Sciences',
                'majors' => [
                    ['name_th' => 'นิติศาสตร์', 'degree_abbr' => 'น.บ.'],
                    ['name_th' => 'รัฐศาสตร์', 'degree_abbr' => 'ร.บ.'],
                    ['name_th' => 'รัฐประศาสนศาสตร์', 'degree_abbr' => 'รป.บ.'],
                    ['name_th' => 'ภาษาอังกฤษเพื่อการสื่อสาร', 'degree_abbr' => 'ศศ.บ.'],
                    ['name_th' => 'การพัฒนาสังคม', 'degree_abbr' => 'ศศ.บ.'],
                    ['name_th' => 'บรรณารักษศาสตร์และสารสนเทศศาสตร์', 'degree_abbr' => 'ศศ.บ.'],
                    ['name_th' => 'ทัศนศิลป์และการออกแบบ', 'degree_abbr' => 'ศป.บ.'],
                    ['name_th' => 'ภาษาไทย', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ภาษาอังกฤษ', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'สังคมศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'ดนตรีศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['name_th' => 'นาฏศิลป์ศึกษา', 'degree_abbr' => 'ค.บ.'],
                ],
            ],
            [
                'code' => 'MGT',
                'name_th' => 'คณะวิทยาการจัดการ',
                'name_en' => 'Faculty of Management Science',
                'majors' => [
                    ['name_th' => 'บัญชี', 'degree_abbr' => 'บช.บ.'],
                    ['name_th' => 'การตลาดสมัยใหม่', 'degree_abbr' => 'บธ.บ.'],
                    ['name_th' => 'การจัดการโลจิสติกส์และซัพพลายเชน', 'degree_abbr' => 'บธ.บ.'],
                    ['name_th' => 'บริหารธุรกิจ', 'degree_abbr' => 'บธ.บ.'],
                    ['name_th' => 'การท่องเที่ยวและการโรงแรม', 'degree_abbr' => 'ศศ.บ.'],
                    ['name_th' => 'นิเทศศาสตร์', 'degree_abbr' => 'ศศ.บ.'],
                    ['name_th' => 'เศรษฐศาสตร์การพัฒนาชุมชน', 'degree_abbr' => 'ศ.บ.'],
                ],
            ],
            [
                'code' => 'TECH',
                'name_th' => 'คณะเทคโนโลยีอุตสาหกรรม',
                'name_en' => 'Faculty of Industrial Technology',
                'majors' => [
                    ['name_th' => 'เทคโนโลยีวิศวกรรมเครื่องกลและยานยนต์ไฟฟ้า', 'degree_abbr' => 'อส.บ.'],
                    ['name_th' => 'เทคโนโลยีวิศวกรรมโยธาและสถาปัตยกรรม', 'degree_abbr' => 'อส.บ.'],
                    ['name_th' => 'วิศวกรรมการจัดการและโลจิสติกส์', 'degree_abbr' => 'วศ.บ.'],
                    ['name_th' => 'อุตสาหกรรมศิลป์', 'degree_abbr' => 'ค.บ.'],
                ],
            ],
            [
                'code' => 'AGRI',
                'name_th' => 'คณะเกษตรและอุตสาหกรรมเกษตร',
                'name_en' => 'Faculty of Agriculture and Agro-Industry',
                'majors' => [
                    ['name_th' => 'เกษตรศาสตร์', 'degree_abbr' => 'วท.บ.'],
                    ['name_th' => 'สัตวศาสตร์', 'degree_abbr' => 'วท.บ.'],
                ],
            ],
        ];

        foreach ($faculties as $facultyData) {
            $majors = $facultyData['majors'];
            unset($facultyData['majors']);

            $faculty = Faculty::updateOrCreate(
                ['code' => $facultyData['code']],
                $facultyData
            );

            foreach ($majors as $index => $majorData) {
                $majorData['code'] = sprintf('%s-%02d', $facultyData['code'], $index + 1);

                $faculty->majors()->updateOrCreate(
                    ['code' => $majorData['code']],
                    $majorData
                );
            }
        }
    }
}
