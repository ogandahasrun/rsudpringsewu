<?php if (!empty($data)) { $no = 1; foreach ($data as $row) { ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                        <td><?php echo htmlspecialchars($row['jumlah']); ?></td>
                        <td><?php echo htmlspecialchars($row['satuan']); ?></td>
                        <td class="currency"><?php echo number_format(ceil($row['harga'])); ?></td>
                        <td class="currency"><?php echo number_format(ceil($row['total'])); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="5" class="currency"><strong>Total</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format(ceil($total_summary)); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="currency"><strong>PPN 11%:</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format(ceil($ppn)); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="currency"><strong>Total dengan PPN</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format(ceil($total_with_ppn)); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="6" class="currency"><strong>Terbilang</strong> <?php echo $terbilang; ?></td>
                </tr>
                <?php } else { ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Data tidak ditemukan</td>
                </tr>
                <?php } ?>