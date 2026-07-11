@props([
    'activities',
    'requiredActivities',
    'hours',
    'requiredHours',
])

<div class="flex items-center rounded-2xl glass-card p-5 shadow-soft">
    <div class="flex-1">
        <x-progress-ring :value="$activities" :target="$requiredActivities" :label="__('กิจกรรม')" color="#7C3AED" track="rgb(124 58 237 / 0.12)" />
    </div>
    <div class="h-[72px] w-px bg-brand-purple-100 dark:bg-slate-700/60"></div>
    <div class="flex-1">
        <x-progress-ring :value="$hours" :target="$requiredHours" :label="__('ชั่วโมง')" color="#059669" track="rgb(5 150 105 / 0.12)" />
    </div>
</div>
