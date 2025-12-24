<?php
include 'koneksi.php';

// Ambil daftar suplier untuk dropdown
$suplier_options = [];
$suplier_query = "SELECT nama_suplier FROM datasuplier ORDER BY nama_suplier";
$suplier_result = mysqli_query($koneksi, $suplier_query);
while ($row = mysqli_fetch_assoc($suplier_result)) {
    $suplier_options[] = $row['nama_suplier'];
}

// Ambil filter dari form
$tgl_pesan_awal = isset($_GET['tgl_pesan_awal']) ? $_GET['tgl_pesan_awal'] : date('Y-m-d');
$tgl_pesan_akhir = isset($_GET['tgl_pesan_akhir']) ? $_GET['tgl_pesan_akhir'] : date('Y-m-d');
$tgl_faktur_awal = isset($_GET['tgl_faktur_awal']) ? $_GET['tgl_faktur_awal'] : '';
$tgl_faktur_akhir = isset($_GET['tgl_faktur_akhir']) ? $_GET['tgl_faktur_akhir'] : '';
$nama_suplier = isset($_GET['nama_suplier']) ? $_GET['nama_suplier'] : '';

// Bangun WHERE clause
$where = [];
if (!empty($tgl_pesan_awal) && !empty($tgl_pesan_akhir)) {
    $where[] = "pemesanan.tgl_pesan BETWEEN '$tgl_pesan_awal' AND '$tgl_pesan_akhir'";
}
if (!empty($tgl_faktur_awal) && !empty($tgl_faktur_akhir)) {
    $where[] = "pemesanan.tgl_faktur BETWEEN '$tgl_faktur_awal' AND '$tgl_faktur_akhir'";
}
if (!empty($nama_suplier)) {
    $where[] = "datasuplier.nama_suplier = '" . mysqli_real_escape_string($koneksi, $nama_suplier) . "'";
}
if (empty($where)) {
    // Default: tampilkan data tgl_pesan hari ini
    $today = date('Y-m-d');
    $where[] = "pemesanan.tgl_pesan = '$today'";
}
$where_sql = implode(' AND ', $where);

// Query data

// Query utama data faktur
$sql = "SELECT
    pemesanan.tgl_pesan,
    pemesanan.tgl_faktur,
    pemesanan.no_faktur,
    datasuplier.nama_suplier,
    detailpesan.kode_brng,
    databarang.nama_brng,
    detailpesan.jumlah,
    databarang.kode_sat
FROM
    pemesanan
INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
WHERE $where_sql
ORDER BY pemesanan.tgl_pesan DESC, pemesanan.no_faktur DESC";
$result = mysqli_query($koneksi, $sql);

// Query status input jenis_barang & cara_belanja
$cb_query = "SELECT no_faktur, jenis_barang, cara_belanja FROM pemesanan_cara_belanja";
$cb_result = mysqli_query($koneksi, $cb_query);
$cb_status = [];
if ($cb_result) {
    while ($cb_row = mysqli_fetch_assoc($cb_result)) {
        $cb_status[$cb_row['no_faktur']] = [
            'jenis_barang' => $cb_row['jenis_barang'],
            'cara_belanja' => $cb_row['cara_belanja']
        ];
    }
}

// Query untuk mengecek status foto
$foto_query = "SELECT no_faktur, foto1, foto2, foto3, foto4, foto5, foto6, foto7, foto8, foto9, foto10 FROM pemesanan_dokumentasi";
$foto_result = mysqli_query($koneksi, $foto_query);
$foto_status = [];
if ($foto_result) {
    while ($foto_row = mysqli_fetch_assoc($foto_result)) {
        $no_faktur = $foto_row['no_faktur'];
        $foto_count = 0;
        for ($i = 1; $i <= 10; $i++) {
            if (!empty($foto_row['foto' . $i])) {
                $foto_count++;
            }
        }
        $foto_status[$no_faktur] = $foto_count;
    }
}

// Proses data untuk rowspan
$data = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['tgl_pesan'] . '|' . $row['tgl_faktur'] . '|' . $row['no_faktur'] . '|' . $row['nama_suplier'];
        if (!isset($data[$key])) $data[$key] = [];
        $data[$key][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Faktur Barang Medis</title>
    <style>
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .back-button a:hover {
            background: #5a6268;
        }
        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .filter-form label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        .filter-form input, .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-width: 150px;
        }
        .filter-form button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .filter-form button:hover {
            background: #0056b3;
        }
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            min-width: 800px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
        }
        th {
            background: #007bff;
            color: #fff;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        tr:hover {
            background: #e3f2fd;
        }
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            position: relative;
            transition: all 0.3s ease;
        }
        
        /* Status foto - Belum ada foto (merah) */
        .action-btn.no-photo {
            background: #dc3545;
        }
        .action-btn.no-photo:hover {
            background: #c82333;
        }
        .action-btn.no-photo::before {
            content: "📷 ";
        }
        .action-btn.no-photo {
            animation: pulse-red 2s infinite;
        }
        
        /* Status foto - Ada foto minimal 1 (hijau) */
        .action-btn.has-photo {
            background: #28a745;
        }
        .action-btn.has-photo:hover {
            background: #218838;
        }
        .action-btn.has-photo::before {
            content: "✅ ";
        }
        
        /* Animasi pulse untuk status foto */
        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        }
        
        /* Badge untuk jumlah foto */
        .action-btn .photo-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 1px 6px;
            font-size: 10px;
            margin-left: 5px;
            font-weight: bold;
        }
        
        /* Legend Status Foto */
        .photo-legend {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .photo-legend h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
            font-size: 14px;
        }
        .legend-items {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 11px;
            font-weight: bold;
            min-width: 90px;
            text-align: center;
        }
        .legend-color.no-photo {
            background: #dc3545;
        }
        .legend-color.has-photo {
            background: #28a745;
        }
        .legend-desc {
            font-size: 12px;
            color: #666;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 30px;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
            }
            h1 {
                font-size: 1.5em;
                margin-bottom: 20px;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            .filter-form input, .filter-form select {
                min-width: 100%;
                margin-bottom: 10px;
            }
            .filter-form button {
                width: 100%;
                padding: 12px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            .action-btn {
                padding: 8px 12px;
                font-size: 11px;
            }
            .action-btn .photo-badge {
                font-size: 9px;
                margin-left: 3px;
                padding: 1px 4px;
            }
            .photo-legend {
                padding: 12px;
            }
            .legend-items {
                flex-direction: column;
                gap: 10px;
            }
            .legend-item {
                gap: 6px;
            }
            .legend-color {
                min-width: 80px;
                font-size: 10px;
                padding: 3px 6px;
            }
            .legend-desc {
                font-size: 11px;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            h1 {
                font-size: 1.3em;
            }
            th, td {
                padding: 6px 4px;
                font-size: 11px;
            }
            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-button">
            <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
        </div>
        
        <h1>Daftar Faktur Barang Medis</h1>
        
        <form method="get" class="filter-form">
        <label>Periode Tgl Pesan:</label>
        <input type="date" name="tgl_pesan_awal" value="<?php echo htmlspecialchars($tgl_pesan_awal); ?>">
        <input type="date" name="tgl_pesan_akhir" value="<?php echo htmlspecialchars($tgl_pesan_akhir); ?>">
        <label>Periode Tgl Faktur:</label>
        <input type="date" name="tgl_faktur_awal" value="<?php echo htmlspecialchars($tgl_faktur_awal); ?>">
        <input type="date" name="tgl_faktur_akhir" value="<?php echo htmlspecialchars($tgl_faktur_akhir); ?>">
        <label>Nama Suplier:</label>
        <select name="nama_suplier">
            <option value="">Semua</option>
            <?php foreach ($suplier_options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nama_suplier == $opt) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <!-- Legend Status Foto -->
    <div class="photo-legend">
        <h4>📊 Status Upload Foto:</h4>
        <div class="legend-items">
            <div class="legend-item">
                <span class="legend-color no-photo">📷 Belum Upload</span>
                <span class="legend-desc">Belum ada foto yang diupload (0/10)</span>
            </div>
            <div class="legend-item">
                <span class="legend-color has-photo">✅ Sudah Upload</span>
                <span class="legend-desc">Minimal 1 foto sudah diupload (1-10/10)</span>
            </div>
        </div>
    </div>

        <div class="table-container">
            <table>
        <tr>
            <th>Tgl Pesan</th>
            <th>Tgl Faktur</th>
            <th>No Faktur</th>
            <th>Nama Suplier</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Kode Satuan</th>
            <th>Status Input</th>
            <th>Aksi</th>
        </tr>
        <?php if (!empty($data)): ?>
            <?php foreach ($data as $key => $rows): 
                $rowspan = count($rows);
                $first = true;
                foreach ($rows as $row): ?>
                <tr>
                    <?php if ($first): ?>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['tgl_pesan']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['tgl_faktur']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['no_faktur']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['nama_suplier']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                    <td><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                    <?php if ($first): ?>
                        <td rowspan="<?= $rowspan ?>" style="text-align:center; font-weight:bold;">
                            <?php
                            $no_faktur = $row['no_faktur'];
                            if (isset($cb_status[$no_faktur]) && $cb_status[$no_faktur]['jenis_barang'] && $cb_status[$no_faktur]['cara_belanja']) {
                                echo '<span style="color:#28a745;">Lengkap</span>';
                            } else {
                                echo '<span style="color:#dc3545;">Belum</span>';
                            }
                            ?>
                        </td>
                        <td rowspan="<?= $rowspan ?>">
                            <?php
                            $foto_count = isset($foto_status[$no_faktur]) ? $foto_status[$no_faktur] : 0;
                            // Tombol upload foto
                            if ($foto_count == 0) {
                                $btn_class = 'no-photo';
                                $btn_text = 'Upload Foto';
                                $btn_title = 'Belum ada foto yang diupload';
                            } else {
                                $btn_class = 'has-photo';
                                $btn_text = 'Lihat/Tambah Foto';
                                $btn_title = $foto_count . ' dari 10 foto sudah diupload';
                            }
                            ?>
                            <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-start;">
                                <a href="dokumentasifaktur.php?no_faktur=<?=urlencode($row['no_faktur'])?>&tgl_pesan_awal=<?=urlencode($tgl_pesan_awal)?>&tgl_pesan_akhir=<?=urlencode($tgl_pesan_akhir)?>&tgl_faktur_awal=<?=urlencode($tgl_faktur_awal)?>&tgl_faktur_akhir=<?=urlencode($tgl_faktur_akhir)?>&nama_suplier=<?=urlencode($nama_suplier)?>" 
                                   class="action-btn <?= $btn_class ?>" 
                                   title="<?= $btn_title ?>">
                                    <?= $btn_text ?>
                                    <span class="photo-badge"><?= $foto_count ?>/10</span>
                                </a>
                                <form class="cara-belanja-form" data-faktur="<?= htmlspecialchars($row['no_faktur']) ?>" onsubmit="return simpanCaraBelanja(this);" style="display:flex; flex-direction:column; gap:4px; margin-top:4px;">
                                    <?php
                                    $jenis_barang_val = isset($cb_status[$row['no_faktur']]) ? $cb_status[$row['no_faktur']]['jenis_barang'] : '';
                                    $cara_belanja_val = isset($cb_status[$row['no_faktur']]) ? $cb_status[$row['no_faktur']]['cara_belanja'] : '';
                                    ?>
                                    <select name="jenis_barang" style="margin-bottom:2px;">
                                        <option value="" <?= $jenis_barang_val==''?'selected':'' ?>>Jenis Barang</option>
                                        <option value="e-katalog" <?= $jenis_barang_val=='e-katalog'?'selected':'' ?>>e-katalog</option>
                                        <option value="non e-katalog" <?= $jenis_barang_val=='non e-katalog'?'selected':'' ?>>non e-katalog</option>
                                    </select>
                                    <select name="cara_belanja" style="margin-bottom:2px;">
                                        <option value="" <?= $cara_belanja_val==''?'selected':'' ?>>Cara Belanja</option>
                                        <option value="e-purchasing" <?= $cara_belanja_val=='e-purchasing'?'selected':'' ?>>e-purchasing</option>
                                        <option value="manual" <?= $cara_belanja_val=='manual'?'selected':'' ?>>manual</option>
                                    </select>
                                    <button type="submit" style="background:#ffc107;color:#333;border:none;padding:5px 10px;border-radius:4px;font-size:12px;cursor:pointer;">💾 Simpan</button>
                                    <span class="cb-status" style="font-size:11px;color:#28a745;display:none;">Tersimpan!</span>
                                </form>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php $first = false; endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" class="no-data">Tidak ada data ditemukan.</td></tr>
        <?php endif; ?>
            </table>
        </div>
    </div>
    <script>
    // AJAX simpan cara belanja
    function simpanCaraBelanja(form) {
        var fd = new FormData(form);
        fd.append('no_faktur', form.getAttribute('data-faktur'));
        var statusSpan = form.querySelector('.cb-status');
        statusSpan.style.display = 'none';
        fetch('simpan_cara_belanja.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                statusSpan.textContent = 'Tersimpan!';
                statusSpan.style.color = '#28a745';
                statusSpan.style.display = 'inline';
            } else {
                statusSpan.textContent = 'Gagal simpan!';
                statusSpan.style.color = '#dc3545';
                statusSpan.style.display = 'inline';
            }
            setTimeout(() => { statusSpan.style.display = 'none'; }, 2000);
        })
        .catch(() => {
            statusSpan.textContent = 'Gagal simpan!';
            statusSpan.style.color = '#dc3545';
            statusSpan.style.display = 'inline';
            setTimeout(() => { statusSpan.style.display = 'none'; }, 2000);
        });
        return false;
    }
    </script>
    <?php mysqli_close($koneksi); ?>
</body>
</html>