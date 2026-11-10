<?php
$c = file_get_contents('public/assets/app/css/tailwind.css');
$blade = file_get_contents('resources/views/dashboard.blade.php');
$layout = file_get_contents('resources/views/layouts/main.blade.php');
$side = file_get_contents('resources/views/partials/sidebar.blade.php');

function collectClasses(string $html): array {
    $out = [];
    // ambil isi atribut class="..." (tanpa interpolation blade)
    if (preg_match_all('/class="([^"]*)"/', $html, $m)) {
        foreach ($m[1] as $chunk) {
            foreach (preg_split('/\s+/', trim($chunk)) as $cls) {
                $cls = trim($cls);
                if ($cls === '') continue;
                if (str_contains($cls, '{{') || str_contains($cls, '{!!')) continue;
                $out[$cls] = true;
            }
        }
    }
    return array_keys($out);
}

$all = array_merge(
    collectClasses($blade),
    collectClasses($layout),
    collectClasses($side)
);
$all = array_values(array_unique($all));
sort($all);

$missing = [];
foreach ($all as $cls) {
    if (! str_contains($c, '.'.$cls)) {
        $missing[] = $cls;
    }
}
echo "Total kelas unik di blade: ".count($all)."\n";
echo "TIDAK ADA di CSS build    : ".count($missing)."\n\n";
foreach ($missing as $m) echo "  - $m\n";
