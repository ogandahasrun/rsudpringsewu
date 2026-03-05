<?php
// Sertakan file koneksi.php
include "koneksi.php";

// Proses update nama pasien jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_rkm_medis'], $_POST['nm_pasien'])) {
    $no_rkm_medis = $koneksi->real_escape_string($_POST['no_rkm_medis']);
    $nm_pasien = $koneksi->real_escape_string($_POST['nm_pasien']);
    $update = $koneksi->query("UPDATE pasien SET nm_pasien='$nm_pasien' WHERE no_rkm_medis='$no_rkm_medis'");
    if ($update) {
        $success = "Nama pasien berhasil diupdate.";
    } else {
        $error = "Gagal mengupdate nama pasien.";
    }
}

// Pencarian pasien berdasarkan no_rkm_medis
$no_rkm_medis = isset($_GET['no_rkm_medis']) ? trim($koneksi->real_escape_string($_GET['no_rkm_medis'])) : '';
$pasien = null;
// Untuk debug: tampilkan query dan hasil
$query_error = '';
$debug_query = '';
$debug_result = '';
if ($no_rkm_medis) {
    $sql = "SELECT pasien.no_rkm_medis, pasien.nm_pasien FROM pasien WHERE pasien.no_rkm_medis='$no_rkm_medis'";
    $debug_query = $sql;
    $result = $koneksi->query($sql);
    if ($result === false) {
        $query_error = "Query error: " . $koneksi->error;
    } else {
        $pasien = $result->fetch_assoc();
        $debug_result = json_encode($pasien);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Nama Pasien - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; position: relative; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .logo-link { position: absolute; left: 25px; top: 50%; transform: translateY(-50%); }
        .logo-link img { width: 50px; height: auto; border-radius: 8px; transition: transform 0.3s ease; }
        .logo-link:hover img { transform: scale(1.1); }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .search-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .search-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .search-grid { display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end; }
        .search-group { display: flex; flex-direction: column; gap: 8px; }
        .search-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .search-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .search-group input:focus { outline: none; border-color: #28a745; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn:hover { transform: translateY(-2px); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .center { text-align: center; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .search-form { padding: 20px 15px; }
            .search-grid { grid-template-columns: 1fr; gap: 15px; }
            .logo-link { position: relative; left: auto; top: auto; transform: none; margin-bottom: 15px; }
            th, td { padding: 8px 6px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="logo-link">
                <img src="images/logo.png" alt="Logo RSUD Pringsewu">
            </a>
            <h1>📝 Update Nama Pasien</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
            </div>
            <!-- Form pencarian pasien -->
            <form action="" method="get" class="search-form">
                <div class="search-title">
                    🔍 Cari Pasien Berdasarkan No. RM
                </div>
                <div class="search-grid">
                    <div class="search-group">
                        <label for="no_rkm_medis">No. Rekam Medis</label>
                        <input type="text" id="no_rkm_medis" name="no_rkm_medis" value="<?php echo htmlspecialchars($no_rkm_medis); ?>" placeholder="Masukkan No. RM..." required>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Cari</button>
                </div>
                <?php if ($no_rkm_medis): ?>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="updatenamapasien.php" class="btn btn-secondary">🔄 Reset Pencarian</a>
                    </div>
                <?php endif; ?>
            </form>
            <?php if (isset($success)): ?>
                <div style="margin: 15px 0; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724; text-align:center;">
                    <?php echo $success; ?>
                </div>
            <?php elseif (isset($error)): ?>
                <div style="margin: 15px 0; padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24; text-align:center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($query_error)): ?>
                <div style="margin: 15px 0; padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24; text-align:center;">
                    <?php echo $query_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pasien): ?>
                        <tr>
                            <form action="" method="post">
                                <td class="center">
                                    <input type="hidden" name="no_rkm_medis" value="<?php echo htmlspecialchars($pasien['no_rkm_medis']); ?>">
                                    <?php echo htmlspecialchars($pasien['no_rkm_medis']); ?>
                                </td>
                                <td>
                                    <input type="text" name="nm_pasien" value="<?php echo htmlspecialchars($pasien['nm_pasien']); ?>" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                                </td>
                                <td class="center">
                                    <button type="submit" class="btn btn-warning">💾 Simpan</button>
                                </td>
                            </form>
                        </tr>
                        <?php elseif ($no_rkm_medis): ?>
                        <tr><td colspan="3" class="no-data">Pasien dengan No. RM <strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong> tidak ditemukan.</td></tr>
                        <?php else: ?>
                        <tr><td colspan="3" class="no-data">Silakan cari pasien dengan memasukkan No. RM.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$koneksi->close();
?>
