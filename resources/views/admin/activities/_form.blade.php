@php
    $categoryLabels = [
        'culture' => __('ทำนุบำรุงศิลปวัฒนธรรม'),
        'academic' => __('วิชาการ'),
        'sports' => __('กีฬาและส่งเสริมสุขภาพ'),
        'volunteer' => __('จิตอาสา/บำเพ็ญประโยชน์'),
        'ethics' => __('คุณธรรมจริยธรรม'),
    ];
    $statusLabels = [
        'draft' => __('ร่าง'),
        'open' => __('เปิดลงทะเบียน'),
        'ongoing' => __('กำลังจัดกิจกรรม'),
        'closed' => __('ปิดกิจกรรม'),
        'cancelled' => __('ถูกยกเลิก'),
    ];
    $selectedFaculties = old('faculty_ids', $activity->restrictions->pluck('faculty_id')->filter()->unique()->values()->all() ?? []);
    $selectedMajors = old('major_ids', $activity->restrictions->pluck('major_id')->filter()->unique()->values()->all() ?? []);
    $selectedYears = old('target_years', $activity->restrictions->pluck('target_year')->filter()->unique()->values()->all() ?? []);
@endphp

<div
    x-data="{
        activityType: '{{ old('activity_type', $activity->activity_type ?? 'elective') }}',
        creditHours: {{ old('credit_hours', $activity->credit_hours ?? 1) }},
        checkinMethod: '{{ old('checkin_method', $activity->checkin_method ?? 'realtime') }}',
        requiresGps: {{ old('requires_gps', $activity->requires_gps ?? true) ? 'true' : 'false' }},
        eligibilityMode: '{{ (empty($selectedFaculties) && empty($selectedMajors) && empty($selectedYears)) ? 'open' : 'restricted' }}',
        lockCredit() { if (this.activityType === 'core') { this.creditHours = 5; } },
        refreshMap() { this.$nextTick(() => window.__activityMap && window.__activityMap.invalidateSize()); },
        clearEligibilityRestrictions() { this.$refs.eligibilityPanel.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false); },
    }"
    x-init="lockCredit()"
>
    <section class="rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-5 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">1</span>{{ __('ข้อมูลกิจกรรม') }}</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ชื่อกิจกรรม') }}</label>
            <input type="text" name="title" value="{{ old('title', $activity->title ?? '') }}" required
                class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('title') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('รายละเอียดกิจกรรม') }}</label>
            <textarea name="description" rows="3"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('description', $activity->description ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ภาพปกกิจกรรม') }} (Banner)</label>
            <input type="file" name="banner" accept="image/*"
                class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-purple-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-purple-700 hover:file:bg-brand-purple-100 dark:text-slate-400 dark:file:bg-brand-purple-500/10 dark:file:text-brand-purple-400">
            @if (! empty($activity->banner_url))
                <img src="{{ asset('storage/'.$activity->banner_url) }}" class="mt-2 h-24 rounded-lg object-cover">
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('หน่วยงานผู้จัด') }}</label>
            <input type="text" name="organizer_name" value="{{ old('organizer_name', $activity->organizer_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('การแต่งกาย') }}</label>
            <input type="text" name="dress_code" value="{{ old('dress_code', $activity->dress_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
        </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-5 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">2</span>{{ __('ประเภท ชั่วโมง และสถานะ') }}</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ระดับกิจกรรม') }}</label>
            <x-premium-select
                name="activity_level" :nullable="false"
                :options="['university' => __('ระดับมหาวิทยาลัย'), 'faculty' => __('ระดับคณะ')]"
                :selected="old('activity_level', $activity->activity_level ?? 'university')"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('หมวดหมู่กิจกรรม') }} (5 {{ __('ด้าน') }})</label>
            <x-premium-select
                name="activity_category" :options="$categoryLabels" :nullable="false"
                :selected="old('activity_category', $activity->activity_category ?? '')"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ปีการศึกษา') }}</label>
            @php
                $currentAcademicYear = \App\Services\AcademicYearCalculator::forDate(now());
            @endphp
            <input type="number" name="academic_year" value="{{ old('academic_year', $activity->academic_year ?? $currentAcademicYear) }}" required
                min="2540" max="{{ date('Y') + 544 }}"
                class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('academic_year') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ภาคเรียน') }}</label>
            <x-premium-select
                name="semester" :nullable="false"
                :options="['1' => __('ภาคเรียนที่ 1'), '2' => __('ภาคเรียนที่ 2'), '3' => __('ภาคฤดูร้อน')]"
                :selected="old('semester', $activity->semester ?? '1')"
            />
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ลักษณะกิจกรรม') }}</label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                    <input type="radio" name="activity_type" value="core" x-model="activityType" @change="lockCredit()" class="text-brand-purple-600 focus:ring-brand-purple-500">
                    {{ __('บังคับแกน') }}
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                    <input type="radio" name="activity_type" value="elective" x-model="activityType" @change="lockCredit()" class="text-brand-purple-600 focus:ring-brand-purple-500">
                    {{ __('บังคับเลือก') }}
                </label>
                <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                    <input type="radio" name="activity_type" value="practice" x-model="activityType" @change="lockCredit()" class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                    <span>
                        <span class="block">{{ __('กิจกรรมซ้อม/เตรียมงาน') }}</span>
                        <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('นับชั่วโมงสะสม แต่ไม่นับเป็น 1 ใน 25 กิจกรรม') }}</span>
                    </span>
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('จำนวนชั่วโมง') }}</label>
            <input
                type="number" name="credit_hours" x-model.number="creditHours" :readonly="activityType === 'core'"
                :class="activityType === 'core' ? 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' : ''"
                min="1" max="100" required
                class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('credit_hours') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror"
            >
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500" x-show="activityType === 'core'">{{ __('กิจกรรมบังคับแกนถูกกำหนดไว้ที่ :hours ชั่วโมงตามเกณฑ์สถาบัน', ['hours' => 5]) }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('จำนวนรับ (คน) — เว้นว่างหากไม่จำกัด') }}</label>
            <input type="number" name="capacity" value="{{ old('capacity', $activity->capacity ?? '') }}" min="1"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('สถานะ') }}</label>
            <x-premium-select
                name="status" :options="$statusLabels" :nullable="false"
                :selected="old('status', $activity->status ?? 'draft')"
            />
        </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-5 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">3</span>{{ __('วันและเวลา') }}</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('วันเวลาเริ่มกิจกรรม') }}</label>
            <div class="relative">
                <input type="text" name="start_at" autocomplete="off"
                    value="{{ old('start_at', isset($activity->start_at) ? $activity->start_at->format('Y-m-d H:i') : '') }}" required
                    class="js-datetime-picker w-full rounded-2xl border bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('start_at') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </span>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('วันเวลาสิ้นสุดกิจกรรม') }}</label>
            <div class="relative">
                <input type="text" name="end_at" autocomplete="off"
                    value="{{ old('end_at', isset($activity->end_at) ? $activity->end_at->format('Y-m-d H:i') : '') }}" required
                    class="js-datetime-picker w-full rounded-2xl border bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('end_at') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </span>
            </div>
        </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-5 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">4</span>{{ __('วิธีเช็คชื่อ') }}</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                <input type="radio" name="checkin_method" value="realtime" x-model="checkinMethod" @change="refreshMap()" class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('สแกน QR + GPS + เซลฟี') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('เช็คชื่อหน้างานแบบเรียลไทม์ (ค่าเริ่มต้น)') }}</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                <input type="radio" name="checkin_method" value="self_report" x-model="checkinMethod" class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('รายงานตนเอง + แนบรูปหลักฐาน') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่ใช้ QR/GPS — สำหรับสถานที่ที่ฉาย QR ไม่ได้ ค่าเริ่มต้นต้องรอแอดมินตรวจสอบก่อนอนุมัติ (ปรับได้ด้านล่าง)') }}</span>
                </span>
            </label>
        </div>

        <div x-show="checkinMethod === 'realtime'" x-cloak class="mt-3">
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10">
                <input type="hidden" name="requires_gps" value="0">
                <input type="checkbox" name="requires_gps" value="1" x-model="requiresGps" @change="refreshMap()" class="mt-0.5 rounded text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('ต้องตรวจสอบตำแหน่ง GPS') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('ปิดไว้สำหรับสถานที่ที่ GPS ไม่เสถียร (ในตึก/ใต้ดิน) — ยังคงสแกน QR และถ่ายเซลฟีตามปกติ') }}</span>
                </span>
            </label>
        </div>

        <div x-show="checkinMethod === 'self_report'" x-cloak class="mt-3">
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10">
                <input type="hidden" name="self_report_auto_approve" value="0">
                <input type="checkbox" name="self_report_auto_approve" value="1"
                    @checked(old('self_report_auto_approve', $activity->self_report_auto_approve ?? false))
                    class="mt-0.5 rounded text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('อนุมัติอัตโนมัติ ไม่ต้องรอตรวจสอบ') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('ปกติทุกรายการที่รายงานตนเองต้องรอแอดมินตรวจสอบก่อนเสมอ — เปิดตัวเลือกนี้เพื่อบันทึกชั่วโมงให้ทันทีที่ส่งหลักฐานแทน เหมาะกับกิจกรรมความเสี่ยงต่ำที่ไม่จำเป็นต้องตรวจสอบราย ๆ') }}</span>
                </span>
            </label>
        </div>
    </section>

    <section class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-5 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">5</span>{{ __('สถานที่') }}</h2>
        <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('สถานที่จัดกิจกรรม') }}</label>
        <input type="text" name="location_name" value="{{ old('location_name', $activity->location_name ?? '') }}" required
            class="mb-4 w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('location_name') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">

        <div x-show="checkinMethod === 'realtime' && requiresGps" x-cloak>
            <label class="mb-2 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ปักหมุดสถานที่จัดกิจกรรม (คลิกบนแผนที่ หรือพิมพ์พิกัดด้านล่าง)') }}</label>
            <div id="activity-map" class="h-72 w-full overflow-hidden rounded-2xl ring-1 ring-brand-purple-100 dark:ring-brand-purple-500/20"></div>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div>
                    <label class="mb-1 block text-xs text-slate-400 dark:text-slate-500">Latitude</label>
                    <input type="text" id="location_lat" name="location_lat" inputmode="decimal" autocomplete="off" placeholder="14.8818"
                        value="{{ old('location_lat', $activity->location_lat ?? '') }}"
                        class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('location_lat') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-400 dark:text-slate-500">Longitude</label>
                    <input type="text" id="location_lng" name="location_lng" inputmode="decimal" autocomplete="off" placeholder="103.4936"
                        value="{{ old('location_lng', $activity->location_lng ?? '') }}"
                        class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('location_lng') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-400 dark:text-slate-500">{{ __('รัศมีปลอดภัย (เมตร)') }}</label>
                    <input type="number" id="allowed_radius" name="allowed_radius" min="10" max="5000"
                        value="{{ old('allowed_radius', $activity->allowed_radius ?? 100) }}"
                        class="w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 @error('allowed_radius') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">{{ __('คัดลอกพิกัดจาก Google Maps (เช่น 14.8818, 103.4936) มาวางในช่อง Latitude ได้เลย ระบบจะแยกให้เอง') }}</p>
        </div>

        <div x-show="checkinMethod === 'self_report'" x-cloak>
            <label class="mb-2 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ช่วงเวลาที่เปิดให้เช็คชื่อแบบรายงานตนเอง') }}</label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-slate-400 dark:text-slate-500">{{ __('เปิดให้เช็คชื่อตั้งแต่') }}</label>
                    <div class="relative">
                        <input type="text" name="checkin_opens_at" autocomplete="off"
                            value="{{ old('checkin_opens_at', isset($activity->checkin_opens_at) ? $activity->checkin_opens_at->format('Y-m-d H:i') : '') }}"
                            class="js-datetime-picker w-full rounded-2xl border bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-700 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 @error('checkin_opens_at') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 dark:text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-400 dark:text-slate-500">{{ __('ปิดรับเช็คชื่อเมื่อ') }}</label>
                    <div class="relative">
                        <input type="text" name="checkin_closes_at" autocomplete="off"
                            value="{{ old('checkin_closes_at', isset($activity->checkin_closes_at) ? $activity->checkin_closes_at->format('Y-m-d H:i') : '') }}"
                            class="js-datetime-picker w-full rounded-2xl border bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-700 transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 @error('checkin_closes_at') border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70 @else border-slate-300 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-600 @enderror">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 dark:text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <h2 class="mb-1 flex items-center gap-2.5 font-display text-lg text-slate-900 dark:text-white"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">6</span>{{ __('ผู้มีสิทธิ์เข้าร่วม') }}</h2>
        <p class="mb-3 text-xs text-slate-400 dark:text-slate-500">{{ __('เลือกได้ว่าจะเปิดให้นักศึกษาทุกคนเข้าร่วม หรือจำกัดเฉพาะคณะ/สาขา/ชั้นปีที่ต้องการ') }}</p>

        {{-- Programme: applies on top of the faculty/major/year choice below. --}}
        @php $selectedProgram = old('target_program', $activity->target_program ?? ''); @endphp
        <div class="mb-4">
            <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('ภาคการศึกษา') }}</p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach (['' => [__('ทุกภาค'), __('ภาคปกติและภาคพิเศษ')], 'normal' => [__('ภาคปกติ'), __('เฉพาะนักศึกษาภาคปกติ')], 'special' => [__('ภาคพิเศษ (กศ.บป.)'), __('เฉพาะนักศึกษาภาคพิเศษ')]] as $value => [$label, $hint])
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                        <input type="radio" name="target_program" value="{{ $value }}" @checked((string) $selectedProgram === (string) $value) class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                        <span>
                            <span class="block font-medium">{{ $label }}</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">{{ $hint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('target_program')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('คณะ / สาขา / ชั้นปี') }}</p>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                <input type="radio" x-model="eligibilityMode" value="open" @change="clearEligibilityRestrictions()" class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('เปิดให้นักศึกษาทุกคน') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('ทั้งมหาวิทยาลัย ไม่จำกัดคณะ/สาขา/ชั้นปี (ค่าเริ่มต้น)') }}</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-800/40 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400">
                <input type="radio" x-model="eligibilityMode" value="restricted" class="mt-0.5 text-brand-purple-600 focus:ring-brand-purple-500">
                <span>
                    <span class="block font-medium">{{ __('จำกัดเฉพาะกลุ่มเป้าหมาย') }}</span>
                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ __('เลือกคณะ/สาขา/ชั้นปีที่ต้องการด้านล่าง') }}</span>
                </span>
            </label>
        </div>

        <div x-ref="eligibilityPanel" x-show="eligibilityMode === 'restricted'" x-cloak class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-brand-purple-500 dark:text-brand-purple-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                            {{ __('คณะ') }}
                        </p>
                        <div class="flex gap-2 text-[0.68rem]">
                            <button type="button" class="text-brand-purple-600 hover:underline dark:text-brand-purple-400" @click="$refs.facultyList.querySelectorAll('input').forEach(cb => cb.checked = true)">{{ __('เลือกทั้งหมด') }}</button>
                            <button type="button" class="text-slate-400 hover:underline dark:text-slate-500" @click="$refs.facultyList.querySelectorAll('input').forEach(cb => cb.checked = false)">{{ __('ล้าง') }}</button>
                        </div>
                    </div>
                    <div x-ref="facultyList" class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($faculties as $faculty)
                            <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-slate-600 transition-colors duration-150 has-[:checked]:bg-brand-purple-50 has-[:checked]:font-medium has-[:checked]:text-brand-purple-700 hover:bg-slate-50 dark:text-slate-300 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400 dark:hover:bg-slate-700/50">
                                <input type="checkbox" name="faculty_ids[]" value="{{ $faculty->id }}"
                                    @checked(in_array($faculty->id, $selectedFaculties))
                                    class="peer h-4 w-4 shrink-0 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                                <span class="flex-1">{{ $faculty->name_th }}</span>
                                <svg class="hidden h-4 w-4 shrink-0 text-brand-purple-600 peer-checked:block dark:text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-brand-purple-500 dark:text-brand-purple-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                            {{ __('ชั้นปี') }}
                        </p>
                        <div class="flex gap-2 text-[0.68rem]">
                            <button type="button" class="text-brand-purple-600 hover:underline dark:text-brand-purple-400" @click="$refs.yearList.querySelectorAll('input').forEach(cb => cb.checked = true)">{{ __('เลือกทั้งหมด') }}</button>
                            <button type="button" class="text-slate-400 hover:underline dark:text-slate-500" @click="$refs.yearList.querySelectorAll('input').forEach(cb => cb.checked = false)">{{ __('ล้าง') }}</button>
                        </div>
                    </div>
                    <div x-ref="yearList" class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-800">
                        @foreach ([1, 2, 3, 4] as $year)
                            <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-slate-600 transition-colors duration-150 has-[:checked]:bg-brand-purple-50 has-[:checked]:font-medium has-[:checked]:text-brand-purple-700 hover:bg-slate-50 dark:text-slate-300 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400 dark:hover:bg-slate-700/50">
                                <input type="checkbox" name="target_years[]" value="{{ $year }}"
                                    @checked(in_array($year, $selectedYears))
                                    class="peer h-4 w-4 shrink-0 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                                <span class="flex-1">{{ __('ชั้นปีที่ :year', ['year' => $year]) }}</span>
                                <svg class="hidden h-4 w-4 shrink-0 text-brand-purple-600 peer-checked:block dark:text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Full width and grouped into one card per faculty (instead of
                 one tall scrolling list) so every major is visible at once —
                 a single column would run to 48 rows for this dataset. --}}
            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-brand-purple-500 dark:text-brand-purple-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M4.26 10.147a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M4.26 10.147A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443"/></svg>
                        {{ __('สาขาวิชา') }}
                    </p>
                    <div class="flex gap-2 text-[0.68rem]">
                        <button type="button" class="text-brand-purple-600 hover:underline dark:text-brand-purple-400" @click="$refs.majorGrid.querySelectorAll('input').forEach(cb => cb.checked = true)">{{ __('เลือกทั้งหมด') }}</button>
                        <button type="button" class="text-slate-400 hover:underline dark:text-slate-500" @click="$refs.majorGrid.querySelectorAll('input').forEach(cb => cb.checked = false)">{{ __('ล้าง') }}</button>
                    </div>
                </div>
                <div x-ref="majorGrid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($faculties as $faculty)
                        @if ($faculty->majors->isNotEmpty())
                            <div x-ref="majorCard{{ $faculty->id }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center justify-between bg-slate-50 px-3 py-1.5 dark:bg-slate-900/40">
                                    <p class="text-[0.68rem] font-medium text-slate-500 dark:text-slate-400">{{ $faculty->name_th }}</p>
                                    <div class="flex gap-1.5 text-[0.65rem]">
                                        <button type="button" class="text-brand-purple-600 hover:underline dark:text-brand-purple-400" @click="$refs.majorCard{{ $faculty->id }}.querySelectorAll('input').forEach(cb => cb.checked = true)">{{ __('เลือกทั้งหมด') }}</button>
                                        <button type="button" class="text-slate-400 hover:underline dark:text-slate-500" @click="$refs.majorCard{{ $faculty->id }}.querySelectorAll('input').forEach(cb => cb.checked = false)">{{ __('ล้าง') }}</button>
                                    </div>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach ($faculty->majors as $major)
                                        <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-slate-600 transition-colors duration-150 has-[:checked]:bg-brand-purple-50 has-[:checked]:font-medium has-[:checked]:text-brand-purple-700 hover:bg-slate-50 dark:text-slate-300 dark:has-[:checked]:bg-brand-purple-500/10 dark:has-[:checked]:text-brand-purple-400 dark:hover:bg-slate-700/50">
                                            <input type="checkbox" name="major_ids[]" value="{{ $major->id }}"
                                                @checked(in_array($major->id, $selectedMajors))
                                                class="peer h-4 w-4 shrink-0 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                                            <span class="flex-1">{{ $major->name_th }}</span>
                                            <svg class="hidden h-4 w-4 shrink-0 text-brand-purple-600 peer-checked:block dark:text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://unpkg.com/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://unpkg.com/flatpickr@4.6.13/dist/l10n/th.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // A single click-to-pick calendar + time list replaces the native
        // datetime-local control, which forced clicking into each of its
        // five separate segments (day/month/year/hour/minute) one at a time.
        document.querySelectorAll('.js-datetime-picker').forEach((el) => {
            flatpickr(el, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                allowInput: true,
                locale: 'th',
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const latInput = document.getElementById('location_lat');
        const lngInput = document.getElementById('location_lng');
        const radiusInput = document.getElementById('allowed_radius');

        const initialLat = parseFloat(latInput.value) || 14.8818; // SRRU approx.
        const initialLng = parseFloat(lngInput.value) || 103.4936;

        const map = L.map('activity-map').setView([initialLat, initialLng], 16);
        window.__activityMap = map;
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        let marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
        let circle = L.circle([initialLat, initialLng], {
            radius: parseFloat(radiusInput.value) || 100,
            color: '#059669',
            fillOpacity: 0.12,
        }).addTo(map);

        function setPoint(lat, lng) {
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
        }

        if (latInput.value && lngInput.value) {
            setPoint(initialLat, initialLng);
        }

        map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng));

        // Typed coordinates move the pin. A pasted "lat, lng" pair (how
        // Google Maps copies a point) is split across both fields.
        function fromInputs() {
            const pair = latInput.value.match(/^\s*(-?\d+(?:\.\d+)?)\s*[,\s]\s*(-?\d+(?:\.\d+)?)\s*$/)
                || lngInput.value.match(/^\s*(-?\d+(?:\.\d+)?)\s*[,\s]\s*(-?\d+(?:\.\d+)?)\s*$/);
            if (pair) {
                latInput.value = pair[1];
                lngInput.value = pair[2];
            }
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (Number.isNaN(lat) || Number.isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                return;
            }
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            map.panTo([lat, lng]);
        }
        latInput.addEventListener('input', fromInputs);
        lngInput.addEventListener('input', fromInputs);
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setPoint(pos.lat, pos.lng);
        });
        radiusInput.addEventListener('input', () => {
            circle.setRadius(parseFloat(radiusInput.value) || 0);
        });
    });
</script>
@endpush
