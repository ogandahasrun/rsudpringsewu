## 📁 BATCH RENAME FILE - DOKUMENTASI LENGKAP

### 🎯 Fitur Aplikasi
Aplikasi ini memungkinkan Anda untuk me-rename hingga 2000 file sekaligus dari data yang ada di file Excel atau CSV.

**Fitur Utama:**
- ✅ Upload file Excel (.xlsx, .xls) atau CSV
- ✅ Preview mapping sebelum rename
- ✅ Validasi file sebelum proses rename
- ✅ Batch rename massal (hingga 2000+ file)
- ✅ Report lengkap dengan detail setiap file
- ✅ Download log (.txt) untuk dokumentasi
- ✅ Error handling yang robust

---

## 📋 CARA PENGGUNAAN LANGKAH DEMI LANGKAH

### LANGKAH 1: Persiapkan File Excel/CSV

**Format File Excel:**
```
Kolom A (Nama Lama)        | Kolom B (Nama Baru)
-------------------------------------------
RESEP2-2026010001.pdf      | 001_UMI_0807R006V0126000001.pdf
RESEP2-2026010002.pdf      | 002_YENI_0807R006V0126000909.pdf
RESEP2-2026010003.pdf      | 008_BUDI_0807R006V1225000024.pdf
RESEP2-2026010004.pdf      | 003_SITI_0807R006V0908000012.pdf
...dst
```

**Catatan:**
- Baris pertama boleh berisi header (akan otomatis diabaikan jika kosong kolom A/B)
- Minimal 2 kolom (Kolom A = nama lama, Kolom B = nama baru)
- File maksimal 10MB
- Bisa menggunakan Excel (.xlsx/.xls) atau CSV

### LANGKAH 2: Akses Aplikasi
Buka browser dan go ke:
```
http://localhost/rsudpringsewu/batch_rename_form.php
```

### LANGKAH 3: Upload File & Tentukan Folder Sumber
1. **Upload File Excel/CSV** - klik "Choose File" dan pilih file Anda
2. **Masukkan Path Folder Sumber** - contoh:
   - Windows: `D:\APOL` atau `C:\xampp\htdocs\rsudpringsewu\files`
   - Pastikan path folder sudah benar dan file-file tersimpan di folder itu
3. **Sheet Name** (opsional) - jika Excel punya multiple sheets, sebutkan nama sheet yang digunakan
4. Klik **"Preview Mapping"**

### LANGKAH 4: Review Preview
- Halaman akan menampilkan:
  - **Statistik**: Total file, file yang ada, file yang hilang
  - **Tabel Detail**: Setiap mapping (nama lama → nama baru) + status validasi
  - **Warna Status**:
    - 🟢 **Valid** = File ditemukan, siap direname
    - 🟡 **Warning** = Ada issue minor (duplikasi nama baru)
    - 🔴 **Error** = File tidak ditemukan

- **PENTING**: Pastikan semua file ada (tidak ada error merah) sebelum melanjutkan
- Jika ada file hilang, kembali & cek:
  1. Path folder sudah benar?
  2. File-file sudah ada di folder tersebut?
  3. Nama file lama di Excel sesuai dengan nama file di folder?

### LANGKAH 5: Rename Sekarang!
Jika preview OK, klik tombol **"Rename Sekarang"** untuk melakukan rename massal.

⚠️ **PENTING:**
- Tunggu hingga selesai (jangan tutup browser)
- Untuk 2000 file, mungkin butuh 30-60 detik
- Browser akan menampilkan progress otomatis

### LANGKAH 6: Lihat Report & Download Log
Setelah rename selesai:
1. Halaman akan menampilkan **statistik peringkas**:
   - Total file
   - Berhasil direname
   - Gagal direname
   - Success rate (%)

2. Lihat **tabel detail** untuk setiap file
3. Klik **"Download Log Text"** untuk download laporan (.txt) sebagai dokumentasi

---

## 🚨 TROUBLESHOOTING

### ❌ Masalah: "File tidak ditemukan" saat Preview
**Solusi:**
1. Pastikan path folder sudah benar (cek di File Explorer)
2. Pastikan file-file sudah benar ada di folder
3. Pastikan nama file lama di Excel sesuai dengan nama file di folder (case-sensitive)
4. Jika folder punya spasi, gunakan quote: `"D:\My Folder\APOL"`

### ❌ Masalah: Upload file gagal
**Solusi:**
1. Pastikan file tidak lebih dari 10MB
2. Gunakan format Excel (.xlsx/.xls) atau CSV
3. Pastikan file tidak corrupt
4. Coba buka ulang file Excel di LibreOffice Calc atau Excel, save, lalu upload lagi

### ❌ Masalah: Beberapa file gagal direname (error saat execute)
**Kemungkinan penyebab:**
1. **Permission denied** - File sedang dibuka di aplikasi lain (close dulu)
2. **Nama file baru sudah ada** - Ada file dengan nama baru yang sama
3. **Path folder tidak valid saat execute** - Folder mungkin dipindah/dihapus

**Solusi:**
1. Close aplikasi yang membuka file (Explorer, Adobe, dll)
2. Pastikan disk C/D tidak penuh
3. Coba run aplikasi sebagai Administrator
4. Rename manual file yang gagal, lalu retry dengan file lain

### ❌ Masalah: Browser hang/freeze saat rename
**Solusi:**
1. Tunggu lebih lama (untuk 2000 file bisa 1-2 menit)
2. Tidak ada fix cepat - tunggu proses selesai di background
3. Jika benar-benar hang (5+ menit), bisa refresh/close tab

---

## 📊 CONTOH OUTPUT REPORT

```
=====================================
BATCH RENAME REPORT
=====================================
Waktu: 2026-03-25 14:30:45
Durasi: 12.54s
Folder: D:\APOL

SUMMARY:
- Total File: 3
- Berhasil: 3
- Gagal: 0
- Success Rate: 100.00%

=====================================
DETAIL HASIL:
=====================================

Row #1
  Status: [SUCCESS]
  Dari: RESEP2-2026010001.pdf
  Ke:  001_UMI_0807R006V0126000001.pdf
  Info: Berhasil direname

Row #2
  Status: [SUCCESS]
  Dari: RESEP2-2026010002.pdf
  Ke:  002_YENI_0807R006V0126000909.pdf
  Info: Berhasil direname

Row #3
  Status: [SUCCESS]
  Dari: RESEP2-2026010003.pdf
  Ke:  008_BUDI_0807R006V1225000024.pdf
  Info: Berhasil direname
```

---

## 💡 TIPS & BEST PRACTICES

1. **Backup terlebih dahulu** - Sebelum rename 2000 file, buat backup folder
2. **Test dengan file kecil dulu** - Rename 5-10 file lebih dulu untuk test
3. **Perhatikan format nama** - Pastikan nama baru tidak ada karakter invalid (`< > : " / \ | ? *`)
4. **Gunakan Excel, bukan CSV** - Lebih mudah edit di Excel vs Notepad
5. **Nama file unik** - Pastikan nama baru tidak ada duplikat
6. **Download log setelah rename** - Simpan sebagai bukti perubahan

---

## 📁 FILE-FILE YANG DIBUAT

```
c:\xampp\htdocs\rsudpringsewu\
├── batch_rename_form.php          # Halaman utama (upload file)
├── batch_rename_preview.php       # Halaman preview & validasi
├── batch_rename_execute.php       # Script rename & report
├── batch_rename_download_log.php  # Script download log
├── batch_rename_reports/          # Folder menyimpan log (auto-created)
│   ├── report_20260325_143045.txt
│   ├── report_20260325_144230.txt
│   └── ...
└── README_BATCH_RENAME.md         # File ini
```

---

## 🔒 KEAMANAN

- ✅ File upload di-validate (extension, size)
- ✅ Path folder di-validate (existence check)
- ✅ File yang di-rename di-validate (exist check sebelum rename)
- ✅ Download log di-restrict (hanya file dengan pattern tertentu)
- ✅ Semua input HTML-escaped untuk prevent XSS
- ✅ Permission checks sebelum rename

---

## 📞 SUPPORT

Jika ada masalah:
1. Check file log di folder `batch_rename_reports/` untuk detail error
2. Pastikan semua requirement sudah terpenuhi
3. Test dengan 5-10 file dulu sebelum 2000 file
4. Contact admin jika masih ada issue

---

**Dibuat: 25 Maret 2026**
**Version: 1.0**
**Kompatibel: PHP 5.6+**
