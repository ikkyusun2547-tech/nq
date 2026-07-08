<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Faculty/major names reflect SRRU's general Rajabhat-system structure.
     * Verify exact current program names & codes against the SRRU registrar
     * before using this in production — curricula are revised periodically.
     */
    public function run(): void
    {
        $faculties = [
            [
                'code' => 'EDU',
                'name_th' => 'คณะครุศาสตร์',
                'name_en' => 'Faculty of Education',
                'majors' => [
                    ['code' => 'EDU-EARLY', 'name_th' => 'การศึกษาปฐมวัย', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-THAI', 'name_th' => 'ภาษาไทย', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-ENG', 'name_th' => 'ภาษาอังกฤษ', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-MATH', 'name_th' => 'คณิตศาสตร์', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-SCI', 'name_th' => 'วิทยาศาสตร์ทั่วไป', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-PE', 'name_th' => 'พลศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-COMP', 'name_th' => 'คอมพิวเตอร์ศึกษา', 'degree_abbr' => 'ค.บ.'],
                    ['code' => 'EDU-SOCIAL', 'name_th' => 'สังคมศึกษา', 'degree_abbr' => 'ค.บ.'],
                ],
            ],
            [
                'code' => 'HUSO',
                'name_th' => 'คณะมนุษยศาสตร์และสังคมศาสตร์',
                'name_en' => 'Faculty of Humanities and Social Sciences',
                'majors' => [
                    ['code' => 'HUSO-THAI', 'name_th' => 'ภาษาไทย', 'degree_abbr' => 'ศศ.บ.'],
                    ['code' => 'HUSO-ENG', 'name_th' => 'ภาษาอังกฤษ', 'degree_abbr' => 'ศศ.บ.'],
                    ['code' => 'HUSO-BUSENG', 'name_th' => 'ภาษาอังกฤษธุรกิจ', 'degree_abbr' => 'ศศ.บ.'],
                    ['code' => 'HUSO-SOCDEV', 'name_th' => 'การพัฒนาสังคม', 'degree_abbr' => 'ศศ.บ.'],
                    ['code' => 'HUSO-TOURISM', 'name_th' => 'อุตสาหกรรมท่องเที่ยว', 'degree_abbr' => 'ศศ.บ.'],
                    ['code' => 'HUSO-MUSIC', 'name_th' => 'ดนตรี', 'degree_abbr' => 'ศศ.บ.'],
                ],
            ],
            [
                'code' => 'SCI',
                'name_th' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
                'name_en' => 'Faculty of Science and Technology',
                'majors' => [
                    ['code' => 'SCI-CHEM', 'name_th' => 'เคมี', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-BIO', 'name_th' => 'ชีววิทยา', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-PHYS', 'name_th' => 'ฟิสิกส์', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-MATH', 'name_th' => 'คณิตศาสตร์', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-CS', 'name_th' => 'วิทยาการคอมพิวเตอร์', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-IT', 'name_th' => 'เทคโนโลยีสารสนเทศ', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-PUBHEALTH', 'name_th' => 'สาธารณสุขศาสตร์', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-AGRI', 'name_th' => 'เกษตรศาสตร์', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'SCI-ENV', 'name_th' => 'วิทยาศาสตร์สิ่งแวดล้อม', 'degree_abbr' => 'วท.บ.'],
                ],
            ],
            [
                'code' => 'TECH',
                'name_th' => 'คณะเทคโนโลยีอุตสาหกรรม',
                'name_en' => 'Faculty of Industrial Technology',
                'majors' => [
                    ['code' => 'TECH-ELEC', 'name_th' => 'เทคโนโลยีวิศวกรรมไฟฟ้า', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'TECH-CIVIL', 'name_th' => 'เทคโนโลยีวิศวกรรมโยธา', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'TECH-INDMGT', 'name_th' => 'การจัดการงานอุตสาหกรรม', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'TECH-DESIGN', 'name_th' => 'ออกแบบผลิตภัณฑ์อุตสาหกรรม', 'degree_abbr' => 'วท.บ.'],
                    ['code' => 'TECH-AUTO', 'name_th' => 'เทคโนโลยีเครื่องกล', 'degree_abbr' => 'วท.บ.'],
                ],
            ],
            [
                'code' => 'MGT',
                'name_th' => 'คณะวิทยาการจัดการ',
                'name_en' => 'Faculty of Management Science',
                'majors' => [
                    ['code' => 'MGT-MGMT', 'name_th' => 'การจัดการ', 'degree_abbr' => 'บธ.บ.'],
                    ['code' => 'MGT-MKT', 'name_th' => 'การตลาด', 'degree_abbr' => 'บธ.บ.'],
                    ['code' => 'MGT-FIN', 'name_th' => 'การเงินการธนาคาร', 'degree_abbr' => 'บธ.บ.'],
                    ['code' => 'MGT-BIZCOMP', 'name_th' => 'คอมพิวเตอร์ธุรกิจ', 'degree_abbr' => 'บธ.บ.'],
                    ['code' => 'MGT-HR', 'name_th' => 'การจัดการทรัพยากรมนุษย์', 'degree_abbr' => 'บธ.บ.'],
                    ['code' => 'MGT-ACC', 'name_th' => 'การบัญชี', 'degree_abbr' => 'บช.บ.'],
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

            foreach ($majors as $majorData) {
                $faculty->majors()->updateOrCreate(
                    ['code' => $majorData['code']],
                    $majorData
                );
            }
        }
    }
}
