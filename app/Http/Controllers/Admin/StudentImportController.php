<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentRosterImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    public function create()
    {
        return view('admin.students.import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'roster_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new StudentRosterImport;
        Excel::import($import, $request->file('roster_file'));

        return redirect()
            ->route('admin.students.import.create')
            ->with('import_result', [
                'created' => $import->createdCount,
                'updated' => $import->updatedCount,
                'errors' => $import->rowErrors,
            ]);
    }

    public function template()
    {
        return Excel::download(new StudentImportTemplateExport, 'srru-student-import-template.xlsx');
    }
}
