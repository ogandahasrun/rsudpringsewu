<?php
// Handle AJAX request untuk update penyerahan resep - harus di paling atas
if (isset($_POST['action']) && $_POST['action'] === 'update_penyerahan') {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers untuk JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Include koneksi
    include 'koneksi.php';
    
    // Validasi input
    if (!isset($_POST['no_resep']) || empty($_POST['no_resep'])) {
        echo json_encode(['success' => false, 'message' => 'No resep tidak valid']);
        exit;
    }
    
    $no_resep = $_POST['no_resep'];
    
    // Set timezone Jakarta
    date_default_timezone_set('Asia/Jakarta');
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Update tgl_penyerahan dan jam_penyerahan berdasarkan no_resep
    $update_sql = "UPDATE resep_obat SET tgl_penyerahan = ?, jam_penyerahan = ? WHERE no_resep = ? LIMIT 1";
    
    $stmt = $koneksi->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("sss", $current_date, $current_time, $no_resep);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Resep berhasil diserahkan']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No resep tidak ditemukan']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update database']);
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
$tgl_peresepan = isset($_GET['tgl_peresepan']) ? $_GET['tgl_peresepan'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil nama organisasi dari database
$nama_organisasi = '';
$org_query = "SELECT nama_instansi FROM setting LIMIT 1";
$org_result = mysqli_query($koneksi, $org_query);
if ($org_result && mysqli_num_rows($org_result) > 0) {
    $org_row = mysqli_fetch_assoc($org_result);
    $nama_organisasi = $org_row['nama_instansi'];
}

// Ambil opsi status dari database
$status_options = [];
$status_query = "SELECT DISTINCT `status` FROM resep_obat ORDER BY `status`";
$status_result = mysqli_query($koneksi, $status_query);
while ($row = mysqli_fetch_assoc($status_result)) {
    if ($row['status'] !== '') $status_options[] = $row['status'];
}

// Query pasien berdasarkan tgl_peresepan dengan filter
$sql = "SELECT
    resep_obat.no_resep,
    resep_obat.tgl_peresepan,
    resep_obat.jam_peresepan,
    reg_periksa.no_rawat,
    reg_periksa.kd_poli,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    resep_obat.`status`,
    resep_obat.tgl_penyerahan
FROM
    resep_obat
INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
WHERE
    resep_obat.tgl_penyerahan = '0000-00-00'
    AND resep_obat.tgl_peresepan = ?
";

$params = [$tgl_peresepan];
$types = "s";

if ($status_filter !== '') {
    $sql .= " AND resep_obat.`status` = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY resep_obat.`status` ASC, resep_obat.tgl_peresepan ASC, resep_obat.jam_peresepan ASC";

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
    <title>Dashboard Antrian Farmasi</title>
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
            color: #28a745;
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
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        form button:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th {
            background-color: #28a745;
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
            background: linear-gradient(45deg, #28a745, #20c997);
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
        .btn-serahkan {
            background: #28a745;
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
        .btn-serahkan:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .btn-serahkan:disabled {
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
    <div class="title">DASHBOARD ANTRIAN FARMASI</div>
    <div class="subtitle"><?= htmlspecialchars($nama_organisasi) ?></div>
    <div class="date"><?= tanggal_indo($tgl_peresepan) ?></div>

    <!-- Filter Form -->
    <form method="get">
        <label for="tgl_peresepan">📅 Tanggal Peresepan:</label>
        <input type="date" id="tgl_peresepan" name="tgl_peresepan" value="<?= htmlspecialchars($tgl_peresepan) ?>" required>
        <label for="status">📋 Status Resep:</label>
        <select id="status" name="status">
            <option value="">Semua Status</option>
            <?php foreach ($status_options as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($status_filter == $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Filter</button>
    </form>

    <?php 
    // Hitung statistik
    $total_resep = 0;
    $status_counts = [];
    if ($result) {
        $data_array = [];
        while ($row = $result->fetch_assoc()) {
            $data_array[] = $row;
            $total_resep++;
            $status = $row['status'];
            $status_counts[$status] = isset($status_counts[$status]) ? $status_counts[$status] + 1 : 1;
        }
        
        // Reset result untuk digunakan lagi
        $result->data_seek(0);
    }
    ?>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-number"><?= $total_resep ?></span>
            <span class="stat-label">Total Resep</span>
        </div>
        <?php foreach ($status_counts as $status => $count): ?>
            <div class="stat-card">
                <span class="stat-number"><?= $count ?></span>
                <span class="stat-label"><?= htmlspecialchars($status) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Rawat</th>
                <th>Nomor Rekam Medik</th>
                <th>Nama Pasien</th>
                <th>Poli</th>
                <th>Status Resep</th>
                <th>No. Resep & Jam</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php 
                $no = 1;
                while ($row = $result->fetch_assoc()): 
                ?>
                    <tr id="row-<?= htmlspecialchars($row['no_resep']) ?>">
                        <td style="text-align: center; font-weight: bold;"><?= $no++ ?></td>
                        <td style="font-family: monospace;"><?= htmlspecialchars($row['no_rawat']) ?></td>
                        <td style="font-family: monospace; text-align: center;"><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['nm_pasien']) ?></td>
                        <td><?= htmlspecialchars($row['kd_poli']) ?></td>
                        <td>
                            <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: center; font-size: 12px;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['no_resep']) ?></div>
                            <div style="color: #6c757d;"><?= date('H:i', strtotime($row['jam_peresepan'])) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <button class="btn-serahkan" 
                                    data-no-resep="<?= htmlspecialchars($row['no_resep']) ?>" 
                                    data-nama="<?= htmlspecialchars($row['nm_pasien']) ?>"
                                    data-jam="<?= date('H:i', strtotime($row['jam_peresepan'])) ?>"
                                    title="Tandai sebagai sudah diserahkan">
                                ✅ Serahkan
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="no-data">
                        📋 Tidak ada data resep untuk tanggal dan filter yang dipilih.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="position:fixed;left:0;right:0;bottom:0;background:#28a745;color:#fff;padding:8px 0;z-index:99;">
    <marquee behavior="scroll" direction="left" scrollamount="8" style="font-size:16px;font-family:Tahoma, Geneva, Verdana, sans-serif;">
        💊 Dashboard Farmasi - Antrian Resep | Klik tombol "Serahkan" untuk menandai resep sudah diserahkan
    </marquee>
</div>

<script>
$(document).ready(function() {
    // Handler untuk tombol serahkan
    $('.btn-serahkan').click(function() {
        var button = $(this);
        var noResep = button.data('no-resep');
        var namaPasien = button.data('nama');
        var jamPeresepan = button.data('jam');
        
        // Konfirmasi sebelum update dengan informasi lebih detail
        if (confirm('Apakah Anda yakin resep No. ' + noResep + ' untuk pasien "' + namaPasien + '" (jam ' + jamPeresepan + ') sudah diserahkan?')) {
            // Disable button dan show loading
            button.prop('disabled', true);
            button.html('⏳ Processing...');
            
            // AJAX request dengan timeout pendek
            $.ajax({
                url: window.location.href,
                type: 'POST',
                timeout: 3000, // 3 detik timeout
                data: {
                    action: 'update_penyerahan',
                    no_resep: noResep
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Success response:', response); // Debug log
                    
                    if (response && response.success) {
                        // Update button menjadi success state (non-blocking)
                        button.prop('disabled', true).html('✅ Berhasil');

                        // Hapus baris menggunakan tombol sebagai referensi (lebih andal)
                        var $tr = button.closest('tr');
                        $tr.fadeOut(120, function() {
                            $(this).remove();

                            // Check jika tbody kosong
                            if ($('tbody tr').length === 0) {
                                $('tbody').html('<tr><td colspan="8" class="no-data">📋 Tidak ada data resep untuk tanggal dan filter yang dipilih.</td></tr>');
                            }

                            // Update statistik segera
                            updateStatistics();
                        });
                    } else {
                        console.log('Response success is false or undefined:', response);
                        var errorMsg = response && response.message ? response.message : 'Response tidak valid';
                        alert('❌ Error: ' + errorMsg);
                        // Re-enable button
                        button.prop('disabled', false);
                        button.html('✅ Serahkan');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', status, error);
                    
                    var errorMsg = 'Terjadi kesalahan';
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout';
                    }
                    
                    alert('❌ ' + errorMsg);
                    // Re-enable button
                    button.prop('disabled', false);
                    button.html('✅ Serahkan');
                }
            });
        }
    });
    
    // Function to update statistics
    function updateStatistics() {
        var totalRows = $('tbody tr').length;
        if (totalRows === 1 && $('tbody tr td').attr('colspan')) {
            totalRows = 0; // No data row
        }
        
        // Update total resep if stats exist
        $('.stat-number').first().text(totalRows);
    }
});
</script>

</body>
</html>