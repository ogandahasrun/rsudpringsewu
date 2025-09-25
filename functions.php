<?php
function terbilang($angka) {
    $angka = round(abs($angka)); // pembulatan ke atas
    $bilangan = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $terbilang = "";

    if ($angka < 12) {
        $terbilang = " " . $bilangan[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka - 10) . " belas ";
    } elseif ($angka < 100) {
        $terbilang = terbilang(intval($angka / 10)) . " puluh " . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = " seratus " . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang(intval($angka / 100)) . " ratus " . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = " seribu " . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang(intval($angka / 1000)) . " ribu " . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang(intval($angka / 1000000)) . " juta " . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $terbilang = terbilang(intval($angka / 1000000000)) . " milyar " . terbilang($angka % 1000000000);
    }

    return trim($terbilang);
}
?>