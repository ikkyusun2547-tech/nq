{{-- Photo / map / approve-reject buttons for one attendance row; shared by the desktop table and the mobile cards. --}}
<button type="button" @click="lightboxUrl = '{{ asset('storage/'.$att->photo_path) }}'"
    title="{{ in_array($att->checkin_method, ['self_report', 'late_request'], true) ? __('รูปหลักฐาน') : __('รูปเซลฟี') }}"
    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-purple-300">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
</button>
@if ($att->student_lat !== null && $att->student_lng !== null)
    <a href="https://www.google.com/maps?q={{ $att->student_lat }},{{ $att->student_lng }}" target="_blank" rel="noopener" title="{{ __('แผนที่') }}"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-purple-300">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
    </a>
@endif
@if ($att->status === 'flagged')
    <form method="POST" action="{{ route('admin.attendance.approve', $att) }}">
        @csrf
        <button class="h-8 rounded-lg bg-brand-green-600 px-3 text-xs font-semibold text-white transition-colors hover:bg-brand-green-700">{{ __('อนุมัติ') }}</button>
    </form>
    <button type="button" @click="rejectUrl = '{{ route('admin.attendance.reject', $att) }}'; rejectName = @js($name)"
        class="h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition-colors hover:border-rose-300 hover:text-rose-700 dark:border-slate-700 dark:text-slate-200">{{ __('ไม่อนุมัติ') }}</button>
@endif
