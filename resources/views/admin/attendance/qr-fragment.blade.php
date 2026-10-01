<div class="w-full [&_svg]:mx-auto [&_svg]:h-auto [&_svg]:w-full">
    {!! $svg !!}
</div>
{{-- Always on the white QR card, so no dark-mode variants here. --}}
<div class="mt-6 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
    <div class="qr-countdown-fill h-full rounded-full" style="animation-duration: {{ $rotationSeconds }}s;"></div>
</div>
