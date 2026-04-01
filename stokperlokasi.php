<?php include "koneksi.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Barang Per Lokasi - RSUD Pringsewu</title>
    <style>
        /* Copy all styles from stokfarmasi.php for consistent look */
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 15px; align-items: end; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4); }
        .btn-success { background: linear-gradient(45deg, #28a745, #20c997); color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }
        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; min-width: 900px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; position: relative; }
        th.sortable { cursor: pointer; user-select: none; transition: all 0.3s ease; }
        th.sortable:hover { background: linear-gradient(45deg, #495057, #6c757d); }
        th.sortable::after { content: ' ⇅'; color: #ccc; font-size: 12px; margin-left: 5px; }
        th.sortable.asc::after { content: ' ▲'; color: #fff; }
        th.sortable.desc::after { content: ' ▼'; color: #fff; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .stock-cell { text-align: right; font-weight: bold; font-family: monospace; }
        .stock-zero { color: #dc3545; background-color: #f8d7da !important; }
        .stock-low { color: #856404; background-color: #fff3cd !important; }
        .stock-good { color: #155724; background-color: #d4edda !important; }
        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; gap: 15px; } th, td { padding: 8px 6px; font-size: 12px; } table { min-width: 700px; } }
        @media (max-width: 480px) { .header h1 { font-size: 1.3em; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Stok Barang Per Lokasi</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>
            <form method="GET" class="filter-form">
                <div class="filter-title">🔍 Filter Stok Barang Per Lokasi</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="bangsal">Pilih Bangsal/Depo</label>
                        <select id="bangsal" name="bangsal" required>
                            <option value="">-- Pilih Bangsal --</option>
                            <?php
                            $bangsal_q = mysqli_query($koneksi, "SELECT kd_bangsal, nm_bangsal FROM bangsal ORDER BY nm_bangsal ASC");
                            while ($b = mysqli_fetch_assoc($bangsal_q)) {
                                $selected = (isset($_GET['bangsal']) && $_GET['bangsal'] == $b['kd_bangsal']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($b['kd_bangsal'])."' $selected>".htmlspecialchars($b['nm_bangsal'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="cari">Cari Barang (Kode/Nama)</label>
                        <input type="text" id="cari" name="cari" placeholder="Masukkan kode/nama barang..." value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">🔍 Cari Data</button>
                    </div>
                    <div>
                        <button type="button" onclick="resetForm()" class="btn btn-success">🔄 Reset Filter</button>
                    </div>
                </div>
            </form>
            <?php
            $where = [];
            if (isset($_GET['bangsal']) && $_GET['bangsal'] != '') {
                $bangsal = mysqli_real_escape_string($koneksi, $_GET['bangsal']);
                $where[] = "(lokasi_barang_medis.kd_bangsal = '".$bangsal."' OR lokasi_barang_medis.kd_bangsal IS NULL)";
            }
            if (isset($_GET['cari']) && $_GET['cari'] != '') {
                $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
                $where[] = "(databarang.kode_brng LIKE '%$cari%' OR databarang.nama_brng LIKE '%$cari%')";
            }
            $where_sql = count($where) ? 'WHERE '.implode(' AND ', $where) : '';
            $query = "
                SELECT
                    databarang.kode_brng,
                    databarang.nama_brng,
                    databarang.kode_sat,
                    IFNULL(gudangbarang.stok, 0) AS stok,
                    lokasi_barang_medis.kd_bangsal,
                    lokasi_barang_medis.lokasi
                FROM
                    databarang
                LEFT JOIN lokasi_barang_medis ON lokasi_barang_medis.kode_brng = databarang.kode_brng
                LEFT JOIN gudangbarang ON gudangbarang.kode_brng = databarang.kode_brng AND (
                    (lokasi_barang_medis.kd_bangsal IS NOT NULL AND gudangbarang.kd_bangsal = lokasi_barang_medis.kd_bangsal)
                    OR (lokasi_barang_medis.kd_bangsal IS NULL AND gudangbarang.kd_bangsal = '$_GET[bangsal]')
                )
                $where_sql
                ORDER BY databarang.nama_brng ASC
            ";
            $result = mysqli_query($koneksi, $query);
            ?>
            <div class="actions-bar" style="margin-bottom:10px;display:flex;justify-content:flex-end;">
                <button class="btn btn-success" onclick="copyTableToClipboard('dataTable')" type="button">
                    📋 Copy Tabel
                </button>
            </div>
            <div class="table-responsive">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th style="text-align:center;">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Depo</th>
                            <th>Lokasi Barang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td style='text-align:center;'>".$no."</td>";
                                echo "<td>".htmlspecialchars($row['kode_brng'])."</td>";
                                echo "<td>".htmlspecialchars($row['nama_brng'])."</td>";
                                echo "<td>".htmlspecialchars($row['kode_sat'])."</td>";
                                echo "<td class='stock-cell'>".number_format($row['stok'])."</td>";
                                echo "<td>".(isset($_GET['bangsal']) && $_GET['bangsal'] != '' ? htmlspecialchars($_GET['bangsal']) : '-')."</td>";
                                echo "<td>".htmlspecialchars($row['lokasi'])."</td>";
                                echo "</tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;color:#888;'>Tidak ada data ditemukan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    function resetForm() {
        document.getElementById('bangsal').selectedIndex = 0;
        document.getElementById('cari').value = '';
        document.getElementById('cari').focus();
    }

    function copyTableToClipboard(tableID) {
        const table = document.getElementById(tableID);
        const textarea = document.createElement("textarea");
        let text = "";
        for (let row of table.rows) {
            let rowData = [];
            for (let cell of row.cells) {
                let cellText = cell.innerText.replace(/Rp\s?/g, '').replace(/\./g, '');
                rowData.push(cellText);
            }
            text += rowData.join("\t") + "\n";
        }
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        showNotification("✅ Data tabel berhasil disalin ke clipboard!", "success");
    }
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            font-weight: bold;
            transform: translateX(400px);
            transition: all 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }
    </script>
</body>
</html>
