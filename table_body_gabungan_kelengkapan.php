<?php if (!empty($data)) { $no = 1; foreach ($data as $row) { ?>
    <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
            <td><?php echo htmlspecialchars($row['jumlah']); ?></td>
            <td><?php echo htmlspecialchars($row['satuan']); ?></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    <?php } ?>
    <?php } else { ?>
    <tr>
        <td colspan="6" style="text-align:center;">Data tidak ditemukan</td>
    </tr>
<?php } ?>