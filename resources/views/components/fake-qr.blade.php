@props(['seed' => 'AURORA', 'size' => 25])

{{-- QR ilustrativo (pagamento simulado): padrão determinístico gerado a partir do código --}}
@php
    mt_srand(crc32($seed));
    $n = $size;
    $finder = function ($x, $y) use ($n) {
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$fx, $fy]) {
            if ($x >= $fx && $x < $fx + 7 && $y >= $fy && $y < $fy + 7) {
                $dx = $x - $fx; $dy = $y - $fy;
                return ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6) || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4) ? 1 : 0;
            }
            if ($x >= $fx - 1 && $x <= $fx + 7 && $y >= $fy - 1 && $y <= $fy + 7) {
                return 0;
            }
        }
        return null;
    };
    $cells = [];
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $f = $finder($x, $y);
            if ($f ?? (mt_rand(0, 100) < 48)) {
                $cells[] = [$x, $y];
            }
        }
    }
    mt_srand();
@endphp

<svg viewBox="-2 -2 {{ $n + 4 }} {{ $n + 4 }}" {{ $attributes->merge(['class' => 'rounded-xl bg-white']) }} shape-rendering="crispEdges" aria-label="QR Code Pix">
    <rect x="-2" y="-2" width="{{ $n + 4 }}" height="{{ $n + 4 }}" fill="#fff"/>
    @foreach ($cells as [$x, $y])<rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#0F1E33"/>@endforeach
    <rect x="{{ $n / 2 - 3 }}" y="{{ $n / 2 - 3 }}" width="6" height="6" rx="1.2" fill="#fff"/>
    <circle cx="{{ $n / 2 }}" cy="{{ $n / 2 }}" r="2.2" fill="#C8A15A"/>
</svg>
