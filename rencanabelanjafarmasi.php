<?php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rencana Belanja Farmasi</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .container { max-width: 900px; margin: 30px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);}
        h1 { text-align: center; color: #4CAF50; margin-bottom: 24px; }
        .filter-form { margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: center;}
        .filter-form label { margin-right: 6px; }
        .filter-form select { padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc; }
        .filter-form button { padding: 6px 18px; background: #4CAF50; color: #fff; border: none; border-radius: 4px; cursor: pointer;}
        .filter-form button:hover { background: #388E3C; }
        .back-button { margin-bottom: 16px; }
        .back-button a { color: #fff; background: #6c757d; padding: 6px 16px; border-radius: 4px; text-decoration: none;}
        .back-button a:hover { background: #495057; }
        .copy-btn { margin-bottom: 16px; background: #2196F3; color: #fff; border: none; padding: 6px 18px; border-radius: 4px; cursor: pointer;}
        .copy-btn:hover { background: #1976D2; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; font-size: 13px; }
        th { background: #4CAF50; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        tr:hover { background: #e3f2fd; }
        .no-data { text-align: center; color: #888; padding: 20px; }
        @media (max-width: 700px) {
            .container { padding: 8px; }
            .filter-form { flex-direction: column; gap: 8px;}
            th, td { font-size: 12px; padding: 6px;}
        }
    </style>

</head>
<body>
<div class="container" id="allTables">
    <h1>Rencana Belanja Farmasi</h1>
    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>

    <button class="copy-btn" id="copyTableBtn">Copy Tabel ke Clipboard</button>
</div>

//query utama
$query = "SELECT
        databarang.kode_brng,
        databarang.nama_brng,
        databarang.kode_sat
        FROM
        databarang";

//query untuk menampilkan data stok berdasarkan lokasi
$query = "SELECT
        gudangbarang.kode_brng,
        gudangbarang.kd_bangsal,
        gudangbarang.stok
        FROM
        gudangbarang
        ";

//query untuk menghitung stok keluar
$query = "SELECT
        detail_pengeluaran_obat_bhp.kode_brng,
        Sum(detail_pengeluaran_obat_bhp.jumlah)
        FROM
        pengeluaran_obat_bhp
        INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
        WHERE
        pengeluaran_obat_bhp.kd_bangsal = 'go'
        pengeluaran_obat_bhp.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND
        GROUP BY
        detail_pengeluaran_obat_bhp.kode_brng
        ORDER BY
        detail_pengeluaran_obat_bhp.kode_brng ASC 
        ";

//query untuk menghitung pengeluaran obat ke pasien
$query = "SELECT
        detail_pemberian_obat.kode_brng,
        Sum(detail_pemberian_obat.jml)
        FROM
        detail_pemberian_obat
        INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
        WHERE
        detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        GROUP BY
        detail_pemberian_obat.kode_brng
        ";

//query untuk menghitung resep yang diberikan saat pasien pulang
$query = "SELECT
        resep_pulang.kode_brng,
        Sum(resep_pulang.jml_barang)
        FROM
        resep_pulang
        INNER JOIN databarang ON resep_pulang.kode_brng = databarang.kode_brng
        WHERE
        resep_pulang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        GROUP BY
        resep_pulang.kode_brng
        ";

<script>
document.getElementById('copyTableBtn').onclick = function() {
    var allTables = document.getElementById('allTables');
    var range = document.createRange();
    range.selectNode(allTables);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            alert('Tabel berhasil disalin ke clipboard!');
        } else {
            alert('Gagal menyalin tabel.');
        }
    } catch (err) {
        alert('Browser tidak mendukung copy tabel otomatis.');
    }
    window.getSelection().removeAllRanges();
};
</script>

</body>
</html>