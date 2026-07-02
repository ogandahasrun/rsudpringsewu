<?php
$c = mysqli_connect("localhost","bpjsfktl","bpjsfktl","sikbaru");
if (!$c) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
$q = mysqli_query($c, "DESCRIBE user");
$columns = [];
while ($row = mysqli_fetch_assoc($q)) {
    if (stripos($row['Field'], 'cuti') !== false) {
        $columns[] = $row['Field'];
    }
}
echo "User table columns containing 'cuti':\n";
print_r($columns);
?>
