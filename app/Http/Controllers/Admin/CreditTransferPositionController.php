<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditTransferPositionController extends Controller
{
    public function index()
    {
        $positions = CreditTransferPosition::orderBy('sort_order')->get();

        return view('admin.credit-transfer-positions.index', compact('positions'));
    }

    public function create()
    {
        return view('admin.credit-transfer-positions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:credit_transfer_positions,key'],
            'label' => ['required', 'string', 'max:255'],
            'hours' => ['required', 'integer', 'min:0', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] ??= ((int) CreditTransferPosition::max('sort_order')) + 1;

        $position = CreditTransferPosition::create($validated);
        AuditLogger::log('created', __('ตำแหน่งเทียบโอนชั่วโมง'), $position->label);

        return redirect()->route('admin.credit-transfer-positions.index')->with('status', __('เพิ่มตำแหน่งเทียบโอนชั่วโมงสำเร็จ'));
    }

    public function edit(CreditTransferPosition $creditTransferPosition)
    {
        $keyLocked = CreditTransferRequest::where('position', $creditTransferPosition->key)->exists();

        return view('admin.credit-transfer-positions.edit', ['position' => $creditTransferPosition, 'keyLocked' => $keyLocked]);
    }

    public function update(Request $request, CreditTransferPosition $creditTransferPosition)
    {
        // A key already referenced by existing requests can't be renamed —
        // those rows only store the key string, so changing it here would
        // orphan them (their position would no longer match any row and
        // fall back to displaying the raw key instead of a label). Same
        // "block it only once it's actually load-bearing" reasoning as
        // destroy() below, just for renames instead of deletes.
        $keyLocked = CreditTransferRequest::where('position', $creditTransferPosition->key)->exists();

        $validated = $request->validate([
            'key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('credit_transfer_positions', 'key')->ignore($creditTransferPosition->id),
                function ($attribute, $value, $fail) use ($keyLocked, $creditTransferPosition) {
                    if ($keyLocked && $value !== $creditTransferPosition->key) {
                        $fail(__('ไม่สามารถเปลี่ยนรหัสตำแหน่งนี้ได้ เนื่องจากมีคำร้องเทียบโอนชั่วโมงผูกอยู่แล้ว'));
                    }
                },
            ],
            'label' => ['required', 'string', 'max:255'],
            'hours' => ['required', 'integer', 'min:0', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] ??= $creditTransferPosition->sort_order;

        // Only affects future credit-transfer requests — an already
        // approved request snapshots its own hours_requested/hours_approved
        // at creation time, same as GraduationCriteria changes only apply
        // going forward, never retroactively to past evaluations.
        $creditTransferPosition->update($validated);
        AuditLogger::log('updated', __('ตำแหน่งเทียบโอนชั่วโมง'), $creditTransferPosition->label);

        return redirect()->route('admin.credit-transfer-positions.index')->with('status', __('บันทึกตำแหน่งเทียบโอนชั่วโมงสำเร็จ'));
    }

    public function destroy(CreditTransferPosition $creditTransferPosition)
    {
        if (CreditTransferRequest::where('position', $creditTransferPosition->key)->exists()) {
            return back()->with('error', __('ไม่สามารถลบตำแหน่ง ":label" ได้ เนื่องจากมีคำร้องเทียบโอนชั่วโมงผูกอยู่', ['label' => $creditTransferPosition->label]));
        }

        $label = $creditTransferPosition->label;
        $creditTransferPosition->delete();
        AuditLogger::log('deleted', __('ตำแหน่งเทียบโอนชั่วโมง'), $label);

        return redirect()->route('admin.credit-transfer-positions.index')->with('status', __('ลบตำแหน่งเทียบโอนชั่วโมงสำเร็จ'));
    }
}
