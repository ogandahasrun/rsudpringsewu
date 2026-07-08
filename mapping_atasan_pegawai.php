<?php
include 'auth.php';
include 'koneksi.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nik = $_POST['nik'] ?? ($_POST['nik_hidden'] ?? '');
    $nik_atasan = $_POST['nik_atasan'] ?? '';

    // Sanitization
    $nik = trim(mysqli_real_escape_string($koneksi, $nik));
    $nik_atasan = trim(mysqli_real_escape_string($koneksi, $nik_atasan));

    if ($action === 'tambah') {
        if (empty($nik) || empty($nik_atasan)) {
            $error_msg = "Pegawai dan Atasan harus dipilih!";
        } elseif ($nik === $nik_atasan) {
            $error_msg = "Pegawai tidak boleh menjadi atasan dirinya sendiri!";
        } else {
            // Cek apakah pegawai sudah memiliki atasan
            $check_query = "SELECT nik FROM atasan_pegawai WHERE nik = ?";
            $stmt = $koneksi->prepare($check_query);
            $stmt->bind_param("s", $nik);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $error_msg = "Pegawai dengan NIK $nik sudah memiliki atasan terdaftar. Silakan edit data tersebut.";
            } else {
                $insert_query = "INSERT INTO atasan_pegawai (nik, nik_atasan) VALUES (?, ?)";
                $stmt_insert = $koneksi->prepare($insert_query);
                $stmt_insert->bind_param("ss", $nik, $nik_atasan);
                if ($stmt_insert->execute()) {
                    $success_msg = "Berhasil menambahkan mapping atasan pegawai.";
                } else {
                    $error_msg = "Gagal menyimpan data: " . $koneksi->error;
                }
            }
        }
    } elseif ($action === 'edit') {
        if (empty($nik) || empty($nik_atasan)) {
            $error_msg = "Pegawai dan Atasan harus dipilih!";
        } elseif ($nik === $nik_atasan) {
            $error_msg = "Pegawai tidak boleh menjadi atasan dirinya sendiri!";
        } else {
            $update_query = "UPDATE atasan_pegawai SET nik_atasan = ? WHERE nik = ?";
            $stmt_update = $koneksi->prepare($update_query);
            $stmt_update->bind_param("ss", $nik_atasan, $nik);
            if ($stmt_update->execute()) {
                $success_msg = "Berhasil memperbarui mapping atasan pegawai.";
            } else {
                $error_msg = "Gagal memperbarui data: " . $koneksi->error;
            }
        }
    } elseif ($action === 'hapus') {
        if (empty($nik)) {
            $error_msg = "NIK pegawai tidak valid!";
        } else {
            $delete_query = "DELETE FROM atasan_pegawai WHERE nik = ?";
            $stmt_delete = $koneksi->prepare($delete_query);
            $stmt_delete->bind_param("s", $nik);
            if ($stmt_delete->execute()) {
                $success_msg = "Berhasil menghapus mapping atasan pegawai.";
            } else {
                $error_msg = "Gagal menghapus data: " . $koneksi->error;
            }
        }
    }
}

// Ambil daftar semua pegawai untuk dropdown list
$query_pegawai = "SELECT nik, nama FROM pegawai ORDER BY nama ASC";
$result_pegawai = mysqli_query($koneksi, $query_pegawai);
$list_pegawai = [];
if ($result_pegawai) {
    while ($row = mysqli_fetch_assoc($result_pegawai)) {
        $list_pegawai[] = $row;
    }
}

// Ambil mapping yang ada (sesuai query user yang telah diperbaiki)
$query_mapping = "
    SELECT 
        ap.nik, 
        p1.nama AS nama_pegawai, 
        ap.nik_atasan, 
        p2.nama AS nama_atasan
    FROM 
        atasan_pegawai ap
    INNER JOIN pegawai p1 ON ap.nik = p1.nik
    INNER JOIN pegawai p2 ON ap.nik_atasan = p2.nik
    ORDER BY p1.nama ASC
";
$result_mapping = mysqli_query($koneksi, $query_mapping);
$list_mapping = [];
if ($result_mapping) {
    while ($row = mysqli_fetch_assoc($result_mapping)) {
        $list_mapping[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping Atasan Pegawai - RSUD Pringsewu</title>
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
        }

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
            margin-right: 40px; /* Offset back button for centering */
        }

        .main-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Alerts */
        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
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

        /* Grid Layout */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr 2fr;
                align-items: start;
            }
        }

        /* Cards */
        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 20px;
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

        .badge {
            font-size: 12px;
            background-color: var(--primary-bg);
            color: var(--primary-dark);
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
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

        /* Select2 overrides */
        .select2-container .select2-selection--single {
            height: 44px;
            background-color: var(--neutral-bg);
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            font-family: inherit;
            font-size: 15px;
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

        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #f1f5f9;
            cursor: not-allowed;
            border-color: var(--border-color);
        }

        /* Button Groups */
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .submit-btn {
            flex-grow: 1;
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
            text-align: center;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(15, 118, 110, 0.25);
        }

        .cancel-btn {
            padding: 12px 18px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-muted);
            background-color: #e2e8f0;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cancel-btn:hover {
            background-color: #cbd5e1;
            color: var(--text-main);
        }

        /* Table styles */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1.5px solid var(--border-color);
            font-size: 14px;
        }

        th {
            background-color: var(--neutral-bg);
            color: var(--primary-dark);
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .text-muted {
            font-size: 12px;
            color: var(--text-muted);
            display: inline-block;
            margin-top: 2px;
        }

        /* Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            background: none;
        }

        .btn-edit {
            background-color: var(--warning-bg);
            color: var(--warning);
            border-color: rgba(180, 83, 9, 0.1);
        }

        .btn-edit:hover {
            background-color: #fef08a;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: var(--danger-bg);
            color: var(--danger);
            border-color: rgba(185, 28, 28, 0.1);
        }

        .btn-delete:hover {
            background-color: #fecaca;
            transform: translateY(-1px);
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
            padding: 30px;
        }

        /* Info boxes */
        .info-box {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="back-btn">← Beranda</a>
    <h1>Mapping Atasan Pegawai</h1>
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

    <div class="grid-layout">
        <!-- Column 1: Form Card -->
        <div>
            <div class="card">
                <div class="card-title" id="formTitle">Tambah Mapping Atasan</div>
                
                <div class="info-box" id="infoBox">
                    Setiap pegawai hanya dapat memiliki satu atasan langsung dalam sistem persetujuan cuti.
                </div>

                <form action="mapping_atasan_pegawai.php" method="POST" id="mappingForm" onsubmit="return validateFormSubmit()">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="nik_hidden" id="nik_hidden" value="" disabled>
                    
                    <div class="form-group">
                        <label for="nik">Pegawai</label>
                        <select name="nik" id="nik" class="form-input select2" required>
                            <option value="" disabled selected>-- Pilih Pegawai --</option>
                            <?php foreach ($list_pegawai as $peg): ?>
                                <option value="<?php echo htmlspecialchars($peg['nik']); ?>">
                                    <?php echo htmlspecialchars($peg['nik'] . ' - ' . $peg['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nik_atasan">Atasan Langsung</label>
                        <select name="nik_atasan" id="nik_atasan" class="form-input select2" required>
                            <option value="" disabled selected>-- Pilih Atasan --</option>
                            <?php foreach ($list_pegawai as $peg): ?>
                                <option value="<?php echo htmlspecialchars($peg['nik']); ?>">
                                    <?php echo htmlspecialchars($peg['nik'] . ' - ' . $peg['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" id="submitBtn" class="submit-btn">Simpan Mapping</button>
                        <button type="button" id="cancelBtn" class="cancel-btn" style="display:none;" onclick="resetForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Column 2: Mappings Table -->
        <div>
            <div class="card">
                <div class="card-title">
                    <span>Daftar Atasan Pegawai</span>
                    <span class="badge" id="countBadge"><?php echo count($list_mapping); ?> Data</span>
                </div>

                <div class="form-group">
                    <input type="text" id="searchBar" class="form-input" placeholder="🔍 Cari nama/NIK pegawai atau atasan..." onkeyup="filterTable()">
                </div>

                <div class="table-responsive">
                    <table id="mappingTable">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align:center;">No</th>
                                <th>Pegawai</th>
                                <th>Atasan Langsung</th>
                                <th style="width: 150px; text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_mapping)): ?>
                                <tr class="no-data-row">
                                    <td colspan="4" class="no-data">Belum ada data mapping atasan pegawai.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_mapping as $map): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo $no++; ?></td>
                                        <td>
                                            <strong class="emp-name"><?php echo htmlspecialchars($map['nama_pegawai']); ?></strong><br>
                                            <span class="emp-nik text-muted">NIK: <?php echo htmlspecialchars($map['nik']); ?></span>
                                        </td>
                                        <td>
                                            <strong class="atasan-name"><?php echo htmlspecialchars($map['nama_atasan']); ?></strong><br>
                                            <span class="atasan-nik text-muted">NIK: <?php echo htmlspecialchars($map['nik_atasan']); ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <button type="button" class="btn-action btn-edit" onclick="editMapping('<?php echo htmlspecialchars($map['nik']); ?>', '<?php echo htmlspecialchars($map['nik_atasan']); ?>')">
                                                ✏️ Edit
                                            </button>
                                            <form action="mapping_atasan_pegawai.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mapping atasan untuk pegawai ini?')">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="nik" value="<?php echo htmlspecialchars($map['nik']); ?>">
                                                <button type="submit" class="btn-action btn-delete">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('.select2').select2({
            width: '100%'
        });
    });

    // Validasi submit form (untuk memastikan pegawai != atasan)
    function validateFormSubmit() {
        const action = document.getElementById('formAction').value;
        let nikVal = '';
        
        if (action === 'edit') {
            nikVal = document.getElementById('nik_hidden').value;
        } else {
            nikVal = document.getElementById('nik').value;
        }
        
        const atasanVal = document.getElementById('nik_atasan').value;

        if (!nikVal || !atasanVal) {
            alert('Silakan pilih Pegawai dan Atasan!');
            return false;
        }

        if (nikVal === atasanVal) {
            alert('Error: Pegawai tidak boleh menjadi atasan dirinya sendiri!');
            return false;
        }

        return true;
    }

    // Set form ke mode edit
    function editMapping(nik, nikAtasan) {
        document.getElementById('formTitle').innerText = 'Edit Mapping Atasan';
        document.getElementById('formAction').value = 'edit';
        
        // Atur input hidden NIK (karena select dinonaktifkan dalam mode edit)
        const hiddenNik = document.getElementById('nik_hidden');
        hiddenNik.value = nik;
        hiddenNik.disabled = false;

        // Pilih value di dropdown NIK & atur disabled
        const selectNik = $('#nik');
        selectNik.val(nik).trigger('change');
        selectNik.prop('disabled', true);

        // Pilih value di dropdown Atasan
        $('#nik_atasan').val(nikAtasan).trigger('change');

        // Tampilkan tombol Batal & perbarui info
        document.getElementById('cancelBtn').style.display = 'inline-block';
        document.getElementById('infoBox').innerHTML = '<strong>Mode Edit:</strong> NIK Pegawai tidak dapat diubah karena merupakan kunci utama. Anda hanya dapat mengubah Atasan Langsung dari pegawai ini.';
        document.getElementById('infoBox').style.backgroundColor = '#fef3c7';
        document.getElementById('infoBox').style.color = '#b45309';
        document.getElementById('infoBox').style.borderColor = '#fde68a';
        document.getElementById('submitBtn').innerText = 'Simpan Perubahan';

        // Scroll ke form di device kecil
        document.getElementById('formTitle').scrollIntoView({ behavior: 'smooth' });
    }

    // Reset form ke mode tambah
    function resetForm() {
        document.getElementById('formTitle').innerText = 'Tambah Mapping Atasan';
        document.getElementById('formAction').value = 'tambah';

        // Nonaktifkan hidden NIK
        const hiddenNik = document.getElementById('nik_hidden');
        hiddenNik.value = '';
        hiddenNik.disabled = true;

        // Aktifkan kembali dropdown NIK & reset selection
        const selectNik = $('#nik');
        selectNik.prop('disabled', false);
        selectNik.val('').trigger('change');

        // Reset dropdown Atasan
        $('#nik_atasan').val('').trigger('change');

        // Sembunyikan tombol Batal & perbarui info
        document.getElementById('cancelBtn').style.display = 'none';
        document.getElementById('infoBox').innerHTML = 'Setiap pegawai hanya dapat memiliki satu atasan langsung dalam sistem persetujuan cuti.';
        document.getElementById('infoBox').style.backgroundColor = '#eff6ff';
        document.getElementById('infoBox').style.color = '#1e40af';
        document.getElementById('infoBox').style.borderColor = '#bfdbfe';
        document.getElementById('submitBtn').innerText = 'Simpan Mapping';
    }

    // Filter pencarian tabel (client-side pencarian responsif)
    function filterTable() {
        const query = document.getElementById('searchBar').value.toLowerCase();
        const rows = document.querySelectorAll('#mappingTable tbody tr');
        let visibleCount = 0;
        let isNoDataRow = false;

        rows.forEach(row => {
            if (row.classList.contains('no-data-row')) {
                isNoDataRow = true;
                return;
            }

            const empName = row.querySelector('.emp-name').innerText.toLowerCase();
            const empNik = row.querySelector('.emp-nik').innerText.toLowerCase();
            const atasanName = row.querySelector('.atasan-name').innerText.toLowerCase();
            const atasanNik = row.querySelector('.atasan-nik').innerText.toLowerCase();

            if (empName.includes(query) || empNik.includes(query) || atasanName.includes(query) || atasanNik.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update badge jumlah data terfilter
        document.getElementById('countBadge').innerText = visibleCount + ' Data';

        // Buat baris "tidak ditemukan" jika semua tersembunyi
        let noMatchRow = document.getElementById('noMatchRow');
        if (visibleCount === 0 && !isNoDataRow) {
            if (!noMatchRow) {
                const tbody = document.querySelector('#mappingTable tbody');
                noMatchRow = document.createElement('tr');
                noMatchRow.id = 'noMatchRow';
                noMatchRow.innerHTML = '<td colspan="4" class="no-data">Tidak ada data mapping yang cocok dengan pencarian Anda.</td>';
                tbody.appendChild(noMatchRow);
            }
        } else {
            if (noMatchRow) {
                noMatchRow.remove();
            }
        }
    }
</script>

</body>
</html>
