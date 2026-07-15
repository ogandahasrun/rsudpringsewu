<?php
// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Sertakan file koneksi.php
include 'koneksi.php';

// Inisialisasi variabel feedback
$success_message = '';
$error_message = '';

// Proses update jika form disubmit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_waktu') {
    $no_rawat = trim($_POST['no_rawat']);
    $old_tanggal = trim($_POST['old_tanggal']);
    $input_tanggal = trim($_POST['tanggal']);
    $input_selesai = trim($_POST['selesaioperasi']);

    if (!empty($no_rawat) && !empty($old_tanggal) && !empty($input_tanggal) && !empty($input_selesai)) {
        // Konversi format datetime-local (Y-m-d\TH:i) ke MySQL datetime format (Y-m-d H:i:s)
        $new_tanggal = date('Y-m-d H:i:s', strtotime($input_tanggal));
        $new_selesaioperasi = date('Y-m-d H:i:s', strtotime($input_selesai));

        // Mulai transaksi untuk memastikan konsistensi data
        $koneksi->begin_transaction();

        try {
            // 1. Update laporan_operasi
            $stmt1 = $koneksi->prepare("UPDATE laporan_operasi SET tanggal = ?, selesaioperasi = ? WHERE no_rawat = ? AND tanggal = ?");
            $stmt1->bind_param("ssss", $new_tanggal, $new_selesaioperasi, $no_rawat, $old_tanggal);
            $stmt1->execute();
            $laporan_updated = $stmt1->affected_rows;
            $stmt1->close();

            // 2. Update operasi (tgl_operasi)
            $stmt2 = $koneksi->prepare("UPDATE operasi SET tgl_operasi = ? WHERE no_rawat = ? AND tgl_operasi = ?");
            $stmt2->bind_param("sss", $new_tanggal, $no_rawat, $old_tanggal);
            $stmt2->execute();
            $operasi_updated = $stmt2->affected_rows;
            $stmt2->close();

            // Fallback: Jika di tabel operasi, tgl_operasi tidak sama persis dengan tanggal laporan_operasi sebelumnya,
            // update semua record operasi untuk no_rawat tersebut.
            if ($operasi_updated === 0) {
                $stmt2_fallback = $koneksi->prepare("UPDATE operasi SET tgl_operasi = ? WHERE no_rawat = ?");
                $stmt2_fallback->bind_param("ss", $new_tanggal, $no_rawat);
                $stmt2_fallback->execute();
                $stmt2_fallback->close();
            }

            // Commit transaksi
            $koneksi->commit();
            $success_message = "Waktu operasi untuk No. Rawat <strong>" . htmlspecialchars($no_rawat) . "</strong> berhasil diupdate.";
        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $error_message = "Semua input harus diisi dengan benar.";
    }
}

// Default filter tanggal: awal bulan s/d hari ini
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$operator_filter = isset($_GET['operator1']) ? $_GET['operator1'] : '';

// Ambil daftar dokter untuk dropdown filter
$dokter_list = [];
$dokter_q = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
if ($dokter_q) {
    while ($d = mysqli_fetch_assoc($dokter_q)) {
        $dokter_list[] = $d;
    }
}

// Query data utama
$where_clauses = ["laporan_operasi.tanggal BETWEEN ? AND ?"];
$bind_types = "ss";
$bind_params = [$tgl_awal . " 00:00:00", $tgl_akhir . " 23:59:59"];

if (!empty($operator_filter)) {
    $where_clauses[] = "operasi.operator1 = ?";
    $bind_types .= "s";
    $bind_params[] = $operator_filter;
}

$sql_query = "SELECT
    laporan_operasi.no_rawat,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    operasi.operator1,
    dokter.nm_dokter,
    laporan_operasi.tanggal,
    laporan_operasi.selesaioperasi
FROM
    laporan_operasi
INNER JOIN reg_periksa ON laporan_operasi.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN operasi ON operasi.no_rawat = reg_periksa.no_rawat
INNER JOIN dokter ON operasi.operator1 = dokter.kd_dokter
WHERE " . implode(" AND ", $where_clauses) . "
ORDER BY laporan_operasi.tanggal DESC";

$stmt = $koneksi->prepare($sql_query);
if ($stmt) {
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $error_message = "Gagal menyiapkan query data: " . $koneksi->error;
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Waktu Operasi - RSUD Pringsewu</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-hover: #0369a1;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Style */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 20px 30px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            width: 50px;
            height: auto;
            border-radius: 8px;
        }

        .header-title-box h1 {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(to right, #38bdf8, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title-box p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-main);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(-3px);
        }

        /* Filter Area */
        .filter-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #38bdf8;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-form .form-group {
            flex: 1 1 220px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            font-family: inherit;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .btn-submit {
            padding: 12px 25px;
            background: linear-gradient(to right, #0284c7, #0ea5e9);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 47px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4);
        }

        .btn-reset {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 47px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Alert styling */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(8px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .alert-close {
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(15, 23, 42, 0.8);
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.9rem;
            color: var(--text-main);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
            padding: 50px 20px;
        }

        /* Badges & Accents */
        .badge-norawat {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .badge-norm {
            background: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .btn-edit {
            padding: 8px 14px;
            background: rgba(2, 132, 199, 0.15);
            border: 1px solid rgba(2, 132, 199, 0.3);
            color: #38bdf8;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: scale(1.03);
        }

        /* Modal Pop-up Styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow-y: auto;
            padding: 40px 15px;
        }

        .modal.open {
            display: flex;
            animation: modalFadeIn 0.3s ease;
        }

        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            margin: auto;
            animation: modalSlideUp 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 15px;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #38bdf8;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--text-main);
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 20px;
        }

        .btn-cancel {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-save {
            padding: 10px 22px;
            background: linear-gradient(to right, #0284c7, #0ea5e9);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }
            .header-title-box {
                flex-direction: column;
                gap: 8px;
            }
            .filter-card {
                padding: 15px;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form .form-group {
                flex: 1 1 100%;
            }
            .btn-submit, .btn-reset {
                width: 100%;
            }
            th, td {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-title-box">
            <img src="images/logo.png" alt="Logo RSUD" class="header-logo" onerror="this.style.display='none'">
            <div>
                <h1>⏰ Update Waktu Operasi</h1>
                <p>RSUD Pringsewu - Manajemen Waktu & Jadwal Operasi Pasien</p>
            </div>
        </div>
        <a href="index.php" class="btn-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Menu
        </a>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <span>🎉 <?php echo $success_message; ?></span>
            <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <span>⚠️ <?php echo $error_message; ?></span>
            <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="filter-card">
        <div class="filter-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            Filter Data Operasi
        </div>
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="tgl_awal">Tanggal Awal</label>
                <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?php echo htmlspecialchars($tgl_awal); ?>">
            </div>
            <div class="form-group">
                <label for="tgl_akhir">Tanggal Akhir</label>
                <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
            </div>
            <div class="form-group">
                <label for="operator1">Dokter Operator</label>
                <select id="operator1" name="operator1" class="form-control">
                    <option value="">-- Semua Dokter --</option>
                    <?php foreach ($dokter_list as $dokter): ?>
                        <option value="<?php echo htmlspecialchars($dokter['kd_dokter']); ?>" <?php echo $operator_filter === $dokter['kd_dokter'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dokter['nm_dokter']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                Tampilkan
            </button>
            <a href="update_waktu_operasi.php" class="btn-reset">Reset</a>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>No. Rawat</th>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Dokter Operator</th>
                        <th>Tanggal Mulai (Laporan)</th>
                        <th>Selesai Operasi</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Format date for visual display
                            $tgl_mulai_formatted = date('d-m-Y H:i:s', strtotime($row['tanggal']));
                            $tgl_selesai_formatted = !empty($row['selesaioperasi']) && $row['selesaioperasi'] !== '0000-00-00 00:00:00' 
                                ? date('d-m-Y H:i:s', strtotime($row['selesaioperasi'])) 
                                : '-';
                            ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted);"><?php echo $no++; ?></td>
                                <td><span class="badge-norawat"><?php echo htmlspecialchars($row['no_rawat']); ?></span></td>
                                <td><span class="badge-norm"><?php echo htmlspecialchars($row['no_rkm_medis']); ?></span></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['nm_dokter']); ?></td>
                                <td><?php echo $tgl_mulai_formatted; ?></td>
                                <td><?php echo $tgl_selesai_formatted; ?></td>
                                <td style="text-align: center;">
                                    <button class="btn-edit" 
                                            data-norawat="<?php echo htmlspecialchars($row['no_rawat']); ?>" 
                                            data-norm="<?php echo htmlspecialchars($row['no_rkm_medis']); ?>" 
                                            data-pasien="<?php echo htmlspecialchars($row['nm_pasien']); ?>" 
                                            data-tanggal="<?php echo htmlspecialchars($row['tanggal']); ?>" 
                                            data-selesai="<?php echo htmlspecialchars($row['selesaioperasi']); ?>"
                                            onclick="openEditModal(this)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px; color: var(--text-muted);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                <br>Tidak ada data waktu operasi ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Popup Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📝 Edit Waktu Operasi</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="post" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="update_waktu">
            <input type="hidden" id="modal_old_tanggal" name="old_tanggal">

            <div class="modal-body">
                <div class="form-group">
                    <label>No. Rawat</label>
                    <input type="text" id="modal_no_rawat" name="no_rawat" class="form-control" readonly style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05); color: var(--text-muted);">
                </div>
                <div class="form-group">
                    <label>Nama Pasien</label>
                    <input type="text" id="modal_nm_pasien" class="form-control" readonly style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05); color: var(--text-muted);">
                </div>
                <div class="form-group">
                    <label for="modal_tanggal">Tanggal & Waktu Mulai (Laporan)</label>
                    <input type="datetime-local" id="modal_tanggal" name="tanggal" class="form-control" step="1" required>
                </div>
                <div class="form-group">
                    <label for="modal_selesai">Tanggal & Waktu Selesai</label>
                    <input type="datetime-local" id="modal_selesai" name="selesaioperasi" class="form-control" step="1" required>
                </div>
                <div id="modal_error_alert" style="display:none; color: var(--danger); font-size: 0.85rem; font-weight: 500; margin-top: 5px;"></div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    const modalNoRawat = document.getElementById('modal_no_rawat');
    const modalNmPasien = document.getElementById('modal_nm_pasien');
    const modalOldTanggal = document.getElementById('modal_old_tanggal');
    const modalTanggal = document.getElementById('modal_tanggal');
    const modalSelesai = document.getElementById('modal_selesai');
    const modalErrorAlert = document.getElementById('modal_error_alert');

    // Mengubah string datetime YYYY-MM-DD HH:MM:SS ke format input datetime-local YYYY-MM-DDTHH:MM:SS
    function formatToDatetimeLocal(datetimeStr) {
        if (!datetimeStr || datetimeStr === '0000-00-00 00:00:00') {
            return '';
        }
        // Ganti spasi dengan T untuk mendukung format detik
        return datetimeStr.replace(' ', 'T');
    }

    function openEditModal(button) {
        const noRawat = button.getAttribute('data-norawat');
        const nmPasien = button.getAttribute('data-pasien');
        const tanggal = button.getAttribute('data-tanggal');
        const selesai = button.getAttribute('data-selesai');

        modalNoRawat.value = noRawat;
        modalNmPasien.value = nmPasien;
        modalOldTanggal.value = tanggal;
        modalTanggal.value = formatToDatetimeLocal(tanggal);
        modalSelesai.value = formatToDatetimeLocal(selesai);
        
        modalErrorAlert.style.display = 'none';
        modalErrorAlert.textContent = '';

        modal.classList.add('open');
    }

    function closeEditModal() {
        modal.classList.remove('open');
    }

    function validateForm() {
        const start = new Date(modalTanggal.value);
        const end = new Date(modalSelesai.value);

        if (end <= start) {
            modalErrorAlert.textContent = '❌ Waktu selesai operasi harus setelah waktu mulai operasi.';
            modalErrorAlert.style.display = 'block';
            return false;
        }
        return true;
    }

    // Menutup modal jika klik di luar area modal content
    window.onclick = function(event) {
        if (event.target === modal) {
            closeEditModal();
        }
    }
</script>
</body>
</html>
<?php
// Tutup koneksi database
if (isset($koneksi)) {
    $koneksi->close();
}
?>
