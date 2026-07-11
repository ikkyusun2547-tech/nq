<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProfileSetupStoreRequest;
use App\Http\Resources\FacultyResource;
use App\Http\Resources\UserResource;
use App\Models\Faculty;

class ProfileSetupController extends Controller
{
    public function show()
    {
        $faculties = Faculty::with('majors')->orderBy('name_th')->get();

        return response()->json(['faculties' => FacultyResource::collection($faculties)]);
    }

    public function store(ProfileSetupStoreRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            ...collect($validated)->except(['title_prefix', 'first_name', 'last_name'])->all(),
            'name_thai' => $validated['title_prefix'].$validated['first_name'].' '.$validated['last_name'],
        ]);

        return response()->json([
            'message' => __('บันทึกข้อมูลโปรไฟล์สำเร็จ'),
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
