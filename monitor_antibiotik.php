<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Helper function to generate no_permintaan with format PPRA + YYYYMMDD + 3-digit sequence (e.g. PPRA20260101001)
function generateNoPermintaan($koneksi, $tanggalStr) {
    $ts = strtotime($tanggalStr);
    if ($ts === false) {
        $ts = time();
    }
    $datePart = date('Ymd', $ts);
    $prefix = 'PPRA' . $datePart;

    $sql = "SELECT MAX(CAST(SUBSTRING(no_permintaan, 13) AS UNSIGNED)) AS max_urut
            FROM konsultasi_perawat
            WHERE no_permintaan LIKE ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    $likeParam = $prefix . '%';
    mysqli_stmt_bind_param($stmt, 's', $likeParam);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    $nextUrut = 1;
    if ($row && $row['max_urut'] !== null) {
        $nextUrut = intval($row['max_urut']) + 1;
    }

    return $prefix . sprintf('%03d', $nextUrut);
}

// AJAX endpoint: Generate nomor permintaan berdasarkan tanggal
if (isset($_GET['action']) && $_GET['action'] === 'get_no_permintaan') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d H:i:s');
    $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
    echo json_encode([
        'success' => true,
        'no_permintaan' => $no_permintaan
    ]);
    exit;
}

// AJAX endpoint: Simpan data konsultasi_perawat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_konsultasi') {
    header('Content-Type: application/json');

    $no_permintaan       = trim($_POST['no_permintaan'] ?? '');
    $no_rawat            = trim($_POST['no_rawat'] ?? '');
    $tanggal_input       = trim($_POST['tanggal'] ?? '');
    $nip                 = trim($_POST['nip'] ?? '');
    $kd_dokter_dikonsuli = trim($_POST['kd_dokter_dikonsuli'] ?? '');
    $situation           = trim($_POST['situation'] ?? '');
    $background          = trim($_POST['background'] ?? '');
    $assessment          = trim($_POST['assessment'] ?? '');
    $recomendation       = trim($_POST['recomendation'] ?? '');

    if ($no_rawat === '' || $nip === '' || $kd_dokter_dikonsuli === '') {
        echo json_encode([
            'success' => false,
            'message' => 'No Rawat, NIP Petugas, dan Dokter Dikonsuli wajib diisi.'
        ]);
        exit;
    }

    // Normalize datetime format (YYYY-MM-DD HH:MM:SS)
    $ts = strtotime($tanggal_input);
    $tanggal = ($ts !== false) ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');

    // Generate or ensure valid no_permintaan
    if ($no_permintaan === '') {
        $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
    } else {
        // Check if no_permintaan already exists; if so, regenerate for that date
        $chk = mysqli_prepare($koneksi, "SELECT no_permintaan FROM konsultasi_perawat WHERE no_permintaan = ?");
        mysqli_stmt_bind_param($chk, 's', $no_permintaan);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);
        if ($chkRes && mysqli_num_rows($chkRes) > 0) {
            $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
        }
    }

    $insSql = "INSERT INTO konsultasi_perawat (
                    no_permintaan,
                    no_rawat,
                    tanggal,
                    nip,
                    kd_dokter_dikonsuli,
                    situation,
                    background,
                    assessment,
                    recomendation
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtIns = mysqli_prepare($koneksi, $insSql);
    if (!$stmtIns) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmtIns,
        'sssssssss',
        $no_permintaan,
        $no_rawat,
        $tanggal,
        $nip,
        $kd_dokter_dikonsuli,
        $situation,
        $background,
        $assessment,
        $recomendation
    );

    if (mysqli_stmt_execute($stmtIns)) {
        echo json_encode([
            'success' => true,
            'message' => 'Data konsultasi berhasil dikirim ke dokter dengan No. Permintaan: ' . $no_permintaan,
            'no_permintaan' => $no_permintaan
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data: ' . mysqli_stmt_error($stmtIns)
        ]);
    }
    exit;
}

// Get date range from GET parameters, default to last 30 days
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end   = isset($_GET['end'])   ? $_GET['end']   : date('Y-m-d');

// Prepare statement for antibiotic monitoring
$query = "SELECT d.no_rawat,
                 r.no_rkm_medis,
                 p.nm_pasien,
                 COALESCE(
                     (SELECT dp.kd_dokter FROM dpjp_ranap dp WHERE dp.no_rawat = d.no_rawat LIMIT 1),
                     r.kd_dokter
                 ) AS kd_dokter_default,
                 MIN(d.tgl_perawatan) AS start_date,
                 MAX(d.tgl_perawatan) AS end_date,
                 COUNT(DISTINCT d.tgl_perawatan) AS days,
                 GROUP_CONCAT(DISTINCT d.kode_brng SEPARATOR ', ') AS kode_barang,
                 GROUP_CONCAT(DISTINCT b.nama_brng SEPARATOR ', ') AS nama_barang,
                 (SELECT kp.no_permintaan
                  FROM konsultasi_perawat kp
                  WHERE kp.no_rawat = d.no_rawat AND kp.no_permintaan LIKE 'PPRA%'
                  ORDER BY kp.tanggal DESC LIMIT 1) AS last_ppra_no
          FROM detail_pemberian_obat d
          INNER JOIN databarang b ON d.kode_brng = b.kode_brng
          INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
          INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
          WHERE b.kode_golongan = 'AB'
            AND d.tgl_perawatan BETWEEN ? AND ?
          GROUP BY d.no_rawat, r.no_rkm_medis, p.nm_pasien, r.kd_dokter
          HAVING days >= 3
          ORDER BY days DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch active petugas for dropdown
$petugasList = [];
$resPetugas = mysqli_query($koneksi, "SELECT nip, nama FROM petugas WHERE status = '1' ORDER BY nama ASC");
if ($resPetugas) {
    while ($p = mysqli_fetch_assoc($resPetugas)) {
        $petugasList[] = $p;
    }
}

// Fetch active dokter for dropdown
$dokterList = [];
$resDokter = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter WHERE status = '1' ORDER BY nm_dokter ASC");
if ($resDokter) {
    while ($dk = mysqli_fetch_assoc($resDokter)) {
        $dokterList[] = $dk;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitor Penggunaan Antibiotik ≥ 3 Hari</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .filter-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #fafafa; }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background-color: #0066cc; color: #fff; }
        .btn-primary:hover { background-color: #0052a3; }
        .btn-success { background-color: #198754; color: #fff; }
        .btn-success:hover { background-color: #146c43; }
        .btn-secondary { background-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #565e64; }
        .badge-sent {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 6px;
            font-size: 11px;
            background-color: #d1e7dd;
            color: #0f5132;
            border-radius: 3px;
        }

        /* Modal Popup Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-overlay.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .modal-card {
            background: #fff;
            border-radius: 6px;
            max-width: 650px;
            width: 100%;
            margin: 20px auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .modal-header {
            background: #0066cc;
            color: #fff;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 16px; }
        .modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }
        .modal-body { padding: 18px; }
        .patient-summary {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 13px;
            line-height: 1.5;
        }
        .form-group { margin-bottom: 12px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 7px 9px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            color: #495057;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .modal-footer {
            padding: 12px 18px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .char-hint {
            font-size: 11px;
            color: #666;
            float: right;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <h2>Monitor Penggunaan Antibiotik (≥ 3 Hari per No Rawat)</h2>

    <div class="toolbar">
        <form method="GET" class="filter-form">
            <label>Periode: </label>
            <input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>" required>
            <span>s/d</span>
            <input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>" required>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>

        <div style="display:flex; gap:8px; align-items:center;">
            <a href="laporan_ppra_penggunaan_antibiotik.php?start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>" class="btn btn-primary">
                Rekapitulasi &amp; Grafik Laporan PPRA
            </a>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <button type="button" id="copy-table-btn" class="btn btn-success">Copy Tabel ke Clipboard</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table id="antibiotik-table">
        <thead>
            <tr>
                <th>No Rawat</th>
                <th>No RKM Medis</th>
                <th>Nama Pasien</th>
                <th>Periode (Mulai - Selesai)</th>
                <th>Jumlah Hari</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th class="no-copy">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                <td><?php echo htmlspecialchars($row['start_date'] . ' - ' . $row['end_date']); ?></td>
                <td><?php echo htmlspecialchars($row['days']); ?></td>
                <td><?php echo htmlspecialchars($row['kode_barang']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td class="no-copy">
                    <button type="button"
                            class="btn btn-primary kirim-dokter-btn"
                            data-row="<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
                        Kirim ke Dokter
                    </button>
                    <div class="sent-status-container">
                        <?php if (!empty($row['last_ppra_no'])): ?>
                            <span class="badge-sent">Terkirim: <?php echo htmlspecialchars($row['last_ppra_no']); ?></span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Tidak ada data yang memenuhi kriteria.</p>
    <?php endif; ?>

    <!-- Modal Popup Kirim Konsultasi Perawat (PPRA) ke Dokter -->
    <div id="konsultasi-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Kirim Konsultasi PPRA ke Dokter</h3>
                <button type="button" class="modal-close" id="close-modal-btn" title="Tutup">&times;</button>
            </div>
            <form id="form-konsultasi">
                <div class="modal-body">
                    <div id="patient-summary" class="patient-summary"></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_permintaan">No. Permintaan (Format: PPRAYYYYMMDD001)</label>
                            <input type="text" id="no_permintaan" name="no_permintaan" maxlength="20" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="no_rawat">No. Rawat</label>
                            <input type="text" id="no_rawat" name="no_rawat" maxlength="17" readonly required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal &amp; Jam Laporan</label>
                        <input type="datetime-local" id="tanggal" name="tanggal" step="1" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nip">Petugas / Perawat (NIP)</label>
                            <select id="nip" name="nip" required>
                                <option value="">-- Pilih Petugas --</option>
                                <?php foreach ($petugasList as $ptg): ?>
                                    <option value="<?php echo htmlspecialchars($ptg['nip']); ?>">
                                        <?php echo htmlspecialchars($ptg['nip'] . ' - ' . $ptg['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kd_dokter_dikonsuli">Dokter Dikonsuli</label>
                            <select id="kd_dokter_dikonsuli" name="kd_dokter_dikonsuli" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php foreach ($dokterList as $dk): ?>
                                    <option value="<?php echo htmlspecialchars($dk['kd_dokter']); ?>">
                                        <?php echo htmlspecialchars($dk['kd_dokter'] . ' - ' . $dk['nm_dokter']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="situation">Situation <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="situation" name="situation" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="background">Background <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="background" name="background" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="assessment">Assessment <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="assessment" name="assessment" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="recomendation">Recommendation <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="recomendation" name="recomendation" rows="2" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-modal-btn">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submit-konsultasi-btn">Kirim ke Dokter</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Helper: Copy text to clipboard with fallback for non-HTTPS
        function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                return new Promise(function(resolve, reject) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-9999px';
                    textArea.style.top = '0';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        const successful = document.execCommand('copy');
                        document.body.removeChild(textArea);
                        if (successful) {
                            resolve();
                        } else {
                            reject(new Error('execCommand copy failed'));
                        }
                    } catch (err) {
                        document.body.removeChild(textArea);
                        reject(err);
                    }
                });
            }
        }

        // 1. Copy entire displayed table to clipboard (excluding the 'Aksi' column)
        const copyTableBtn = document.getElementById('copy-table-btn');
        if (copyTableBtn) {
            copyTableBtn.addEventListener('click', function() {
                const table = document.getElementById('antibiotik-table');
                if (!table) return;

                const lines = [];
                const rows = table.querySelectorAll('tr');
                rows.forEach(function(row) {
                    const cells = row.querySelectorAll('th:not(.no-copy), td:not(.no-copy)');
                    const rowValues = Array.from(cells).map(function(cell) {
                        return cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/\t/g, ' ').trim();
                    });
                    if (rowValues.length > 0) {
                        lines.push(rowValues.join('\t'));
                    }
                });

                const tsvContent = lines.join('\n');
                copyTextToClipboard(tsvContent).then(function() {
                    alert('Tabel berhasil disalin ke clipboard!');
                }).catch(function(err) {
                    console.error('Copy tabel gagal:', err);
                    alert('Gagal menyalin tabel ke clipboard.');
                });
            });
        }

        // 2. Modal Popup logic for sending data to konsultasi_perawat
        const modal = document.getElementById('konsultasi-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const formKonsultasi = document.getElementById('form-konsultasi');
        const inputNoPermintaan = document.getElementById('no_permintaan');
        const inputNoRawat = document.getElementById('no_rawat');
        const inputTanggal = document.getElementById('tanggal');
        const selectNip = document.getElementById('nip');
        const selectDokter = document.getElementById('kd_dokter_dikonsuli');
        const inputSituation = document.getElementById('situation');
        const inputBackground = document.getElementById('background');
        const inputAssessment = document.getElementById('assessment');
        const inputRecomendation = document.getElementById('recomendation');
        const patientSummary = document.getElementById('patient-summary');
        let activeRowButton = null;

        function getLocalDateTimeString() {
            const now = new Date();
            const pad = function(n) { return String(n).padStart(2, '0'); };
            return now.getFullYear() + '-' +
                   pad(now.getMonth() + 1) + '-' +
                   pad(now.getDate()) + 'T' +
                   pad(now.getHours()) + ':' +
                   pad(now.getMinutes()) + ':' +
                   pad(now.getSeconds());
        }

        function updateNoPermintaan(tanggalStr) {
            inputNoPermintaan.value = 'Memuat...';
            fetch('monitor_antibiotik.php?action=get_no_permintaan&tanggal=' + encodeURIComponent(tanggalStr) + '&_t=' + Date.now(), {
                cache: 'no-store'
            })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.no_permintaan) {
                        inputNoPermintaan.value = data.no_permintaan;
                    }
                })
                .catch(function(err) {
                    console.error('Gagal mengambil no_permintaan:', err);
                });
        }

        // Refresh no_permintaan if date changes
        inputTanggal.addEventListener('change', function() {
            if (this.value) {
                updateNoPermintaan(this.value.replace('T', ' '));
            }
        });

        function openModalForRow(rowData, btnElement) {
            activeRowButton = btnElement;
            const nowLocal = getLocalDateTimeString();

            inputNoRawat.value = rowData.no_rawat || '';
            inputTanggal.value = nowLocal;
            updateNoPermintaan(nowLocal.replace('T', ' '));

            // Pre-select patient's default doctor if available
            if (rowData.kd_dokter_default) {
                selectDokter.value = rowData.kd_dokter_default;
            } else {
                selectDokter.value = '';
            }

            // Pre-fill SBAR with antibiotic monitoring details so user can review/edit
            inputSituation.value = 'Pasien ' + (rowData.nm_pasien || '') + ' (No. RM: ' + (rowData.no_rkm_medis || '') + ') mendapatkan terapi antibiotik ' + (rowData.nama_barang || '') + ' selama ' + (rowData.days || '') + ' hari.';
            inputBackground.value = 'Periode pemberian antibiotik: ' + (rowData.start_date || '') + ' s/d ' + (rowData.end_date || '') + ' (Kode: ' + (rowData.kode_barang || '') + ').';
            inputAssessment.value = 'Monitoring PPRA penggunaan antibiotik >= 3 hari (' + (rowData.days || '') + ' hari).';
            inputRecomendation.value = 'Mohon evaluasi/konfirmasi kelanjutan terapi antibiotik (lanjut / de-eskalasi / stop).';

            patientSummary.innerHTML =
                '<strong>Pasien:</strong> ' + (rowData.nm_pasien || '-') + ' (' + (rowData.no_rkm_medis || '-') + ')<br>' +
                '<strong>Antibiotik:</strong> ' + (rowData.nama_barang || '-') + ' (' + (rowData.days || '0') + ' hari: ' + (rowData.start_date || '-') + ' s/d ' + (rowData.end_date || '-') + ')';

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            activeRowButton = null;
        }

        document.querySelectorAll('.kirim-dokter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const rowData = JSON.parse(this.getAttribute('data-row'));
                openModalForRow(rowData, this);
            });
        });

        closeModalBtn.addEventListener('click', closeModal);
        cancelModalBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Submit form via AJAX
        formKonsultasi.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-konsultasi-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim...';

            const formData = new FormData(formKonsultasi);
            formData.append('action', 'simpan_konsultasi');
            formData.set('tanggal', inputTanggal.value.replace('T', ' '));

            fetch('monitor_antibiotik.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim ke Dokter';

                if (data.success) {
                    alert(data.message);
                    if (activeRowButton) {
                        const statusContainer = activeRowButton.parentElement.querySelector('.sent-status-container');
                        if (statusContainer) {
                            statusContainer.innerHTML = '<span class="badge-sent">Terkirim: ' + data.no_permintaan + '</span>';
                        }
                    }
                    closeModal();
                } else {
                    alert(data.message || 'Gagal mengirim data ke dokter.');
                }
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim ke Dokter';
                console.error('Error simpan konsultasi:', err);
                alert('Terjadi kesalahan saat menyimpan data.');
            });
        });
    </script>
</body>
</html>
