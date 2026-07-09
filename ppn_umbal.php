<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Data Klaim</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px;
        }

        .container {
            width: calc(100vw - 20px);
            max-width: none;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 20px 24px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            margin-bottom: 5px;
        }

        .content {
            padding: 18px;
        }

        .filter-form {
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 18px;
            border: 1px solid #e9ecef;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group select,
        .form-group button {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .table-responsive {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            overflow-y: hidden;
            margin-top: 18px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dataTables_wrapper {
            padding: 10px;
        }

        .dataTables_filter {
            margin-bottom: 20px;
        }

        .dataTables_filter input {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            margin-left: 8px;
        }

        .dt-buttons {
            margin-bottom: 15px;
        }

        .dt-button {
            background: linear-gradient(135deg, #27ae60, #229954) !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            margin-right: 8px !important;
            font-size: 13px !important;
            transition: all 0.3s ease !important;
        }

        .dt-button:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 3px 10px rgba(39, 174, 96, 0.3) !important;
        }

        table.dataTable {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.dataTable thead th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: #ff0000;
            font-weight: 700;
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #d6d6d6;
            border-right: 1px solid #d6d6d6;
            font-size: 11px;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            padding: 8px 6px;
            border-bottom: 1px solid #e9ecef;
            border-right: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 11px;
            white-space: nowrap;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table.dataTable tbody tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.3s ease;
        }

        table.dataTable tfoot th {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            font-weight: 600;
            padding: 10px 6px;
            text-align: center;
            border: none;
            border-right: 1px solid #f1c0ba;
            font-size: 11px;
            white-space: nowrap;
        }

        table.dataTable thead th:last-child,
        table.dataTable tbody td:last-child,
        table.dataTable tfoot th:last-child {
            border-right: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .container {
                width: calc(100vw - 10px);
                margin: 5px auto;
                border-radius: 10px;
            }

            .content {
                padding: 20px 15px;
            }

            .filter-form {
                padding: 20px 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .header h1 {
                font-size: 24px;
            }

            .button-group {
                justify-content: center;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }

            table.dataTable {
                font-size: 10px;
            }

            table.dataTable thead th,
            table.dataTable tbody td,
            table.dataTable tfoot th {
                padding: 6px 4px;
            }
        }
</style>


</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LAMPUNG EYE CENTER</h1>
            <p>RINCIAN PASIEN BPJS</p>
            <p>Periode : <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?></p>
        </div>

        <div class="content">

            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="bulanklaim">Bulan Klaim:</label>
                        <select name="bulanklaim" id="bulanklaim" required>
                            <option value="">-- Pilih Bulan --</option>
                            <?php
                            $query_bulan = "SELECT DISTINCT bulanklaim FROM rspsw_umbal ORDER BY bulanklaim DESC";
                            $result_bulan = mysqli_query($koneksi, $query_bulan);
                            while ($row_bulan = mysqli_fetch_assoc($result_bulan)) {
                                $selected = (isset($_GET['bulanklaim']) && $_GET['bulanklaim'] == $row_bulan['bulanklaim']) ? 'selected' : '';
                                echo "<option value='{$row_bulan['bulanklaim']}' $selected>{$row_bulan['bulanklaim']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">🔍 Filter Data</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">🔄 Reset Form</button>
                </div>
            </form>

    <?php
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    if (isset($_GET['bulanklaim']) && !empty($_GET['bulanklaim'])) {
        $bulanklaim = mysqli_real_escape_string($koneksi, $_GET['bulanklaim']);

        $query = "SELECT 
                    rspsw_umbal.bulanklaim,
                    lec_umbal.tgl_registrasi AS tgl_sep,
                    lec_umbal.no_sep,
                    reg_periksa.no_rawat,
                    reg_periksa.tgl_registrasi AS tgl_registrasi,
                    lec_umbal.norm,
                    lec_umbal.nm_pasien,
                    lec_umbal.disetujui,
                    lec_umbal.status,
                    COALESCE(non_obat.total_non_obat, 0) AS biaya_non_obat,
                    COALESCE(obat.total_obat, 0) AS biaya_obat,
                    COALESCE(ppn_obat.total_ppn_obat, 0) AS ppn_obat,
                    COALESCE(total_billing.total_biaya, 0) AS total_biaya
                FROM 
                    lec_umbal
                    LEFT JOIN rspsw_umbal ON lec_umbal.no_sep = rspsw_umbal.no_sep
                    INNER JOIN reg_periksa ON lec_umbal.no_rawat = reg_periksa.no_rawat
                    LEFT JOIN (
                        SELECT 
                            no_rawat,
                            SUM(totalbiaya) AS total_non_obat
                        FROM billing
                        WHERE status <> 'obat'
                        GROUP BY no_rawat
                    ) AS non_obat ON lec_umbal.no_rawat = non_obat.no_rawat
                    LEFT JOIN (
                        SELECT 
                            no_rawat,
                            SUM(totalbiaya) AS total_obat
                        FROM billing
                        WHERE status = 'obat' AND nm_perawatan <> 'PPN Obat'
                        GROUP BY no_rawat
                    ) AS obat ON lec_umbal.no_rawat = obat.no_rawat
                    LEFT JOIN (
                        SELECT 
                            no_rawat,
                            SUM(totalbiaya) AS total_ppn_obat
                        FROM billing
                        WHERE nm_perawatan = 'PPN Obat'
                        GROUP BY no_rawat
                    ) AS ppn_obat ON lec_umbal.no_rawat = ppn_obat.no_rawat
                    LEFT JOIN (
                        SELECT 
                            no_rawat,
                            SUM(totalbiaya) AS total_biaya
                        FROM billing
                        GROUP BY no_rawat
                    ) AS total_billing ON lec_umbal.no_rawat = total_billing.no_rawat
                WHERE
                    rspsw_umbal.bulanklaim = '$bulanklaim'
                ORDER BY lec_umbal.no_sep";

        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) > 0) {
            echo "<div class='table-responsive'>
                  <table id='data-table' class='display nowrap' style='width:100%'>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Registrasi</th>
                            <th>Bulan Klaim</th>
                            <th>Tanggal SEP</th>
                            <th>No SEP</th>
                            <th>No Rawat</th>
                            <th>No RM</th>
                            <th>Nama Pasien</th>
                            <th>Disetujui</th>
                            <th>Status</th>
                            <th>Biaya Non Obat</th>
                            <th>Biaya Obat</th>
                            <th>PPN Obat</th>
                            <th>Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>";
            $no = 1;
            $total_disetujui = 0;
            $total_non_obat = 0;
            $total_obat = 0;
            $total_ppn_obat = 0;
            $grand_total_biaya = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['bulanklaim']}</td>
                        <td>{$row['tgl_sep']}</td>
                        <td>{$row['no_sep']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['norm']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>" . formatRupiah($row['disetujui']) . "</td>
                        <td>{$row['status']}</td>
                        <td>" . formatRupiah($row['biaya_non_obat']) . "</td>
                        <td>" . formatRupiah($row['biaya_obat']) . "</td>
                        <td>" . formatRupiah($row['ppn_obat']) . "</td>
                        <td>" . formatRupiah($row['total_biaya']) . "</td>
                    </tr>";
                $no++;
                $total_disetujui += $row['disetujui'];
                $total_non_obat += $row['biaya_non_obat'];
                $total_obat += $row['biaya_obat'];
                $total_ppn_obat += $row['ppn_obat'];
                $grand_total_biaya += $row['total_biaya'];
            }

            echo "</tbody>
                <tfoot>
                    <tr>
                        <th colspan='8'>Total</th>
                        <th>" . formatRupiah($total_disetujui) . "</th>
                        <th></th>
                        <th>" . formatRupiah($total_non_obat) . "</th>
                        <th>" . formatRupiah($total_obat) . "</th>
                        <th>" . formatRupiah($total_ppn_obat) . "</th>
                        <th>" . formatRupiah($grand_total_biaya) . "</th>
                    </tr>
                </tfoot>
                </table>
                </div>";
        } else {
            echo "<div class='no-data'>📊 Data tidak ditemukan untuk periode yang dipilih.</div>";
        }
    }
    ?>

        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#data-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape',
                        pageSize: 'A3',
                        title: 'Data Klaim RS Lampung Eye Center Periode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>',
                        customize: function(doc) {
                            doc.content.unshift({
                                text: 'LAMPUNG EYE CENTER\nRINCIAN PASIEN BPJS\nPeriode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>',
                                fontSize: 14,
                                bold: true,
                                margin: [0, 0, 0, 12]
                            });
                        }
                    }
                ],
                paging: false,
                responsive: true,
                scrollX: true
            });
        });

        // Function to reset form
        function resetForm() {
            document.getElementById('bulanklaim').value = '';
        }

        // Function to copy table data to clipboard
        function copyToClipboard() {
            const table = document.getElementById('data-table');
            if (table) {
                const range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                
                // Show notification
                alert('📋 Data berhasil disalin ke clipboard!');
            }
        }
    </script>
</body>
</html>
