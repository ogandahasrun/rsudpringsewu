<?php
// Mock holidays lookup representing what is in database for 2026:
// 2026-01-01 -> LIBUR TAHUN BARU
// 2026-01-16 -> ISRO MIROJ
// 2026-03-18 -> idul fitri
// 2026-03-19 -> cuti bersama idul fitri
// 2026-05-01 -> HARI BURUH
// 2026-05-14 -> LIBUR PASKAH
// 2026-05-27 -> IDUL ADHA
$holidays_lookup = [
    '2026-01-01' => true,
    '2026-01-16' => true,
    '2026-03-18' => true,
    '2026-03-19' => true,
    '2026-05-01' => true,
    '2026-05-14' => true,
    '2026-05-27' => true,
];

function calculate_leave_days($tanggal_awal, $tanggal_akhir, $holidays_lookup) {
    $start_ts = strtotime($tanggal_awal);
    $end_ts = strtotime($tanggal_akhir);
    
    if ($end_ts < $start_ts) {
        return -1; // Error
    }
    
    $jumlah = 0;
    $current_ts = $start_ts;
    while ($current_ts <= $end_ts) {
        $current_date = date('Y-m-d', $current_ts);
        $day_of_week = (int)date('w', $current_ts); // 0 = Minggu
        
        if ($day_of_week !== 0 && !isset($holidays_lookup[$current_date])) {
            $jumlah++;
        }
        
        $current_ts = strtotime("+1 day", $current_ts);
    }
    return $jumlah;
}

$tests = [
    // 1. Same day, weekday (Monday) -> should be 1
    ['2026-07-06', '2026-07-06', 1],
    // 2. Same day, Sunday -> should be 0
    ['2026-07-05', '2026-07-05', 0],
    // 3. Same day, Holiday (Labor Day Friday 2026-05-01) -> should be 0
    ['2026-05-01', '2026-05-01', 0],
    // 4. Saturday to Monday (includes Sunday) -> Saturday (1), Sunday (0), Monday (1) = 2
    ['2026-07-04', '2026-07-06', 2],
    // 5. Thursday 2026-04-30 to Saturday 2026-05-02 (includes Friday 2026-05-01 holiday) -> Thursday (1), Friday (0), Saturday (1) = 2
    ['2026-04-30', '2026-05-02', 2],
    // 6. Monday to Sunday (includes Sunday) -> Mon-Sat (6), Sun (0) = 6
    ['2026-07-06', '2026-07-12', 6],
];

$failed = false;
foreach ($tests as $i => $test) {
    $result = calculate_leave_days($test[0], $test[1], $holidays_lookup);
    if ($result === $test[2]) {
        echo "Test " . ($i + 1) . " PASSED: {$test[0]} to {$test[1]} returned {$result}\n";
    } else {
        echo "Test " . ($i + 1) . " FAILED: {$test[0]} to {$test[1]} returned {$result}, expected {$test[2]}\n";
        $failed = true;
    }
}

if ($failed) {
    exit(1);
} else {
    echo "All tests passed successfully!\n";
}
?>
