<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MajorResource;
use App\Models\Faculty;

class FacultyController extends Controller
{
    public function majors(Faculty $faculty)
    {
        return response()->json([
            'data' => MajorResource::collection(
                $faculty->majors()->orderBy('name_th')->get(['id', 'name_th', 'degree_abbr', 'faculty_id'])
            ),
        ]);
    }
}
