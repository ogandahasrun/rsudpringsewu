# Rangkuman: Halaman Verifikasi Resep Dokter

## Sumber Kode Asli (Java)
- **File**: [`InventoryTelaahResep.java`](file:///d:/SIMRS-Khanza/src/inventory/InventoryTelaahResep.java)
- **Package**: `inventory`
- **Posisi**: Tab ke-2 dalam dialog "Telaah Resep & Obat" (tab pertama = Telaah Resep & Obat, tab kedua = Verifikasi Resep Dokter)
- **Method utama**: `tampil2()` — [baris 1822–2136](file:///d:/SIMRS-Khanza/src/inventory/InventoryTelaahResep.java#L1822-L2136)

---

## Fungsi Halaman

Halaman ini menampilkan **perbandingan side-by-side** antara:
1. **"Obat Yang Diresepkan"** — obat yang ditulis dokter dalam resep
2. **"Obat Yang Diberikan"** — obat yang divalidasi/diserahkan oleh bagian farmasi

Tujuannya agar petugas farmasi dapat **memverifikasi** apakah obat yang diberikan sudah sesuai dengan resep dokter — baik jenis obat, jumlah, satuan, maupun aturan pakai.

---

## Relasi Pencocokan (Kunci Penting)

Tabel `detail_pemberian_obat` **TIDAK memiliki kolom `no_resep`**. Pencocokan antara resep dokter dan pemberian obat farmasi menggunakan **3 kolom komposit**:

```
resep_obat.no_rawat        ←→  detail_pemberian_obat.no_rawat
resep_obat.tgl_perawatan   ←→  detail_pemberian_obat.tgl_perawatan
resep_obat.jam             ←→  detail_pemberian_obat.jam
```

### Mekanisme Sinkronisasi

Saat farmasi memvalidasi/memberikan obat (di [`DlgCariObat2.java`](file:///d:/SIMRS-Khanza/src/inventory/DlgCariObat2.java)):
1. Detail obat disimpan ke `detail_pemberian_obat` dengan `tgl_perawatan` dan `jam` **saat pemberian**
2. Kolom `resep_obat.tgl_perawatan` dan `resep_obat.jam` di-**update** dengan tanggal & jam yang sama

Sehingga `resep_obat.tgl_perawatan` + `resep_obat.jam` **bukan** tanggal resep dibuat (`tgl_peresepan` + `jam_peresepan`), melainkan **tanggal & jam saat farmasi memproses resep tersebut**.

> [!WARNING]
> **Risiko**: Jika 2 resep berbeda (misal rawat inap) diproses pada waktu yang persis sama (hingga detik), data "Obat Yang Diberikan" bisa tercampur. Desain ini mengasumsikan **satu kombinasi `no_rawat + tgl_perawatan + jam` hanya untuk satu resep**.

---

## Struktur Tabel yang Terlibat

### 1. `resep_obat` — Header resep (dari dokter)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_resep` | varchar(14) | **PK** — Nomor resep |
| `tgl_perawatan` | date | Tanggal pemberian obat (di-update saat farmasi memproses) |
| `jam` | time | Jam pemberian obat (di-update saat farmasi memproses) |
| `no_rawat` | varchar(17) | FK ke `reg_periksa` |
| `kd_dokter` | varchar(20) | FK ke `dokter` |
| `tgl_peresepan` | date | Tanggal resep dibuat oleh dokter |
| `jam_peresepan` | time | Jam resep dibuat oleh dokter |
| `status` | enum('ralan','ranap') | Status rawat jalan/inap |
| `tgl_penyerahan` | date | Tanggal penyerahan obat |
| `jam_penyerahan` | time | Jam penyerahan obat |

### 2. `resep_dokter` — Detail obat non-racikan yang diresepkan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_resep` | varchar | FK ke `resep_obat` |
| `kode_brng` | varchar | FK ke `databarang` |
| `jml` | double | Jumlah obat diresepkan |
| `aturan_pakai` | varchar | Aturan pakai (signa) |

### 3. `resep_dokter_racikan` — Header racikan yang diresepkan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_resep` | varchar | FK ke `resep_obat` |
| `no_racik` | varchar | Nomor racikan |
| `nama_racik` | varchar | Nama racikan |
| `kd_racik` | varchar | FK ke `metode_racik` |
| `jml_dr` | double | Jumlah dari dokter |
| `aturan_pakai` | varchar | Aturan pakai racikan |
| `keterangan` | varchar | Keterangan tambahan |

### 4. `resep_dokter_racikan_detail` — Detail item per racikan yang diresepkan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_resep` | varchar | FK ke `resep_obat` |
| `no_racik` | varchar | Nomor racikan |
| `kode_brng` | varchar | FK ke `databarang` |
| `jml` | double | Jumlah obat dalam racikan |

### 5. `detail_pemberian_obat` — Obat non-racikan yang diberikan farmasi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `tgl_perawatan` | date | **PK komposit** |
| `jam` | time | **PK komposit** |
| `no_rawat` | varchar(17) | **PK komposit**, FK ke `reg_periksa` |
| `kode_brng` | varchar(15) | **PK komposit**, FK ke `databarang` |
| `h_beli` | double | Harga beli |
| `biaya_obat` | double | Biaya obat |
| `jml` | double | Jumlah diberikan |
| `embalase` | double | Biaya embalase |
| `tuslah` | double | Biaya tuslah |
| `total` | double | Total biaya |
| `status` | enum('Ralan','Ranap') | Status rawat |
| `kd_bangsal` | char(5) | FK ke `bangsal` |
| `no_batch` | varchar(20) | **PK komposit** |
| `no_faktur` | varchar(20) | **PK komposit** |

### 6. `obat_racikan` — Header racikan yang diberikan farmasi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `tgl_perawatan` | date | Relasi ke `detail_pemberian_obat` |
| `jam` | time | Relasi ke `detail_pemberian_obat` |
| `no_rawat` | varchar | Relasi ke `detail_pemberian_obat` |
| `no_racik` | varchar | Nomor racikan |
| `nama_racik` | varchar | Nama racikan |
| `kd_racik` | varchar | FK ke `metode_racik` |
| `jml_dr` | double | Jumlah dari dokter |
| `aturan_pakai` | varchar | Aturan pakai |
| `keterangan` | varchar | Keterangan |

### 7. `detail_obat_racikan` — Detail item per racikan yang diberikan farmasi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `tgl_perawatan` | date | Relasi |
| `jam` | time | Relasi |
| `no_rawat` | varchar | Relasi |
| `no_racik` | varchar | Nomor racikan |
| `kode_brng` | varchar | FK ke `databarang` |

### 8. `aturan_pakai` — Aturan pakai obat yang diberikan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `tgl_perawatan` | date | Relasi |
| `jam` | time | Relasi |
| `no_rawat` | varchar | Relasi |
| `kode_brng` | varchar | Kode barang |
| `aturan` | varchar | Teks aturan pakai |

### Tabel Pendukung (Master)

| Tabel | Fungsi |
|---|---|
| `reg_periksa` | Data registrasi periksa (`no_rawat` → `no_rkm_medis`) |
| `pasien` | Data pasien (`no_rkm_medis`, `nm_pasien`) |
| `dokter` | Data dokter (`kd_dokter`, `nm_dokter`) |
| `databarang` | Master obat/barang (`kode_brng`, `nama_brng`, `kode_sat`) |
| `metode_racik` | Master metode racikan (`kd_racik`, `nm_racik`) |

---

## Alur Query (Urutan Tampilan)

### Query 1 — Daftar Resep (Header)

```sql
SELECT resep_obat.no_resep, resep_obat.tgl_peresepan, resep_obat.jam_peresepan,
       resep_obat.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien,
       resep_obat.kd_dokter, dokter.nm_dokter, resep_obat.status,
       resep_obat.tgl_perawatan, resep_obat.jam
FROM resep_obat
  INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
  INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
  INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
WHERE resep_obat.tgl_peresepan <> '0000-00-00'
  AND resep_obat.tgl_perawatan <> '0000-00-00'
  AND resep_obat.tgl_peresepan BETWEEN :tgl1 AND :tgl2
  -- filter pencarian opsional
ORDER BY resep_obat.tgl_perawatan, resep_obat.jam DESC
```

> [!NOTE]
> Filter `tgl_perawatan <> '0000-00-00'` memastikan hanya resep yang **sudah diproses farmasi** yang ditampilkan (karena `tgl_perawatan` di-update saat farmasi memproses).

**Kolom yang ditampilkan**: No.Resep, Tgl.Resep, Jam Resep, No.Rawat, No.RM, Pasien, Kode Dokter, Dokter Peresep, Status

---

### Untuk setiap resep, tampilkan 2 blok:

### Query 2a — Obat Yang Diresepkan (Non-Racikan)

```sql
SELECT databarang.kode_brng, databarang.nama_brng, resep_dokter.jml,
       databarang.kode_sat, resep_dokter.aturan_pakai
FROM resep_dokter
  INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
WHERE resep_dokter.no_resep = :no_resep
ORDER BY databarang.kode_brng
```

### Query 2b — Obat Yang Diresepkan (Racikan — Header)

```sql
SELECT resep_dokter_racikan.no_racik, resep_dokter_racikan.nama_racik,
       resep_dokter_racikan.kd_racik, metode_racik.nm_racik AS metode,
       resep_dokter_racikan.jml_dr, resep_dokter_racikan.aturan_pakai,
       resep_dokter_racikan.keterangan
FROM resep_dokter_racikan
  INNER JOIN metode_racik ON resep_dokter_racikan.kd_racik = metode_racik.kd_racik
WHERE resep_dokter_racikan.no_resep = :no_resep
```

### Query 2c — Obat Yang Diresepkan (Racikan — Detail per Racik)

```sql
SELECT databarang.kode_brng, databarang.nama_brng,
       resep_dokter_racikan_detail.jml, databarang.kode_sat
FROM resep_dokter_racikan_detail
  INNER JOIN databarang ON resep_dokter_racikan_detail.kode_brng = databarang.kode_brng
WHERE resep_dokter_racikan_detail.no_resep = :no_resep
  AND resep_dokter_racikan_detail.no_racik = :no_racik
ORDER BY databarang.kode_brng
```

---

### Query 3a — Obat Yang Diberikan (Non-Racikan)

```sql
SELECT databarang.kode_brng, databarang.nama_brng,
       detail_pemberian_obat.jml, databarang.kode_sat,
       detail_pemberian_obat.biaya_obat, detail_pemberian_obat.embalase,
       detail_pemberian_obat.tuslah, detail_pemberian_obat.total
FROM detail_pemberian_obat
  INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
WHERE detail_pemberian_obat.tgl_perawatan = :tgl_perawatan
  AND detail_pemberian_obat.jam = :jam
  AND detail_pemberian_obat.no_rawat = :no_rawat
  AND databarang.kode_brng NOT IN (
    SELECT detail_obat_racikan.kode_brng
    FROM detail_obat_racikan
    WHERE detail_obat_racikan.tgl_perawatan = :tgl_perawatan
      AND detail_obat_racikan.jam = :jam
      AND detail_obat_racikan.no_rawat = :no_rawat
  )
ORDER BY databarang.kode_brng
```

> [!IMPORTANT]
> Subquery `NOT IN` digunakan untuk **mengecualikan obat yang sudah masuk sebagai bagian racikan**, agar tidak double-count.

**Aturan pakai** untuk obat non-racikan yang diberikan diambil terpisah:

```sql
SELECT aturan_pakai.aturan
FROM aturan_pakai
WHERE aturan_pakai.tgl_perawatan = :tgl_perawatan
  AND aturan_pakai.jam = :jam
  AND aturan_pakai.no_rawat = :no_rawat
  AND aturan_pakai.kode_brng = :kode_brng
```

### Query 3b — Obat Yang Diberikan (Racikan — Header)

```sql
SELECT obat_racikan.no_racik, obat_racikan.nama_racik,
       obat_racikan.kd_racik, metode_racik.nm_racik AS metode,
       obat_racikan.jml_dr, obat_racikan.aturan_pakai,
       obat_racikan.keterangan
FROM obat_racikan
  INNER JOIN metode_racik ON obat_racikan.kd_racik = metode_racik.kd_racik
WHERE obat_racikan.tgl_perawatan = :tgl_perawatan
  AND obat_racikan.jam = :jam
  AND obat_racikan.no_rawat = :no_rawat
```

### Query 3c — Obat Yang Diberikan (Racikan — Detail per Racik)

```sql
SELECT databarang.kode_brng, databarang.nama_brng,
       detail_pemberian_obat.jml, databarang.kode_sat,
       detail_pemberian_obat.biaya_obat, detail_pemberian_obat.embalase,
       detail_pemberian_obat.tuslah, detail_pemberian_obat.total
FROM detail_pemberian_obat
  INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
  INNER JOIN detail_obat_racikan
    ON detail_pemberian_obat.kode_brng = detail_obat_racikan.kode_brng
    AND detail_pemberian_obat.tgl_perawatan = detail_obat_racikan.tgl_perawatan
    AND detail_pemberian_obat.jam = detail_obat_racikan.jam
    AND detail_pemberian_obat.no_rawat = detail_obat_racikan.no_rawat
WHERE detail_pemberian_obat.tgl_perawatan = :tgl_perawatan
  AND detail_pemberian_obat.jam = :jam
  AND detail_pemberian_obat.no_rawat = :no_rawat
  AND detail_obat_racikan.no_racik = :no_racik
ORDER BY databarang.kode_brng
```

---

## Struktur Tampilan HTML

Untuk setiap resep, output HTML-nya disusun:

```
┌──────────────────────────────────────────────────────────┐
│ No.Resep | Tgl.Resep | Jam | No.Rawat | No.RM | Pasien  │
│ Kode Dokter | Dokter Peresep | Status                   │
├──────────────────────────────────────────────────────────┤
│ Obat Yang Diresepkan :                                   │
│ ┌──────┬────────┬─────────────┬─────────┬──────────────┐ │
│ │Jumlah│ Satuan │ Aturan Pakai│ Kode/No │ Nama Obat    │ │
│ ├──────┼────────┼─────────────┼─────────┼──────────────┤ │
│ │  10  │  Tab   │ 3x1         │ B00001  │ Amoxicillin  │ │
│ │  ...obat non-racikan...                               │ │
│ │  5   │ Kapsul │ 2x1         │No.Racik:│ Racikan ABC  │ │
│ │    → detail item racikan (indented)                   │ │
│ └──────┴────────┴─────────────┴─────────┴──────────────┘ │
├──────────────────────────────────────────────────────────┤
│ Obat Yang Diberikan :                                    │
│ ┌──────┬────────┬─────────────┬─────────┬──────────────┐ │
│ │Jumlah│ Satuan │ Aturan Pakai│ Kode/No │ Nama Obat    │ │
│ ├──────┼────────┼─────────────┼─────────┼──────────────┤ │
│ │  10  │  Tab   │ 3x1         │ B00001  │ Amoxicillin  │ │
│ │  ...obat non-racikan (excl. racikan)...               │ │
│ │  5   │ Kapsul │ 2x1         │No.Racik:│ Racikan ABC  │ │
│ │    → detail item racikan (indented)                   │ │
│ └──────┴────────┴─────────────┴─────────┴──────────────┘ │
└──────────────────────────────────────────────────────────┘
```

---

## Filter & Pencarian

- **Filter tanggal**: Berdasarkan `resep_obat.tgl_peresepan` (tanggal resep dibuat)
- **Pencarian teks** (opsional): Mencari di `no_resep`, `no_rawat`, `no_rkm_medis`, `nm_pasien`, `kd_dokter`, `nm_dokter`, `status`

---

## Catatan untuk Implementasi PHP

1. **Koneksi database**: Gunakan PDO/MySQLi dengan prepared statements
2. **Output**: Bisa menggunakan HTML table langsung (seperti versi Java) atau JSON untuk AJAX
3. **Kolom tampilan**: 5 kolom per blok obat: Jumlah, Satuan, Aturan Pakai, Kode/No, Nama Obat/Racikan
4. **Racikan**: Tampilkan header racikan → di bawahnya detail item racikan (indent)
5. **Perhatikan** subquery `NOT IN` pada Query 3a untuk memisahkan obat non-racikan dari obat racikan
6. **Status resep**: Kolom `status` di `resep_obat` ditampilkan dengan huruf "r" diganti "R" (capitalize)
