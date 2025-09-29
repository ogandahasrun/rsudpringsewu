<?php
include 'koneksi.php';

$no_faktur = isset($_GET['no_faktur']) ? $_GET['no_faktur'] : '';
$pesan = '';
$foto_paths = ['','',''];

// Ambil data dokumentasi jika sudah ada
$q = mysqli_query($koneksi, "SELECT * FROM pemesanan_dokumentasi WHERE no_faktur='$no_faktur' LIMIT 1");
if ($row = mysqli_fetch_assoc($q)) {
    $foto_paths[0] = $row['foto1'];
    $foto_paths[1] = $row['foto2'];
    $foto_paths[2] = $row['foto3'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $no_faktur = $_POST['no_faktur'];
    $upload_dir = 'uploads/faktur/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Cek sudah ada berapa foto
    $q = mysqli_query($koneksi, "SELECT * FROM pemesanan_dokumentasi WHERE no_faktur='$no_faktur' LIMIT 1");
    if ($row = mysqli_fetch_assoc($q)) {
        $foto_paths[0] = $row['foto1'];
        $foto_paths[1] = $row['foto2'];
        $foto_paths[2] = $row['foto3'];
    }

    // Cari slot kosong
    $slot = -1;
    for ($i=0; $i<3; $i++) {
        if (empty($foto_paths[$i])) {
            $slot = $i;
            break;
        }
    }

    if ($slot === -1) {
        $pesan = "Sudah ada 3 foto untuk faktur ini!";
    } else {
        $file_name = $no_faktur . '_' . ($slot+1) . '.jpg';
        $target_file = $upload_dir . $file_name;
        $tmp_file = $_FILES['foto']['tmp_name'];
        $max_size = 200 * 1024; // 200 KB

        // Kompresi dan konversi ke JPG
        $image_info = getimagesize($tmp_file);
        if ($image_info) {
            $src_img = false;
            switch ($image_info['mime']) {
                case 'image/jpeg':
                    $src_img = imagecreatefromjpeg($tmp_file);
                    break;
                case 'image/png':
                    $src_img = imagecreatefrompng($tmp_file);
                    break;
                case 'image/gif':
                    $src_img = imagecreatefromgif($tmp_file);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $src_img = imagecreatefromwebp($tmp_file);
                    }
                    break;
            }
            if ($src_img) {
                // Resize jika lebar > 1280px (opsional)
                $width = imagesx($src_img);
                $height = imagesy($src_img);
                $max_width = 1280;
                if ($width > $max_width) {
                    $new_width = $max_width;
                    $new_height = intval($height * $new_width / $width);
                    $dst_img = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                } else {
                    $dst_img = $src_img;
                }

                // Kompresi dan simpan (loop kualitas sampai <200KB)
                $quality = 80;
                do {
                    ob_start();
                    imagejpeg($dst_img, null, $quality);
                    $img_data = ob_get_clean();
                    $img_size = strlen($img_data);
                    $quality -= 5;
                } while ($img_size > $max_size && $quality > 30);

                // Simpan file hasil kompresi
                file_put_contents($target_file, $img_data);

                // Hapus resource
                if (isset($src_img) && $src_img !== $dst_img) imagedestroy($src_img);
                if (isset($dst_img)) imagedestroy($dst_img);

                // Simpan nama file ke database
                $field = 'foto'.($slot+1);
                if ($q && mysqli_num_rows($q) > 0) {
                    mysqli_query($koneksi, "UPDATE pemesanan_dokumentasi SET $field='$file_name' WHERE no_faktur='$no_faktur'");
                } else {
                    $foto1 = $slot==0 ? $file_name : '';
                    $foto2 = $slot==1 ? $file_name : '';
                    $foto3 = $slot==2 ? $file_name : '';
                    mysqli_query($koneksi, "INSERT INTO pemesanan_dokumentasi (no_faktur, foto1, foto2, foto3) VALUES ('$no_faktur', '$foto1', '$foto2', '$foto3')");
                }
                $pesan = "Foto berhasil diupload dan dikompresi!";
                $foto_paths[$slot] = $file_name;
            } else {
                $pesan = "Format gambar tidak didukung!";
            }
        } else {
            $pesan = "File bukan gambar!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Foto Faktur</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; padding: 30px; }
        .form-upload { background: #f9f9f9; padding: 24px; border-radius: 8px; max-width: 400px; margin: auto; }
        label { display: block; margin-bottom: 8px; }
        input[type="file"] { margin-bottom: 16px; }
        button { padding: 6px 18px; background: #007bff; color: #fff; border: none; border-radius: 4px; }
        .pesan { margin-bottom: 16px; color: green; }
        .foto-preview { margin-bottom: 10px; }
        .foto-preview img { max-width: 100%; max-height: 120px; margin-bottom: 4px; display: block; }
        a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-upload">
        <h2>Upload Foto Faktur</h2>
        <?php if ($pesan): ?>
            <div class="pesan"><?php echo htmlspecialchars($pesan); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="no_faktur" value="<?php echo htmlspecialchars($no_faktur); ?>">
            <label>No Faktur: <strong><?php echo htmlspecialchars($no_faktur); ?></strong></label>
            <?php for ($i=0; $i<3; $i++): ?>
                <div class="foto-preview">
                    <?php if (!empty($foto_paths[$i])): ?>
                        <div>Foto <?php echo $i+1; ?>:<br>
                            <img src="uploads/faktur/<?php echo htmlspecialchars($foto_paths[$i]); ?>" alt="Foto <?php echo $i+1; ?>">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
            <?php
            $foto_tersedia = 0;
            for ($i=0; $i<3; $i++) if (!empty($foto_paths[$i])) $foto_tersedia++;
            ?>
            <?php if ($foto_tersedia < 3): ?>
                <label>Upload Foto Faktur ke-<?php echo $foto_tersedia+1; ?>:</label>
                <input type="file" name="foto" accept="image/*" required>
                <button type="submit">Upload</button>
            <?php else: ?>
                <div style="color:#d32f2f;">Maksimal 3 foto per faktur.</div>
            <?php endif; ?>
        </form>
        <br>
        <a href="pemesanandokumentasi.php">← Kembali ke Daftar Faktur</a>
    </div>
</body>
</html>