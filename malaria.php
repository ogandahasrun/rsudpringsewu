<!DOCTYPE html>
<html>
<head>
	<title>SURVEILANS MALARIA</title>
</head>
<body>
 
	<h2>SURVEILANS - MALARIA</h2>
	<br/>
	<a href="tambah.php"></a>
	<br/>
	<table border="1">
		<tr>
			<th>NO</th>
			<th>no_rawat</th>
			<th>tgl_registrasi</th>
			<th>no_rkm_medis</th>
            <th>nm_pasien</th>
            <th>kd_jenis_prw</th>
            <th>nm_perawatan</th>
            <th>tgl_periksa</th>
            <th>jam</th>
            <th>nilai</th>
            <th>nilai_rujukan</th>
            <th>keterangan</th>        
			<th>OPSI</th>
		</tr>
		<?php 
		include 'koneksi.php';
		$no = 1;
		$data = mysqli_query($koneksi,"
                
SELECT
reg_periksa.no_rawat AS no_rawat,
reg_periksa.tgl_registrasi AS tgl_registrasi,
pasien.no_rkm_medis AS no_rkm_medis,
pasien.nm_pasien AS nm_pasien,
detail_periksa_lab.kd_jenis_prw AS kd_jenis_prw,
template_laboratorium.Pemeriksaan,
detail_periksa_lab.tgl_periksa AS tgl_periksa,
detail_periksa_lab.jam AS jam,
detail_periksa_lab.nilai AS nilai,
detail_periksa_lab.nilai_rujukan AS nilai_rujukan,
detail_periksa_lab.keterangan AS keterangan
FROM
(((reg_periksa
JOIN pasien ON (reg_periksa.no_rkm_medis = pasien.no_rkm_medis))
JOIN detail_periksa_lab ON (reg_periksa.no_rawat = detail_periksa_lab.no_rawat)))
INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
WHERE
template_laboratorium.id_template = '467'
 ");
		while($d = mysqli_fetch_array($data)){
			?>
			<tr>
				<td><?php echo $no++; ?></td>
				<td><?php echo $d['no_rawat']; ?></td>
				<td><?php echo $d['tgl_registrasi']; ?></td>
				<td><?php echo $d['no_rkm_medis']; ?></td>
                <td><?php echo $d['nm_pasien']; ?></td>
                <td><?php echo $d['kd_jenis_prw']; ?></td>
                <td><?php echo $d['Pemeriksaan']; ?></td>
                <td><?php echo $d['tgl_periksa']; ?></td>
                <td><?php echo $d['jam']; ?></td>
                <td><?php echo $d['nilai']; ?></td>
                <td><?php echo $d['nilai_rujukan']; ?></td>
                <td><?php echo $d['keterangan']; ?></td>
				<td>
					<a href="edit.php?no_rawat=<?php echo $d['no_rawat']; ?>">EDIT</a>
					<a href="hapus.php?no_rawat=<?php echo $d['no_rawat']; ?>">HAPUS</a>
				</td>
			</tr>
			<?php 
		}
		?>
	</table>
</body>
</html>