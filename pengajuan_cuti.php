<?php
include 'auth.php';
include 'koneksi.php';

// Ambil NIK dari Session login
$nik = $_SESSION['username'];

// Ambil Nama Pegawai dari NIK
$query_pegawai_info = "SELECT nama FROM pegawai WHERE nik = ? LIMIT 1";
$stmt_peg_info = $koneksi->prepare($query_pegawai_info);
$stmt_peg_info->bind_param("s", $nik);
$stmt_peg_info->execute();
$res_peg_info = $stmt_peg_info->get_result();
$nama_pegawai = "Pegawai";
if ($row_info = $res_peg_info->fetch_assoc()) {
    $nama_pegawai = $row_info['nama'];
}

$error_msg = '';
$success_msg = '';

// Ambil daftar hari libur nasional dari database
$holidays = [];
$query_holidays = "SELECT tanggal FROM set_hari_libur";
$res_holidays = mysqli_query($koneksi, $query_holidays);
if ($res_holidays) {
    while ($row = mysqli_fetch_assoc($res_holidays)) {
        $holidays[] = $row['tanggal'];
    }
}
$holidays_json = json_encode($holidays);
$holidays_lookup = array_fill_keys($holidays, true);

// Proses Simpan Pengajuan Cuti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $tanggal_awal = $_POST['tanggal_awal'] ?? '';
    $tanggal_akhir = $_POST['tanggal_akhir'] ?? '';
    $urgensi = $_POST['urgensi'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $kepentingan = $_POST['kepentingan'] ?? '';
    $nik_pj = $_POST['nik_pj'] ?? '';
    
    // Validasi input wajib
    if (empty($tanggal_awal) || empty($tanggal_akhir) || empty($urgensi) || empty($nik_pj)) {
        $error_msg = "Semua kolom wajib diisi!";
    } else {
        // Hitung selisih hari secara inklusif dengan mengecualikan Minggu & Libur Nasional
        $start_ts = strtotime($tanggal_awal);
        $end_ts = strtotime($tanggal_akhir);
        
        if ($end_ts < $start_ts) {
            $error_msg = "Tanggal akhir tidak boleh mendahului tanggal awal!";
        } else {
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
            
            // Validasi kuota 12 hari per tahun
            $year = date('Y', $start_ts);
            $query_limit = "SELECT SUM(jumlah) AS total_days FROM pengajuan_cuti WHERE nik = ? AND YEAR(tanggal_awal) = ? AND status != 'Ditolak'";
            $stmt_limit = $koneksi->prepare($query_limit);
            $stmt_limit->bind_param("ss", $nik, $year);
            $stmt_limit->execute();
            $res_limit = $stmt_limit->get_result();
            $row_limit = $res_limit->fetch_assoc();
            $total_days = (int)($row_limit['total_days'] ?? 0);
            
            if ($total_days + $jumlah > 12) {
                $error_msg = "Pengajuan gagal disimpan! Total cuti Anda di tahun $year akan menjadi " . ($total_days + $jumlah) . " hari, melebihi kuota maksimal 12 hari. Sisa kuota Anda: " . (12 - $total_days) . " hari.";
            } else {
                // Generate nomor pengajuan otomatis (PCYYYYMMDDXXX)
                $today = date('Ymd');
                $prefix = 'PC' . $today;
                $query_no = "SELECT no_pengajuan FROM pengajuan_cuti WHERE no_pengajuan LIKE '$prefix%' ORDER BY no_pengajuan DESC LIMIT 1";
                $result_no = mysqli_query($koneksi, $query_no);
                if ($result_no && mysqli_num_rows($result_no) > 0) {
                    $row_no = mysqli_fetch_assoc($result_no);
                    $last_no = $row_no['no_pengajuan'];
                    $last_num = (int)substr($last_no, -3);
                    $next_num = $last_num + 1;
                } else {
                    $next_num = 1;
                }
                $no_pengajuan = $prefix . sprintf('%03d', $next_num);
                $tanggal_pengajuan = date('Y-m-d');
                $status_default = 'Proses Pengajuan';
                
                // Simpan ke database
                $query_insert = "INSERT INTO pengajuan_cuti (no_pengajuan, tanggal, tanggal_awal, tanggal_akhir, nik, urgensi, alamat, jumlah, kepentingan, nik_pj, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $koneksi->prepare($query_insert);
                $stmt_insert->bind_param("sssssssisss", $no_pengajuan, $tanggal_pengajuan, $tanggal_awal, $tanggal_akhir, $nik, $urgensi, $alamat, $jumlah, $kepentingan, $nik_pj, $status_default);
                
                if ($stmt_insert->execute()) {
                    // Cari rantai atasan (approvers) secara rekursif dari atasan_pegawai
                    $approvers = [];
                    $current_employee = $nik;
                    
                    for ($level = 1; $level <= 3; $level++) {
                        $query_atasan = "SELECT nik_atasan FROM atasan_pegawai WHERE nik = ?";
                        $stmt_atasan = $koneksi->prepare($query_atasan);
                        $stmt_atasan->bind_param("s", $current_employee);
                        $stmt_atasan->execute();
                        $res_atasan = $stmt_atasan->get_result();
                        
                        if ($row_atasan = $res_atasan->fetch_assoc()) {
                            $atasan_nik = $row_atasan['nik_atasan'];
                            if (!empty($atasan_nik)) {
                                $approvers[] = [
                                    'level' => $level,
                                    'nik_approver' => $atasan_nik
                                ];
                                $current_employee = $atasan_nik; // Naik ke level berikutnya
                            } else {
                                break;
                            }
                        } else {
                            break;
                        }
                    }
                    
                    // Fallback jika tidak ada atasan terdaftar di atasan_pegawai
                    if (empty($approvers)) {
                        $approvers[] = [
                            'level' => 1,
                            'nik_approver' => $nik_pj
                        ];
                    }
                    
                    // Simpan data persetujuan ke tabel persetujuan_cuti
                    $query_insert_pc = "INSERT INTO persetujuan_cuti (no_pengajuan, level, nik_approver, status) VALUES (?, ?, ?, 'Pending')";
                    $stmt_insert_pc = $koneksi->prepare($query_insert_pc);
                    
                    $pc_ok = true;
                    foreach ($approvers as $app) {
                        $stmt_insert_pc->bind_param("sis", $no_pengajuan, $app['level'], $app['nik_approver']);
                        if (!$stmt_insert_pc->execute()) {
                            $pc_ok = false;
                        }
                    }
                    
                    if ($pc_ok) {
                        $success_msg = "Pengajuan cuti dengan nomor $no_pengajuan berhasil diajukan!";
                    } else {
                        $error_msg = "Pengajuan berhasil diajukan, namun gagal menginisialisasi alur persetujuan: " . $koneksi->error;
                    }
                } else {
                    $error_msg = "Gagal menyimpan pengajuan cuti: " . $koneksi->error;
                }
            }
        }
    }
}

// Proses Verifikasi/Persetujuan Cuti oleh Atasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_approval'])) {
    $persetujuan_id = (int)($_POST['persetujuan_id'] ?? 0);
    $action = $_POST['action'] ?? ''; // 'Disetujui' atau 'Ditolak'
    $catatan = $_POST['catatan'] ?? '';
    
    if (empty($action) || !in_array($action, ['Disetujui', 'Ditolak'])) {
        $error_msg = "Aksi persetujuan tidak valid!";
    } else {
        // Ambil detail persetujuan
        $query_app = "SELECT no_pengajuan, level, nik_approver FROM persetujuan_cuti WHERE id = ?";
        $stmt_app = $koneksi->prepare($query_app);
        $stmt_app->bind_param("i", $persetujuan_id);
        $stmt_app->execute();
        $res_app = $stmt_app->get_result();
        
        if ($row_app = $res_app->fetch_assoc()) {
            $no_pengajuan = $row_app['no_pengajuan'];
            $level = (int)$row_app['level'];
            $nik_approver = $row_app['nik_approver'];
            
            // Pastikan yang menyetujui adalah yang sedang login
            if ($nik_approver === $nik) {
                $now = date('Y-m-d H:i:s');
                $query_update_pc = "UPDATE persetujuan_cuti SET status = ?, tanggal_keputusan = ?, catatan = ? WHERE id = ?";
                $stmt_up_pc = $koneksi->prepare($query_update_pc);
                $stmt_up_pc->bind_param("sssi", $action, $now, $catatan, $persetujuan_id);
                
                if ($stmt_up_pc->execute()) {
                    if ($action === 'Ditolak') {
                        // Jika ditolak di tingkat mana pun, status utama langsung 'Ditolak'
                        $query_update_main = "UPDATE pengajuan_cuti SET status = 'Ditolak' WHERE no_pengajuan = ?";
                        $stmt_up_main = $koneksi->prepare($query_update_main);
                        $stmt_up_main->bind_param("s", $no_pengajuan);
                        $stmt_up_main->execute();
                        $success_msg = "Pengajuan cuti nomor $no_pengajuan berhasil ditolak.";
                    } else {
                        // Jika disetujui, cek apakah ini level terakhir untuk pengajuan ini
                        $query_check_last = "SELECT MAX(level) AS max_level FROM persetujuan_cuti WHERE no_pengajuan = ?";
                        $stmt_last = $koneksi->prepare($query_check_last);
                        $stmt_last->bind_param("s", $no_pengajuan);
                        $stmt_last->execute();
                        $res_last = $stmt_last->get_result()->fetch_assoc();
                        $max_level = (int)$res_last['max_level'];
                        
                        if ($level === $max_level) {
                            // Jika sudah di level akhir, status utama menjadi 'Disetujui'
                            $query_update_main = "UPDATE pengajuan_cuti SET status = 'Disetujui' WHERE no_pengajuan = ?";
                            $stmt_up_main = $koneksi->prepare($query_update_main);
                            $stmt_up_main->bind_param("s", $no_pengajuan);
                            $stmt_up_main->execute();
                            $success_msg = "Pengajuan cuti nomor $no_pengajuan telah disetujui sepenuhnya.";
                        } else {
                            $success_msg = "Persetujuan Level $level untuk pengajuan $no_pengajuan berhasil disimpan. Menunggu persetujuan level selanjutnya.";
                        }
                    }
                } else {
                    $error_msg = "Gagal memproses persetujuan: " . $koneksi->error;
                }
            } else {
                $error_msg = "Anda tidak memiliki hak akses untuk menyetujui pengajuan ini!";
            }
        } else {
            $error_msg = "Data persetujuan tidak ditemukan.";
        }
    }
}

// Ambil data riwayat cuti pegawai (untuk dikalkulasi kuota di client-side JS)
$query_yearly = "SELECT YEAR(tanggal_awal) AS thn, SUM(jumlah) AS total FROM pengajuan_cuti WHERE nik = ? AND status != 'Ditolak' GROUP BY YEAR(tanggal_awal)";
$stmt_yearly = $koneksi->prepare($query_yearly);
$stmt_yearly->bind_param("s", $nik);
$stmt_yearly->execute();
$res_yearly = $stmt_yearly->get_result();
$yearly_data = [];
while ($row = $res_yearly->fetch_assoc()) {
    $yearly_data[(int)$row['thn']] = (int)$row['total'];
}
$yearly_json = json_encode($yearly_data);

// Ambil daftar pegawai lain untuk dropdown Penanggung Jawab
$query_pj = "SELECT nik, nama FROM pegawai WHERE nik != ? ORDER BY nama ASC";
$stmt_pj = $koneksi->prepare($query_pj);
$stmt_pj->bind_param("s", $nik);
$stmt_pj->execute();
$res_pj = $stmt_pj->get_result();
$list_pj = [];
while ($row = $res_pj->fetch_assoc()) {
    $list_pj[] = $row;
}

// Ambil daftar pengajuan cuti yang butuh persetujuan dari user ini (jika user adalah atasan)
$query_approvals = "
    SELECT p.no_pengajuan, p.tanggal, p.tanggal_awal, p.tanggal_akhir, p.jumlah, p.urgensi, p.kepentingan,
           peg.nama AS nama_pegawai, pc.level, pc.id AS persetujuan_id
    FROM persetujuan_cuti pc
    INNER JOIN pengajuan_cuti p ON pc.no_pengajuan = p.no_pengajuan
    INNER JOIN pegawai peg ON p.nik = peg.nik
    WHERE pc.nik_approver = ? AND pc.status = 'Pending' AND p.status = 'Proses Pengajuan'
      AND (
          pc.level = 1 
          OR (
              pc.level > 1 
              AND EXISTS (
                  SELECT 1 FROM persetujuan_cuti pc_prev 
                  WHERE pc_prev.no_pengajuan = pc.no_pengajuan 
                    AND pc_prev.level = pc.level - 1 
                    AND pc_prev.status = 'Disetujui'
              )
          )
      )
    ORDER BY p.tanggal_awal ASC
";
$stmt_approvals = $koneksi->prepare($query_approvals);
$stmt_approvals->bind_param("s", $nik);
$stmt_approvals->execute();
$res_approvals = $stmt_approvals->get_result();
$list_approvals = [];
while ($row = $res_approvals->fetch_assoc()) {
    $list_approvals[] = $row;
}

// Ambil riwayat cuti per pegawai (untuk ditampilkan di bagian bawah)
$query_history = "SELECT p.no_pengajuan, p.tanggal, p.tanggal_awal, p.tanggal_akhir, p.urgensi, p.alamat, p.jumlah, p.kepentingan, p.nik_pj, peg.nama AS nama_pegawai, pj.nama AS nama_pj, p.status 
                  FROM pengajuan_cuti p 
                  INNER JOIN pegawai peg ON p.nik = peg.nik 
                  LEFT JOIN pegawai pj ON p.nik_pj = pj.nik 
                  WHERE p.nik = ? 
                  ORDER BY p.tanggal_awal DESC";
$stmt_hist = $koneksi->prepare($query_history);
$stmt_hist->bind_param("s", $nik);
$stmt_hist->execute();
$res_hist = $stmt_hist->get_result();
$list_history = [];
while ($row = $res_hist->fetch_assoc()) {
    $list_history[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti Online</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #14b8a6;
            --primary-dark: #115e59;
            --primary-bg: #f0fdfa;
            --neutral-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --success: #15803d;
            --success-bg: #dcfce7;
            --warning: #b45309;
            --warning-bg: #fef3c7;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--neutral-bg);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }

        /* Mobile Header */
        header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: #ffffff;
            padding: 20px 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 4px 10px rgba(15, 118, 110, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 20px;
            transition: all 0.2s;
        }

        header .back-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        header h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            flex-grow: 1;
            margin-right: 40px; /* offset back button for centering */
        }

        .main-container {
            padding: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Alerts */
        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .alert-success {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(21, 128, 61, 0.1);
        }

        .alert-error {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(185, 28, 28, 0.1);
        }

        /* Form Card */
        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 16px;
            border-bottom: 1.5px solid var(--border-color);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-family: inherit;
            font-size: 15px;
            color: var(--text-main);
            background-color: var(--neutral-bg);
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--primary-light);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
        }

        .form-input[readonly] {
            background-color: #f1f5f9;
            color: #475569;
            cursor: not-allowed;
            border-color: var(--border-color);
        }

        /* Quota Progress Bar Widget */
        .quota-widget {
            background-color: var(--primary-bg);
            border: 1.5px dashed var(--primary-light);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .quota-header {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }

        .quota-bar-container {
            height: 10px;
            background-color: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .quota-bar-used {
            height: 100%;
            background-color: var(--primary-light);
            border-radius: 6px 0 0 6px;
            transition: width 0.3s ease;
        }

        .quota-bar-proposed {
            height: 100%;
            background-color: #eab308; /* yellow accent */
            position: absolute;
            top: 0;
            transition: all 0.3s ease;
        }

        .quota-warning {
            font-size: 12px;
            color: var(--danger);
            margin-top: 6px;
            font-weight: 500;
            display: none;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(15, 118, 110, 0.2);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(15, 118, 110, 0.25);
        }

        .submit-btn:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        /* History Cards Section */
        .history-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            margin: 24px 0 12px 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .history-card {
            background-color: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            position: relative;
        }

        .history-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .history-no {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }

        .status-badge {
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .status-proses {
            background-color: var(--warning-bg);
            color: var(--warning);
        }

        .status-disetujui {
            background-color: var(--success-bg);
            color: var(--success);
        }

        .status-ditolak {
            background-color: var(--danger-bg);
            color: var(--danger);
        }

        .history-card-body {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
        }

        .history-row {
            display: flex;
            justify-content: space-between;
        }

        .history-label {
            color: var(--text-muted);
        }

        .history-value {
            font-weight: 500;
            color: var(--text-main);
            text-align: right;
        }

        .history-desc {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px solid #f1f5f9;
            font-style: italic;
            color: #475569;
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            padding: 24px;
            background-color: var(--card-bg);
            border-radius: 12px;
            border: 1px dashed var(--border-color);
        }

        /* Responsive spacing adjustment */
        @media (max-width: 480px) {
            header h1 {
                font-size: 16px;
            }
            .card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="back-btn">← Beranda</a>
    <h1>Pengajuan Cuti</h1>
</header>

<div class="main-container">

    <!-- Notification Messages -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success">
            <div>
                <strong>Berhasil!</strong> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error">
            <div>
                <strong>Gagal!</strong> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Persetujuan Cuti Card (Hanya muncul jika ada antrean approval) -->
    <?php if (!empty($list_approvals)): ?>
        <div class="card" style="border: 1.5px solid var(--primary-light); background-color: var(--primary-bg);">
            <div class="card-title" style="color: var(--primary-dark); font-weight: 700; border-bottom-color: var(--primary-light);">
                <span>📝 Persetujuan Cuti Staf</span>
                <span class="status-badge status-proses" style="font-weight: 600;"><?php echo count($list_approvals); ?> Butuh Tindakan</span>
            </div>
            
            <?php foreach ($list_approvals as $app): ?>
                <div style="background: #ffffff; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <strong style="color: var(--text-main); font-size: 15px;"><?php echo htmlspecialchars($app['nama_pegawai']); ?></strong>
                        <span style="font-size: 11px; background: #e2e8f0; padding: 2px 8px; border-radius: 12px; font-weight: 500;">Level <?php echo $app['level']; ?></span>
                    </div>
                    <div style="font-size: 13px; color: var(--text-muted); display: grid; gap: 4px; margin-bottom: 12px;">
                        <div><strong>No Pengajuan:</strong> <?php echo htmlspecialchars($app['no_pengajuan']); ?></div>
                        <div><strong>Tanggal Cuti:</strong> <?php echo date('d-m-Y', strtotime($app['tanggal_awal'])); ?> s/d <?php echo date('d-m-Y', strtotime($app['tanggal_akhir'])); ?> (<?php echo $app['jumlah']; ?> Hari)</div>
                        <div><strong>Urgensi:</strong> <?php echo htmlspecialchars($app['urgensi']); ?></div>
                        <div><strong>Keperluan:</strong> "<?php echo htmlspecialchars($app['kepentingan']); ?>"</div>
                    </div>
                    
                    <form action="pengajuan_cuti.php" method="POST" style="border-top: 1px solid #f1f5f9; padding-top: 12px;">
                        <input type="hidden" name="persetujuan_id" value="<?php echo $app['persetujuan_id']; ?>">
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <input type="text" name="catatan" class="form-input" placeholder="Tulis catatan (opsional)..." style="background:#ffffff; font-size: 13px; padding: 8px 12px;">
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" name="action_approval" value="setujui" onclick="this.form.action.value='Disetujui';" class="submit-btn" style="margin-top: 0; padding: 8px; font-size: 13px; background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                                Setujui
                            </button>
                            <button type="submit" name="action_approval" value="tolak" onclick="this.form.action.value='Ditolak';" class="submit-btn" style="margin-top: 0; padding: 8px; font-size: 13px; background: linear-gradient(135deg, var(--danger), #991b1b); box-shadow: 0 4px 8px rgba(185, 28, 28, 0.2);">
                                Tolak
                            </button>
                        </div>
                        <input type="hidden" name="action" value="">
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="card">
        <div class="card-title">
            <span>Formulir Cuti</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">NIK: <?php echo htmlspecialchars($nik); ?></span>
        </div>

        <form action="pengajuan_cuti.php" method="POST" id="leaveForm" onsubmit="return validateFormOnSubmit()">
            
            <div class="form-group">
                <label>Nama Pegawai</label>
                <input type="text" class="form-input" value="<?php echo htmlspecialchars($nama_pegawai); ?>" readonly>
            </div>

            <div class="form-group" style="display:none;">
                <!-- Disembunyikan agar form ringkas di mobile, namun terkirim jika diperlukan -->
                <label>Nomor Pengajuan</label>
                <input type="text" name="no_pengajuan" class="form-input" value="OTOMATIS" readonly>
            </div>

            <div class="form-group">
                <label for="tanggal_awal">Tanggal Awal Cuti</label>
                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-input" required onchange="onDateChanged()">
            </div>

            <div class="form-group">
                <label for="tanggal_akhir">Tanggal Akhir Cuti</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-input" required onchange="onDateChanged()">
            </div>

            <!-- Widget Kalkulasi & Kuota -->
            <div class="quota-widget" id="quotaWidget" style="display:none;">
                <div class="quota-header">
                    <span id="quotaYearTitle">Kuota Cuti</span>
                    <span id="quotaDetailsLabel">0 / 12 Hari</span>
                </div>
                <div class="quota-bar-container">
                    <div class="quota-bar-used" id="quotaBarUsed" style="width: 0%;"></div>
                    <div class="quota-bar-proposed" id="quotaBarProposed" style="width: 0%; left: 0%;"></div>
                </div>
                <div class="quota-warning" id="quotaWarning">
                    ⚠️ Pengajuan melebihi sisa kuota 12 hari per tahun!
                </div>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah Hari Cuti</label>
                <input type="number" name="jumlah" id="jumlah" class="form-input" value="0" readonly>
            </div>

            <div class="form-group">
                <label for="urgensi">Urgensi Cuti</label>
                <select name="urgensi" id="urgensi" class="form-input" required>
                    <option value="" disabled selected>-- Pilih Urgensi Cuti --</option>
                    <option value="Tahunan">Tahunan</option>
                    <option value="Besar">Besar</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Bersalin">Bersalin</option>
                    <option value="Alasan Penting">Alasan Penting</option>
                    <option value="Keterangan Lainnya">Keterangan Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat Selama Cuti</label>
                <input type="text" name="alamat" id="alamat" class="form-input" placeholder="Masukkan alamat lengkap..." max="100" required>
            </div>

            <div class="form-group">
                <label for="kepentingan">Keperluan/Kepentingan</label>
                <input type="text" name="kepentingan" id="kepentingan" class="form-input" placeholder="Tulis alasan pengajuan cuti..." max="70" required>
            </div>

            <div class="form-group">
                <label for="nik_pj">Penanggung Jawab (PJ)</label>
                <select name="nik_pj" id="nik_pj" class="form-input" required>
                    <option value="" disabled selected>-- Pilih Penanggung Jawab --</option>
                    <?php foreach ($list_pj as $pj): ?>
                        <option value="<?php echo htmlspecialchars($pj['nik']); ?>">
                            <?php echo htmlspecialchars($pj['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="simpan" id="submitBtn" class="submit-btn">Kirim Pengajuan Cuti</button>
        </form>
    </div>

    <!-- History Header -->
    <div class="history-title">
        <span>Riwayat Pengajuan Cuti</span>
        <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">Total: <?php echo count($list_history); ?> Pengajuan</span>
    </div>

    <!-- History Cards -->
    <?php if (empty($list_history)): ?>
        <div class="no-data">
            Belum ada riwayat pengajuan cuti.
        </div>
    <?php else: ?>
        <?php foreach ($list_history as $hist): 
            $status_class = '';
            $status_label = $hist['status'];
            if ($status_label === 'Proses Pengajuan') {
                $status_class = 'status-proses';
            } elseif ($status_label === 'Disetujui') {
                $status_class = 'status-disetujui';
            } elseif ($status_label === 'Ditolak') {
                $status_class = 'status-ditolak';
            }
        ?>
            <div class="history-card">
                <div class="history-card-header">
                    <span class="history-no"><?php echo htmlspecialchars($hist['no_pengajuan']); ?></span>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span>
                </div>
                <div class="history-card-body">
                    <div class="history-row">
                        <span class="history-label">Tanggal Pengajuan</span>
                        <span class="history-value"><?php echo date('d-m-Y', strtotime($hist['tanggal'])); ?></span>
                    </div>
                    <div class="history-row">
                        <span class="history-label">Tanggal Cuti</span>
                        <span class="history-value">
                            <?php echo date('d-m-Y', strtotime($hist['tanggal_awal'])); ?> s/d <?php echo date('d-m-Y', strtotime($hist['tanggal_akhir'])); ?>
                        </span>
                    </div>
                    <div class="history-row">
                        <span class="history-label">Durasi Cuti</span>
                        <span class="history-value"><?php echo htmlspecialchars($hist['jumlah']); ?> Hari</span>
                    </div>
                    <div class="history-row">
                        <span class="history-label">Urgensi</span>
                        <span class="history-value"><?php echo htmlspecialchars($hist['urgensi']); ?></span>
                    </div>
                    <div class="history-row">
                        <span class="history-label">Penanggung Jawab</span>
                        <span class="history-value"><?php echo htmlspecialchars($hist['nama_pj'] ?? $hist['nik_pj']); ?></span>
                    </div>
                    <div class="history-desc">
                        <strong>Keperluan:</strong> "<?php echo htmlspecialchars($hist['kepentingan']); ?>"
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- Client-side script logic -->
<script>
    // Memasukkan data cuti yang sudah diambil per tahun dari PHP
    const historyCutiByYear = <?php echo $yearly_json; ?>;
    // Memasukkan data hari libur nasional dari PHP
    const listHariLibur = <?php echo $holidays_json; ?>;

    function onDateChanged() {
        const tglAwalVal = document.getElementById('tanggal_awal').value;
        const tglAkhirVal = document.getElementById('tanggal_akhir').value;
        const jumlahInput = document.getElementById('jumlah');
        const submitBtn = document.getElementById('submitBtn');
        
        const quotaWidget = document.getElementById('quotaWidget');
        const quotaYearTitle = document.getElementById('quotaYearTitle');
        const quotaDetailsLabel = document.getElementById('quotaDetailsLabel');
        const quotaBarUsed = document.getElementById('quotaBarUsed');
        const quotaBarProposed = document.getElementById('quotaBarProposed');
        const quotaWarning = document.getElementById('quotaWarning');

        if (!tglAwalVal || !tglAkhirVal) {
            jumlahInput.value = 0;
            quotaWidget.style.display = 'none';
            submitBtn.disabled = false;
            return;
        }

        const start = new Date(tglAwalVal);
        const end = new Date(tglAkhirVal);
        
        if (end < start) {
            jumlahInput.value = 0;
            quotaWidget.style.display = 'none';
            submitBtn.disabled = true;
            alert("Tanggal akhir tidak boleh kurang dari tanggal awal!");
            return;
        }

        // hitung selisih hari inklusif dengan mengecualikan Minggu & Libur Nasional
        let diffDays = 0;
        let curDate = new Date(start);
        curDate.setHours(0, 0, 0, 0);
        const normalizedEnd = new Date(end);
        normalizedEnd.setHours(0, 0, 0, 0);

        while (curDate <= normalizedEnd) {
            const dayOfWeek = curDate.getDay(); // 0 = Minggu
            
            // Format to YYYY-MM-DD
            const yyyy = curDate.getFullYear();
            const mm = String(curDate.getMonth() + 1).padStart(2, '0');
            const dd = String(curDate.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;
            
            if (dayOfWeek !== 0 && !listHariLibur.includes(dateStr)) {
                diffDays++;
            }
            curDate.setDate(curDate.getDate() + 1);
        }

        jumlahInput.value = diffDays;

        // Ambil tahun dari tanggal_awal
        const year = start.getFullYear();
        
        // Ambil data historis dari tahun tsb
        const usedDays = historyCutiByYear[year] || 0;
        const maxQuota = 12;
        const totalProjected = usedDays + diffDays;

        // update tampilan widget kuota
        quotaWidget.style.display = 'block';
        quotaYearTitle.innerText = `Kuota Cuti Tahun ${year}`;
        quotaDetailsLabel.innerText = `${usedDays} / 12 Hari`;

        // Kalkulasi persentase bar
        const usedPercent = Math.min((usedDays / maxQuota) * 100, 100);
        const proposedPercent = Math.min((diffDays / maxQuota) * 100, 100 - usedPercent);
        
        quotaBarUsed.style.width = `${usedPercent}%`;
        
        quotaBarProposed.style.left = `${usedPercent}%`;
        quotaBarProposed.style.width = `${proposedPercent}%`;

        // Cek batasan kuota
        if (totalProjected > maxQuota) {
            quotaWarning.innerText = `⚠️ Pengajuan (${diffDays} hari) melebihi kuota cuti tahun ${year}! Terpakai: ${usedDays} hari, Maks: 12 hari.`;
            quotaWarning.style.display = 'block';
            quotaBarProposed.style.backgroundColor = 'var(--danger)'; // warn merah
            submitBtn.disabled = true;
        } else {
            quotaWarning.style.display = 'none';
            quotaBarProposed.style.backgroundColor = '#eab308'; // kembali kuning
            submitBtn.disabled = false;
        }
    }

    function validateFormOnSubmit() {
        const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
        const tglAwalVal = document.getElementById('tanggal_awal').value;
        
        if (jumlah <= 0) {
            alert("Jumlah hari cuti harus lebih dari 0!");
            return false;
        }

        const start = new Date(tglAwalVal);
        const year = start.getFullYear();
        const usedDays = historyCutiByYear[year] || 0;
        
        if (usedDays + jumlah > 12) {
            alert(`Pengajuan gagal disimpan! Anda sudah mengambil ${usedDays} hari cuti di tahun ${year}. Tambahan ${jumlah} hari melebihi batas 12 hari.`);
            return false;
        }

        return true;
    }
</script>
</body>
</html>
