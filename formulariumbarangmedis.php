<?php
include "koneksi.php";

// Ambil semua industri untuk combo box
$industriList = [];
$industriRes = $koneksi->query("SELECT kode_industri, nama_industri FROM industrifarmasi ORDER BY nama_industri");
while ($row = $industriRes->fetch_assoc()) {
    $industriList[$row['kode_industri']] = $row['nama_industri'];
}

// Proses update industri
if (isset($_POST['edit_kode_brng']) && isset($_POST['edit_kode_industri'])) {
    $kode_brng = $koneksi->real_escape_string($_POST['edit_kode_brng']);
    $kode_industri = $koneksi->real_escape_string($_POST['edit_kode_industri']);
    $koneksi->query("UPDATE databarang SET kode_industri='$kode_industri' WHERE kode_brng='$kode_brng'");
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Query utama
$query = "SELECT
            databarang.kode_brng,
            databarang.nama_brng,
            databarang.kode_sat,
            databarang.kode_industri,
            industrifarmasi.nama_industri,
            databarang.`status`
            FROM databarang
            INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
            WHERE databarang.status = '1' AND databarang.kode_kategori = 'OBT'";

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $koneksi->real_escape_string($_GET['search']);
    $query .= " WHERE
                databarang.kode_brng LIKE '%$searchTerm%'
                OR databarang.nama_brng LIKE '%$searchTerm%'
                OR databarang.kode_sat LIKE '%$searchTerm%'
                OR industrifarmasi.nama_industri LIKE '%$searchTerm%'";
}

$result = $koneksi->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontrol Formularium</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        table { width: 100%; margin-top: 20px; background-color: #fff; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; }
        tr:nth-child(odd) { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #fff; }
        th { cursor: pointer; user-select: none; }
        .edit-btn, .save-btn { padding: 2px 10px; font-size: 0.9em; }
    </style>

    <script>
    // Sorting tabel native
    function sortTable(colIndex) {
        let table = document.getElementById("dataTable");
        let rows = Array.from(table.querySelectorAll("tr:nth-child(n+2)")); // skip header

        let asc = table.getAttribute("data-sort-dir-" + colIndex) !== "asc"; 
        // toggle arah sorting, default ASC

        rows.sort((a, b) => {
            let x = a.cells[colIndex].innerText.trim().toLowerCase();
            let y = b.cells[colIndex].innerText.trim().toLowerCase();

            if (!isNaN(x) && !isNaN(y)) { // kalau angka
                return asc ? (x - y) : (y - x);
            }
            return asc ? x.localeCompare(y) : y.localeCompare(x);
        });

        // re-append rows ke tabel
        rows.forEach(row => table.tBodies[0].appendChild(row));

        // simpan state arah sort
        table.setAttribute("data-sort-dir-" + colIndex, asc ? "asc" : "desc");
    }

    // Edit industri
    function editIndustri(rowId) {
        document.getElementById('select-industri-' + rowId).disabled = false;
        document.getElementById('edit-btn-' + rowId).style.display = 'none';
        document.getElementById('save-btn-' + rowId).style.display = 'inline-block';
    }
    </script>

</head>
<body>
<div class="container">
    <h2>Kontrol Formularium</h2>
    <!-- Search -->
    <form action="" method="GET">
        <div class="form-group">
            <label for="search">Pencarian :</label>
            <input type="text" class="form-control" name="search" id="search" placeholder="Masukkan Keyword"
                   value="<?= isset($searchTerm) ? htmlspecialchars($searchTerm) : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <?php if (!$result) { echo "Query error: " . $koneksi->error; } else { ?>
    <table class="table table-bordered" id="dataTable">
        <tr>
            <th onclick="sortTable(0)">Kode Barang &#x25B2;&#x25BC;</th>
            <th onclick="sortTable(1)">Nama Barang &#x25B2;&#x25BC;</th>
            <th onclick="sortTable(2)">Satuan &#x25B2;&#x25BC;</th>
            <th onclick="sortTable(3)">Industri &#x25B2;&#x25BC;</th>
            <th onclick="sortTable(4)">Status &#x25B2;&#x25BC;</th>
            <th>Aksi</th>
        </tr>
        <?php
        $rowId = 1;
        while ($row = $result->fetch_assoc()) {
            $kodeBrng = htmlspecialchars($row['kode_brng']);
            $kodeIndustri = htmlspecialchars($row['kode_industri']);
            echo "<tr>";
            echo "<td>{$kodeBrng}</td>";
            echo "<td>" . htmlspecialchars($row['nama_brng']) . "</td>";
            echo "<td>" . htmlspecialchars($row['kode_sat']) . "</td>";

            // Industri select langsung di cell
            echo "<td>
                <form method='POST' style='margin:0;'>
                    <input type='hidden' name='edit_kode_brng' value='{$kodeBrng}'>
                    <select id='select-industri-{$rowId}' name='edit_kode_industri' disabled>";
            foreach ($industriList as $kode => $nama) {
                $selected = ($kode == $kodeIndustri) ? "selected" : "";
                echo "<option value='" . htmlspecialchars($kode) . "' $selected>" . htmlspecialchars($nama) . "</option>";
            }
            echo "</select>
            </td>";

            echo "<td>" . htmlspecialchars($row['status']) . "</td>";

            // Tombol
            echo "<td>
                <button type='button' class='btn btn-warning btn-sm edit-btn' id='edit-btn-{$rowId}' onclick='editIndustri({$rowId})'>Edit</button>
                <button type='submit' class='btn btn-success btn-sm save-btn' id='save-btn-{$rowId}' style='display:none'>Simpan</button>
                </form>
            </td>";

            echo "</tr>";
            $rowId++;
        }
        ?>
    </table>
    <?php } ?>
</div>
<?php $koneksi->close(); ?>
</body>
</html>
