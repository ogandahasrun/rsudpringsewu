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
        // Hitung selisih hari (End Date - Start Date)
        $start_ts = strtotime($tanggal_awal);
        $end_ts = strtotime($tanggal_akhir);
        
        if ($end_ts < $start_ts) {
            $error_msg = "Tanggal akhir tidak boleh mendahului tanggal awal!";
        } else {
            $jumlah = (int)(($end_ts - $start_ts) / 86400); // Selisih waktu dalam hari
            
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
                    $success_msg = "Pengajuan cuti dengan nomor $no_pengajuan berhasil diajukan!";
                } else {
                    $error_msg = "Gagal menyimpan pengajuan cuti: " . $koneksi->error;
                }
            }
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
    <!-- jQuery & Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

        /* Select2 Customization */
        .select2-container .select2-selection--single {
            height: 44px;
            background-color: var(--neutral-bg);
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            font-family: inherit;
            font-size: 15px;
            color: var(--text-main);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-main);
            padding-left: 14px;
            padding-right: 14px;
            width: 100%;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            right: 10px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary-light);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.12);
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
    $(document).ready(function() {
        $('#nik_pj').select2({
            placeholder: "-- Pilih Penanggung Jawab --",
            width: '100%'
        });
    });

    // Memasukkan data cuti yang sudah diambil per tahun dari PHP
    const historyCutiByYear = <?php echo $yearly_json; ?>;

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
        
        // hitung selisih hari (End - Start)
        const diffTime = end - start;
        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) {
            jumlahInput.value = 0;
            quotaWidget.style.display = 'none';
            submitBtn.disabled = true;
            alert("Tanggal akhir tidak boleh kurang dari tanggal awal!");
            return;
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
