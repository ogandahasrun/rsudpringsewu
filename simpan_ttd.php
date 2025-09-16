<?php
if (isset($_POST['img']) && isset($_POST['no_rawat']) && isset($_POST['waktu'])) {
    $img = $_POST['img'];
    $no_rawat = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['no_rawat']); // amankan nama file
    $waktu = preg_replace('/[^0-9]/', '', $_POST['waktu']);
    $folder = "image/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    $filename = $folder . $no_rawat . "_" . $waktu . ".png";
    if (file_put_contents($filename, $data)) {
        echo "Tanda tangan berhasil disimpan sebagai $filename";
    } else {
        echo "Gagal menyimpan tanda tangan.";
    }
} else {
    echo "Data tidak lengkap.";
}
?>