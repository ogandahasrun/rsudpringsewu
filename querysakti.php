<?php
// querysakti.php
include 'koneksi.php'; // Pastikan file koneksi.php ada dan benar

// Proses form jika ada input
$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$result = null;
$error = '';
if ($query) {
    // Cek keamanan query (hanya SELECT)
    if (stripos($query, 'select') === 0) {
        $result = mysqli_query($koneksi, $query);
        if (!$result) {
            $error = 'Query error: ' . mysqli_error($koneksi);
        }
    } else {
        $error = 'Hanya query SELECT yang diizinkan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Query Sakti</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
        textarea { width: 100%; height: 60px; }
    </style>
</head>
<body>
    <h2>Query Sakti (Hanya SELECT)</h2>
    <form method="post">
        <label for="query">Masukkan Query SQL (SELECT):</label><br>
        <textarea name="query" id="query" required><?= htmlspecialchars($query) ?></textarea><br>
        <button type="submit">Jalankan</button>
    </form>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <button onclick="copyTableToClipboard()" style="margin-top:10px;">Copy to Clipboard</button>
        <table id="resultTable">
            <tr>
                <?php while ($field = mysqli_fetch_field($result)): ?>
                    <th><?= htmlspecialchars($field->name) ?></th>
                <?php endwhile; ?>
            </tr>
            <?php mysqli_data_seek($result, 0); while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endwhile; ?>
        </table>
        <script>
        function copyTableToClipboard() {
            var table = document.getElementById('resultTable');
            var range, sel;
            if (document.createRange && window.getSelection) {
                range = document.createRange();
                sel = window.getSelection();
                sel.removeAllRanges();
                try {
                    range.selectNodeContents(table);
                    sel.addRange(range);
                } catch (e) {
                    range.selectNode(table);
                    sel.addRange(range);
                }
                document.execCommand('copy');
                sel.removeAllRanges();
                alert('Tabel berhasil disalin ke clipboard!');
            }
        }
        </script>
    <?php elseif ($result && mysqli_num_rows($result) === 0): ?>
        <div class="success">Query berhasil, tapi tidak ada data.</div>
    <?php endif; ?>
</body>
</html>
