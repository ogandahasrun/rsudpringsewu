<?php
include 'koneksi.php';

$no_faktur = isset($_GET['no_faktur']) ? $_GET['no_faktur'] : '';
$pesan = '';
$foto_paths = ['','',''];

// Ambil parameter filter dari GET untuk tombol kembali
$filter_params = '';
$filter_keys = ['tgl_pesan_awal','tgl_pesan_akhir','tgl_faktur_awal','tgl_faktur_akhir','nama_suplier'];
foreach ($filter_keys as $key) {
    if (isset($_GET[$key])) {
        $filter_params .= '&' . $key . '=' . urlencode($_GET[$key]);
    }
}

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
        $safe_no_faktur = preg_replace('/[^A-Za-z0-9]/', '', $no_faktur);
        $file_name = $safe_no_faktur . '_' . ($slot+1) . '.jpg';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto Faktur</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 1.5em;
            font-weight: bold;
        }
        .form-upload {
            padding: 30px;
        }
        .faktur-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .faktur-info label {
            font-size: 16px;
            color: #333;
            margin: 0;
        }
        .faktur-info strong {
            color: #007bff;
            font-size: 18px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        input[type="file"]:hover,
        input[type="file"]:focus {
            background: #e3f2fd;
            border-color: #0056b3;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .pesan {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }
        .pesan.success {
            background: #d1edff;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .pesan.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .foto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .foto-preview {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .foto-preview img {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 8px;
        }
        .foto-preview .foto-label {
            font-size: 14px;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
        }
        .back-button {
            margin-top: 25px;
            text-align: center;
        }
        .back-button a {
            display: inline-block;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        .max-photos {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ffeaa7;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h2 {
                font-size: 1.3em;
            }
            .form-upload {
                padding: 20px 15px;
            }
            .foto-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .foto-preview img {
                max-height: 120px;
            }
            button {
                padding: 12px;
                font-size: 15px;
            }
            .back-button a {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .header {
                padding: 15px 10px;
            }
            .header h2 {
                font-size: 1.2em;
            }
            .form-upload {
                padding: 15px 10px;
            }
            .faktur-info {
                padding: 12px;
            }
            input[type="file"] {
                padding: 10px;
                font-size: 13px;
            }
            button {
                padding: 10px;
                font-size: 14px;
                /* Better touch target */
                min-height: 44px;
            }
            .back-button a {
                /* Better touch target */
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            button:hover {
                transform: none;
            }
            .back-button a:hover {
                transform: none;
            }
            /* Larger touch targets */
            button, .back-button a {
                min-height: 48px;
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📸 Upload Foto Faktur</h2>
        </div>
        
        <div class="form-upload">
            <div class="faktur-info">
                <label><strong>No Faktur:</strong> <?php echo htmlspecialchars($no_faktur); ?></label>
            </div>
            
            <?php if ($pesan): ?>
                <div class="pesan <?php echo strpos($pesan, 'berhasil') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tampilkan foto yang sudah ada -->
            <?php
            $foto_tersedia = 0;
            for ($i=0; $i<3; $i++) if (!empty($foto_paths[$i])) $foto_tersedia++;
            ?>
            
            <?php if ($foto_tersedia > 0): ?>
                <div class="foto-grid">
                    <?php for ($i=0; $i<3; $i++): ?>
                        <?php if (!empty($foto_paths[$i])): ?>
                            <div class="foto-preview">
                                <div class="foto-label">📷 Foto <?php echo $i+1; ?></div>
                                <img src="uploads/faktur/<?php echo htmlspecialchars($foto_paths[$i]); ?>" alt="Foto <?php echo $i+1; ?>">
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form upload -->
            <?php if ($foto_tersedia < 3): ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="no_faktur" value="<?php echo htmlspecialchars($no_faktur); ?>">
                    
                    <label>📤 Upload Foto Faktur ke-<?php echo $foto_tersedia+1; ?>:</label>
                    <input type="file" name="foto" accept="image/*" required>
                    <button type="submit">
                        ⬆️ Upload Foto
                    </button>
                </form>
            <?php else: ?>
                <div class="max-photos">
                    ✅ Maksimal 3 foto telah tercapai
                </div>
            <?php endif; ?>
            
            <div class="back-button">
                <a href="pemesanandokumentasi.php?<?php echo ltrim($filter_params, '&'); ?>">
                    ← Kembali ke Daftar Faktur
                </a>
            </div>
        </div>
    </div>
</body>
</html>