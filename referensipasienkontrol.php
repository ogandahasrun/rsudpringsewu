<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referensi Pasien Kontrol - RSUD Pringsewu</title>
    <style>
        /* Copy all styles from referensipasienmjkn.php */
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4); }
        .btn-success { background: linear-gradient(45deg, #28a745, #20c997); color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }
        .btn-secondary { background: #6c757d; color: white; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; min-width: 900px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; gap: 15px; } .filter-actions { justify-content: stretch; } .btn { padding: 10px 15px; font-size: 13px; } th, td { padding: 8px 6px; font-size: 12px; } table { min-width: 700px; } }
        @media (max-width: 480px) { .header h1 { font-size: 1.3em; } .filter-title { font-size: 16px; } }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }
        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('nm_poli').value = '';
            document.getElementById('nm_dokter_bpjs').value = '';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Referensi Pasien Kontrol</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="bpjs.php">← Kembali ke BPJS</a>
            </div>
<?php
include 'koneksi.php';
// Ambil data untuk dropdown filter
function getOptions($koneksi, $field, $table, $where = "") {
    $options = [];
    $query = "SELECT DISTINCT $field FROM $table $where ORDER BY $field";
    $result = mysqli_query($koneksi, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $options[] = $row[$field];
    }
    return $options;
}
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
$nm_poli = isset($_POST['nm_poli']) ? $_POST['nm_poli'] : '';
$nm_dokter_bpjs = isset($_POST['nm_dokter_bpjs']) ? $_POST['nm_dokter_bpjs'] : '';
$nm_poli_options = getOptions($koneksi, 'nm_poli_bpjs', 'bridging_surat_kontrol_bpjs');
$nm_dokter_bpjs_options = getOptions($koneksi, 'nm_dokter_bpjs', 'bridging_surat_kontrol_bpjs');
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Referensi Pasien Kontrol
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Rencana Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Rencana Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="nm_poli">🏥 Nama Poli BPJS</label>
                        <select id="nm_poli" name="nm_poli">
                            <option value="">-- Semua Poli --</option>
                            <?php foreach ($nm_poli_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nm_poli == $opt) echo "selected"; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="nm_dokter_bpjs">👨‍⚕️ Nama Dokter BPJS</label>
                        <select id="nm_dokter_bpjs" name="nm_dokter_bpjs">
                            <option value="">-- Semua Dokter --</option>
                            <?php foreach ($nm_dokter_bpjs_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nm_dokter_bpjs == $opt) echo "selected"; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">📊 Tampilkan Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset Filter</button>
                </div>
            </form>
<?php
if (isset($_POST['filter'])) {
    $where_conditions = ["bridging_surat_kontrol_bpjs.tgl_rencana BETWEEN '" . $tanggal_awal . "' AND '" . $tanggal_akhir . "'"];
    if ($nm_poli != '') {
        $where_conditions[] = "bridging_surat_kontrol_bpjs.nm_poli_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_poli) . "'";
    }
    if ($nm_dokter_bpjs != '') {
        $where_conditions[] = "bridging_surat_kontrol_bpjs.nm_dokter_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_dokter_bpjs) . "'";
    }
    $where = "WHERE " . implode(" AND ", $where_conditions);
    $query = "SELECT bridging_sep.nomr, bridging_sep.nama_pasien, bridging_surat_kontrol_bpjs.tgl_rencana, bridging_surat_kontrol_bpjs.nm_dokter_bpjs, bridging_surat_kontrol_bpjs.nm_poli_bpjs FROM bridging_surat_kontrol_bpjs INNER JOIN bridging_sep ON bridging_surat_kontrol_bpjs.no_sep = bridging_sep.no_sep $where ORDER BY bridging_surat_kontrol_bpjs.tgl_rencana DESC";
    $result = mysqli_query($koneksi, $query);
    if ($result) {
        $total_rows = mysqli_num_rows($result);
        echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
        echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> referensi</div>';
        echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
        echo '</div>';
        echo "<div class='table-responsive'><table>\n<tr>\n<th>No</th>\n<th>No RM</th>\n<th>Nama Pasien</th>\n<th>Tanggal Rencana</th>\n<th>Dokter BPJS</th>\n<th>Poli BPJS</th>\n</tr>";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>\n<td>{$no}</td>\n<td>{$row['nomr']}</td>\n<td>{$row['nama_pasien']}</td>\n<td>{$row['tgl_rencana']}</td>\n<td>{$row['nm_dokter_bpjs']}</td>\n<td>{$row['nm_poli_bpjs']}</td>\n</tr>";
            $no++;
        }
        echo "</table></div>";
        if ($total_rows == 0) {
            echo '<div class="no-data">📊 Tidak ada data referensi pada filter yang dipilih</div>';
        }
    } else {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
        echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
        echo '</div>';
    }
    mysqli_close($koneksi);
}
?>
        </div>
    </div>
</body>
</html>
