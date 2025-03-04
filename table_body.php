<?php
if (isset($result) && mysqli_num_rows($result) > 0) {
    $nomor_urut = 1;
    $total_keseluruhan = 0;

    mysqli_data_seek($result, 0); // Reset pointer result set ke awal

    while ($row = mysqli_fetch_assoc($result)) {
        $total = $row['jumlah'] * $row['h_pesan'];
        $total_keseluruhan += $total;

        echo "<tr>";
        echo "<td>" . $nomor_urut . "</td>";
        echo "<td>" . $row['nama_brng'] . "</td>";
        echo "<td class='text-right'>" . number_format($row['jumlah'], 0, ',', '.') . "</td>";
        echo "<td>" . $row['satuan'] . "</td>";
        echo "<td class='text-right'>" . number_format($row['h_pesan'], 0, ',', '.') . "</td>";
        echo "<td class='text-right'>" . number_format($total, 0, ',', '.') . "</td>";
        echo "</tr>";

        $nomor_urut++;
    }

    // Menampilkan total keseluruhan
    echo "<tr>";
    echo "<td colspan='5'><strong>Total Keseluruhan</strong></td>";
    echo "<td class='text-right'><strong>" . number_format($total_keseluruhan, 0, ',', '.') . "</strong></td>";
    echo "</tr>";

    // Menghitung PPN 11%
    $ppn = $total_keseluruhan * 0.11;

    // Menampilkan PPN
    echo "<tr>";
    echo "<td colspan='5'><strong>PPN (11%)</strong></td>";
    echo "<td class='text-right'><strong>" . number_format($ppn, 0, ',', '.') . "</strong></td>";
    echo "</tr>";

    // Menghitung total akhir (total_keseluruhan + PPN)
    $total_akhir = $total_keseluruhan + $ppn;

    // Menampilkan total akhir
    echo "<tr>";
    echo "<td colspan='5'><strong>Total</strong></td>";
    echo "<td class='text-right'><strong>" . number_format($total_akhir, 0, ',', '.') . "</strong></td>";
    echo "</tr>";
} else {
    if (isset($_POST['filter'])) {
        echo "<tr><td colspan='6'>Tidak ada data yang ditemukan untuk nomor faktur: $no_faktur</td></tr>";
    }
}
?>