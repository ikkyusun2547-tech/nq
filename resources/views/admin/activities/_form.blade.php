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
        'open' => __('เปิดรับสมัคร'),
        'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'),
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
        lockCredit() { if (this.activityType === 'core') { this.creditHours = 5; } },
    }"
    x-init="lockCredit()"
>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('ชื่อกิจกรรม') }}</label>
            <input type="text" name="title" value="{{ old('title', $activity->title ?? '') }}" required
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('รายละเอียดกิจกรรม') }}</label>
            <textarea name="description" rows="3"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">{{ old('description', $activity->description ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('ภาพปกกิจกรรม') }} (Banner)</label>
            <input type="file" name="banner" accept="image/*"
                class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-purple-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-purple-700 hover:file:bg-brand-purple-100">
            @if (! empty($activity->banner_url))
                <img src="{{ asset('storage/'.$activity->banner_url) }}" class="mt-2 h-24 rounded-lg object-cover">
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('หน่วยงานผู้จัด') }}</label>
            <input type="text" name="organizer_name" value="{{ old('organizer_name', $activity->organizer_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('การแต่งกาย') }}</label>
            <input type="text" name="dress_code" value="{{ old('dress_code', $activity->dress_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('ระดับกิจกรรม') }}</label>
            <select name="activity_level" class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
                <option value="university" @selected(old('activity_level', $activity->activity_level ?? '') === 'university')>{{ __('ระดับมหาวิทยาลัย') }}</option>
                <option value="faculty" @selected(old('activity_level', $activity->activity_level ?? '') === 'faculty')>{{ __('ระดับคณะ') }}</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('หมวดหมู่กิจกรรม') }} (5 {{ __('ด้าน') }})</label>
            <select name="activity_category" class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
                @foreach ($categoryLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('activity_category', $activity->activity_category ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-600">{{ __('ลักษณะกิจกรรม') }}</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm shadow-soft transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700">
                    <input type="radio" name="activity_type" value="core" x-model="activityType" @change="lockCredit()" class="text-brand-purple-600 focus:ring-brand-purple-500">
                    {{ __('บังคับแกน') }}
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm shadow-soft transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700">
                    <input type="radio" name="activity_type" value="elective" x-model="activityType" @change="lockCredit()" class="text-brand-purple-600 focus:ring-brand-purple-500">
                    {{ __('บังคับเลือก') }}
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('จำนวนชั่วโมง') }}</label>
            <input
                type="number" name="credit_hours" x-model.number="creditHours" :readonly="activityType === 'core'"
                :class="activityType === 'core' ? 'bg-slate-100 text-slate-400' : ''"
                min="1" max="100" required
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
            >
            <p class="mt-1 text-xs text-slate-400" x-show="activityType === 'core'">{{ __('กิจกรรมบังคับแกนถูกกำหนดไว้ที่ :hours ชั่วโมงตามเกณฑ์สถาบัน', ['hours' => 5]) }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('จำนวนรับ (คน) — เว้นว่างหากไม่จำกัด') }}</label>
            <input type="number" name="capacity" value="{{ old('capacity', $activity->capacity ?? '') }}" min="1"
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('สถานะ') }}</label>
            <select name="status" class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $activity->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('วันเวลาเริ่มกิจกรรม') }}</label>
            <input type="datetime-local" name="start_at"
                value="{{ old('start_at', isset($activity->start_at) ? $activity->start_at->format('Y-m-d\TH:i') : '') }}" required
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">{{ __('วันเวลาสิ้นสุดกิจกรรม') }}</label>
            <input type="datetime-local" name="end_at"
                value="{{ old('end_at', isset($activity->end_at) ? $activity->end_at->format('Y-m-d\TH:i') : '') }}" required
                class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
        </div>
    </div>

    <div class="mt-6 rounded-2xl glass-card p-5 shadow-soft">
        <label class="mb-2 block text-sm font-medium text-slate-600">{{ __('ปักหมุดสถานที่จัดกิจกรรม (คลิกบนแผนที่)') }}</label>
        <div id="activity-map" class="h-72 w-full overflow-hidden rounded-2xl ring-1 ring-brand-purple-100"></div>
        <div class="mt-3 grid grid-cols-3 gap-3">
            <div>
                <label class="mb-1 block text-xs text-slate-400">Latitude</label>
                <input type="text" id="location_lat" name="location_lat" readonly
                    value="{{ old('location_lat', $activity->location_lat ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-100/70 px-3.5 py-2.5 text-sm text-slate-500">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-400">Longitude</label>
                <input type="text" id="location_lng" name="location_lng" readonly
                    value="{{ old('location_lng', $activity->location_lng ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-100/70 px-3.5 py-2.5 text-sm text-slate-500">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-400">{{ __('รัศมีปลอดภัย (เมตร)') }}</label>
                <input type="number" id="allowed_radius" name="allowed_radius" min="10" max="5000"
                    value="{{ old('allowed_radius', $activity->allowed_radius ?? 100) }}"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10">
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl glass-card p-5 shadow-soft">
        <h3 class="mb-3 text-sm font-medium text-slate-600">{{ __('กำหนดเป้าหมายผู้มีสิทธิ์เข้าร่วม (ไม่เลือกเลย = เปิดสิทธิ์ทั้งมหาวิทยาลัย)') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-purple-500">{{ __('คณะ') }}</p>
                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                    @foreach ($faculties as $faculty)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="faculty_ids[]" value="{{ $faculty->id }}"
                                @checked(in_array($faculty->id, $selectedFaculties))
                                class="rounded text-brand-purple-600 focus:ring-brand-purple-500">
                            {{ $faculty->name_th }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-purple-500">{{ __('สาขาวิชา') }}</p>
                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                    @foreach ($faculties as $faculty)
                        @foreach ($faculty->majors as $major)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="major_ids[]" value="{{ $major->id }}"
                                    @checked(in_array($major->id, $selectedMajors))
                                    class="rounded text-brand-purple-600 focus:ring-brand-purple-500">
                                {{ $major->name_th }}
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-purple-500">{{ __('ชั้นปี') }}</p>
                <div class="space-y-1.5">
                    @foreach ([1, 2, 3, 4] as $year)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="target_years[]" value="{{ $year }}"
                                @checked(in_array($year, $selectedYears))
                                class="rounded text-brand-purple-600 focus:ring-brand-purple-500">
                            {{ __('ชั้นปีที่ :year', ['year' => $year]) }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const latInput = document.getElementById('location_lat');
        const lngInput = document.getElementById('location_lng');
        const radiusInput = document.getElementById('allowed_radius');

        const initialLat = parseFloat(latInput.value) || 14.8818; // SRRU approx.
        const initialLng = parseFloat(lngInput.value) || 103.4936;

        const map = L.map('activity-map').setView([initialLat, initialLng], 16);
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
