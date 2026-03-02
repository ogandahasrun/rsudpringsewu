<?php
// Handle AJAX request untuk panggil pasien - harus di paling atas
if (isset($_POST['action']) && $_POST['action'] === 'panggil_pasien') {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers untuk JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Include koneksi
    include 'koneksi.php';
    
    // Set timezone Jakarta
    date_default_timezone_set('Asia/Jakarta');
    
    // Validasi input
    if (!isset($_POST['nama_pasien']) || empty($_POST['nama_pasien']) ||
        !isset($_POST['no_reg']) || empty($_POST['no_reg']) ||
        !isset($_POST['no_rawat']) || empty($_POST['no_rawat'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    $nama_pasien = $_POST['nama_pasien'];
    $no_reg = $_POST['no_reg'];
    $no_rawat = $_POST['no_rawat'];
    $kalimat_panggil = 'Pasien atas nama ' . $nama_pasien . ', nomor antrian ' . $no_reg . ', silakan menuju poliklinik.';
    $waktu_panggil = date('Y-m-d H:i:s');
    
    // Simpan ke database
    $insert_sql = "INSERT INTO antrian_panggilan_poliklinik (nama_pasien, no_reg, no_rawat, kalimat_panggil, waktu_panggil, status_tampil) VALUES (?, ?, ?, ?, ?, 'belum')";
    
    $stmt = $koneksi->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("sssss", $nama_pasien, $no_reg, $no_rawat, $kalimat_panggil, $waktu_panggil);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Panggilan berhasil dikirim ke display']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan panggilan']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal prepare statement']);
    }
    
    mysqli_close($koneksi);
    exit;
}

// Suppress any output buffering and errors untuk clean JSON response
ob_start();

include 'koneksi.php';

// Clear output buffer untuk halaman normal
ob_end_clean();

date_default_timezone_set('Asia/Jakarta');
function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('j', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return "$tgl $bln $thn";
}

// Ambil filter dari form
$tgl_registrasi = isset($_GET['tgl_registrasi']) ? $_GET['tgl_registrasi'] : date('Y-m-d');
$nm_poli = isset($_GET['nm_poli']) ? $_GET['nm_poli'] : '';
$nm_dokter = isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : '';

// Ambil nama organisasi dari database
$nama_organisasi = '';
$org_query = "SELECT nama_instansi FROM setting LIMIT 1";
$org_result = mysqli_query($koneksi, $org_query);
if ($org_result && mysqli_num_rows($org_result) > 0) {
    $org_row = mysqli_fetch_assoc($org_result);
    $nama_organisasi = $org_row['nama_instansi'];
}

// Ambil opsi poliklinik dari database
$poli_options = [];
$poli_query = "SELECT DISTINCT nm_poli FROM poliklinik ORDER BY nm_poli";
$poli_result = mysqli_query($koneksi, $poli_query);
while ($row = mysqli_fetch_assoc($poli_result)) {
    if ($row['nm_poli'] !== '') $poli_options[] = $row['nm_poli'];
}

// Ambil opsi dokter dari database
$dokter_options = [];
$dokter_query = "SELECT DISTINCT nm_dokter FROM dokter ORDER BY nm_dokter";
$dokter_result = mysqli_query($koneksi, $dokter_query);
while ($row = mysqli_fetch_assoc($dokter_result)) {
    if ($row['nm_dokter'] !== '') $dokter_options[] = $row['nm_dokter'];
}

// Query pasien berdasarkan filter
$sql = "SELECT
    reg_periksa.no_reg,
    reg_periksa.no_rawat,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    poliklinik.nm_poli,
    dokter.nm_dokter,
    reg_periksa.kd_pj
FROM
    reg_periksa
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
WHERE
    reg_periksa.stts = 'Belum' AND
    reg_periksa.tgl_registrasi = ?";

$params = [$tgl_registrasi];
$types = "s";

if ($nm_poli !== '') {
    $sql .= " AND poliklinik.nm_poli = ?";
    $params[] = $nm_poli;
    $types .= "s";
}

if ($nm_dokter !== '') {
    $sql .= " AND dokter.nm_dokter = ?";
    $params[] = $nm_dokter;
    $types .= "s";
}

$sql .= " ORDER BY reg_periksa.no_reg ASC, reg_periksa.no_rawat ASC";

$stmt = $koneksi->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konsol Antrian Poliklinik</title>
    <meta http-equiv="refresh" content="30">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .title {
            text-align: center;
            margin-bottom: 10px;
            font-size: 26px;
            font-weight: bold;
            color: #007bff;
        }
        .subtitle {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .date {
            text-align: center;
            color: #555;
            font-size: 16px;
            margin-bottom: 20px;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        form label {
            margin-right: 8px;
            font-weight: 600;
        }
        form input, form select {
            margin-right: 16px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        form button {
            padding: 8px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        form button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #888;
            font-style: italic;
        }
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .stat-card {
            background: linear-gradient(45deg, #007bff, #20c997);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            min-width: 120px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        .btn-panggil {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-panggil:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
        .btn-panggil:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                max-width: 100%;
            }
            .title {
                font-size: 22px;
            }
            .date {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
                font-size: 13px;
            }
            .stats-container {
                flex-direction: column;
            }
            .stat-card {
                min-width: auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="title">DASHBOARD ANTRIAN POLIKLINIK</div>
    <div class="subtitle"><?= htmlspecialchars($nama_organisasi) ?></div>
    <div class="date"><?= tanggal_indo($tgl_registrasi) ?></div>

    <!-- Filter Form -->
    <form method="get">
        <label for="tgl_registrasi">📅 Tanggal Registrasi:</label>
        <input type="date" id="tgl_registrasi" name="tgl_registrasi" value="<?= htmlspecialchars($tgl_registrasi) ?>" required>
        
        <label for="nm_poli">🏥 Poliklinik:</label>
        <select id="nm_poli" name="nm_poli">
            <option value="">Semua Poliklinik</option>
            <?php foreach ($poli_options as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($nm_poli == $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>
        
        <label for="nm_dokter">👨‍⚕️ Dokter:</label>
        <select id="nm_dokter" name="nm_dokter">
            <option value="">Semua Dokter</option>
            <?php foreach ($dokter_options as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($nm_dokter == $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">🔍 Filter</button>
    </form>

    <?php 
    // Hitung statistik
    $total_pasien = 0;
    $poli_counts = [];
    $dokter_counts = [];
    if ($result) {
        $data_array = [];
        while ($row = $result->fetch_assoc()) {
            $data_array[] = $row;
            $total_pasien++;
            $poli = $row['nm_poli'];
            $dokter = $row['nm_dokter'];
            $poli_counts[$poli] = isset($poli_counts[$poli]) ? $poli_counts[$poli] + 1 : 1;
            $dokter_counts[$dokter] = isset($dokter_counts[$dokter]) ? $dokter_counts[$dokter] + 1 : 1;
        }
        
        // Reset result untuk digunakan lagi
        $result->data_seek(0);
    }
    ?>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-number"><?= $total_pasien ?></span>
            <span class="stat-label">Total Pasien</span>
        </div>
        <?php if ($nm_poli !== '' && isset($poli_counts[$nm_poli])): ?>
            <div class="stat-card">
                <span class="stat-number"><?= $poli_counts[$nm_poli] ?></span>
                <span class="stat-label"><?= htmlspecialchars($nm_poli) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($nm_dokter !== '' && isset($dokter_counts[$nm_dokter])): ?>
            <div class="stat-card">
                <span class="stat-number"><?= $dokter_counts[$nm_dokter] ?></span>
                <span class="stat-label">Dr. <?= htmlspecialchars($nm_dokter) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Antrian</th>
                <th>No. Rawat</th>
                <th>No. RM</th>
                <th>Nama Pasien</th>
                <th>Poliklinik</th>
                <th>Dokter</th>
                <th>Jenis Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php 
                $no = 1;
                while ($row = $result->fetch_assoc()): 
                ?>
                    <tr id="row-<?= htmlspecialchars($row['no_reg']) ?>">
                        <td style="text-align: center; font-weight: bold;"><?= $no++ ?></td>
                        <td style="font-family: monospace; text-align: center; font-weight: bold; font-size: 16px;"><?= htmlspecialchars($row['no_reg']) ?></td>
                        <td style="font-family: monospace;"><?= htmlspecialchars($row['no_rawat']) ?></td>
                        <td style="font-family: monospace; text-align: center;"><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['nm_pasien']) ?></td>
                        <td><?= htmlspecialchars($row['nm_poli']) ?></td>
                        <td><?= htmlspecialchars($row['nm_dokter']) ?></td>
                        <td style="text-align: center;">
                            <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($row['kd_pj']) ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <button class="btn-panggil" 
                                    data-nama="<?= htmlspecialchars($row['nm_pasien']) ?>"
                                    data-noreg="<?= htmlspecialchars($row['no_reg']) ?>"
                                    data-norawat="<?= htmlspecialchars($row['no_rawat']) ?>"
                                    title="Panggil pasien">
                                🔊 Panggil
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="no-data">
                        📋 Tidak ada data pasien untuk tanggal dan filter yang dipilih.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="position:fixed;left:0;right:0;bottom:0;background:#007bff;color:#fff;padding:8px 0;z-index:99;">
    <marquee behavior="scroll" direction="left" scrollamount="8" style="font-size:16px;font-family:Tahoma, Geneva, Verdana, sans-serif;">
        ⚕️ Selamat datang di Poliklinik - Mohon harap menunggu antrian dipanggil - Terima kasih atas kesabaran Anda
    </marquee>
</div>

<script>
$(document).ready(function() {
    // Handler tombol panggil - kirim ke database untuk display
    $('.btn-panggil').click(function() {
        var button = $(this);
        var namaPasien = button.data('nama');
        var noReg = button.data('noreg');
        var noRawat = button.data('norawat');
        
        // Disable button sementara
        button.prop('disabled', true);
        button.html('📡 Mengirim...');
        
        // Kirim data ke database
        $.ajax({
            url: window.location.href,
            type: 'POST',
            timeout: 5000,
            data: {
                action: 'panggil_pasien',
                nama_pasien: namaPasien,
                no_reg: noReg,
                no_rawat: noRawat
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    button.html('✅ Terkirim');
                    // Auto reset button setelah 3 detik
                    setTimeout(function() {
                        button.prop('disabled', false);
                        button.html('🔊 Panggil');
                    }, 3000);
                } else {
                    var errorMsg = response && response.message ? response.message : 'Gagal mengirim panggilan';
                    alert('❌ Error: ' + errorMsg);
                    button.prop('disabled', false);
                    button.html('🔊 Panggil');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                alert('❌ Gagal mengirim panggilan');
                button.prop('disabled', false);
                button.html('🔊 Panggil');
            }
        });
    });
});
</script>

</body>
</html>