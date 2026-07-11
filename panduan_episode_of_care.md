# Panduan Pengisian & Pengiriman EpisodeOfCare (EOC) SATUSEHAT

Dokumen ini menjelaskan kegunaan, cara pengisian field form, serta alur/urutan status (lifecycle) dari resource **EpisodeOfCare** di platform SATUSEHAT Kementerian Kesehatan RI.

---

## 1. Kegunaan EpisodeOfCare (EOC)
Dalam standar FHIR R4 SATUSEHAT, **EpisodeOfCare** bertindak sebagai "wadah besar" yang mengelompokkan berbagai aktivitas pelayanan kesehatan (kunjungan/kunjungan rumah, pemeriksaan lab, resep obat) yang terkait dengan satu kondisi medis tertentu dalam jangka waktu tertentu.

EOC sangat penting dan **wajib** digunakan pada kasus pelayanan berjangka panjang, seperti:
- **Antenatal Care (ANC / Masa Kehamilan)**: Menghubungkan seluruh rangkaian kontrol ibu hamil (K1 s.d K6).
- **Pengobatan Tuberkulosis (TB) & HIV**: Melacak kepatuhan konsumsi obat selama berbulan-bulan.
- **Registrasi & Perawatan Kanker**: Memantau siklus kemoterapi dan kontrol onkologi.

---

## 2. Deskripsi Field Form & Aturan Pengisian

| Field di Form | Format/Aturan FHIR | Penjelasan & Cara Pengisian |
| :--- | :--- | :--- |
| **Cari ID Pasien via NIK** | 16 Digit Angka | Masukkan NIK pasien untuk lookup otomatis ke database Satu Sehat nasional. |
| **ID Pasien (Satu Sehat UUID)** | UUID Kemenkes (`Patient/id`) | Nomor IHS Pasien. Terisi otomatis jika pencarian NIK berhasil. |
| **Nama Pasien** | String Text | Nama lengkap pasien sesuai KTP. Terisi otomatis jika pencarian NIK berhasil. |
| **Status Episode** | Dropdown Enum | Status aktif dari periode perawatan tersebut (lihat bagian alur status). |
| **ID Episode SIMRS (Identifier)** | String Unik | Kode internal episode perawatan dari SIMRS Anda (misal: `EOC-ANC-2026-0711-0012`). Harus unik untuk tiap program perawatan pasien. |
| **Waktu Mulai Episode** | ISO 8601 Datetime | Waktu program perawatan pertama kali dimulai (contoh: `2026-07-11T08:00:00+07:00`). |
| **Waktu Selesai (Opsional)** | ISO 8601 Datetime | Diisi **hanya jika** status diubah ke `finished` atau `cancelled`. Wajib kosong selama status masih `active`. |
| **Nomor Rawat (Bridging SIMRS)** | Varchar (SIMRS ID) | Nomor rawat/registrasi pasien. Jika diisi, UUID EOC sukses terkirim akan disimpan otomatis ke tabel database lokal `satu_sehat_episodeofcare`. |

---

## 3. Alur & Urutan Status EpisodeOfCare (Lifecycle)

Urutan status EpisodeOfCare menggambarkan perjalanan klinis pasien dari pendaftaran hingga selesai program perawatan:

```
[ planned ] ──> [ waitlist ] ──> [ active ] ──> [ finished ]
     │                                │ 
     │                                └──> [ onhold ] ──> [ active ]
     │                                
     └─────────────────────────> [ cancelled ]
```

### Penjelasan Status:
1. **`planned` (Direncanakan)**: Episode telah dibuat di sistem tetapi pelayanan aktif belum dimulai (misal: rujukan masuk atau penjadwalan awal).
2. **`waitlist` (Daftar Tunggu)**: Pasien terdaftar dalam program perawatan tetapi sedang mengantre alokasi fasilitas/tempat tidur.
3. **`active` (Sedang Berjalan)**: Rangkaian pengobatan/pemeriksaan sedang berlangsung. Pada tahap ini, UUID EpisodeOfCare wajib disematkan dalam setiap pengiriman data kunjungan (`Encounter.episodeOfCare.reference`).
4. **`onhold` (Ditangguhkan)**: Pengobatan dihentikan sementara (misal: pasien drop sehingga kemoterapi ditunda, atau pasien pergi ke luar kota). Status dapat diaktifkan kembali (`active`) setelah pasien kembali berobat.
5. **`finished` (Selesai)**: Program perawatan telah berakhir secara permanen (misal: pasien TB dinyatakan sembuh total, atau ibu hamil telah melahirkan). Pada status ini, **Waktu Selesai** wajib diinput.
6. **`cancelled` (Dibatalkan)**: Episode dibatalkan di tengah jalan sebelum selesai (misal: pasien menolak pengobatan, atau dirujuk keluar ke rumah sakit lain).

---

## 4. Contoh Skenario Penggunaan (ANC / Ibu Hamil)

1. **Pemeriksaan Kehamilan Ke-1 (ANC 1)**:
   - Fasyankes membuat `EpisodeOfCare` baru dengan status `active` dan waktu mulai pemeriksaan pertama.
   - Kirim ke SATUSEHAT -> dapatkan UUID EOC (misal: `abc-123-xyz`). Simpan UUID ini di database lokal SIMRS.
   - Kirim data kunjungan (`Encounter`) ke-1 dengan mereferensikan `EpisodeOfCare/abc-123-xyz`.
2. **Pemeriksaan Kehamilan Ke-2 s.d Ke-6**:
   - SIMRS cukup mengirimkan data kunjungan (`Encounter`) baru dan mereferensikan UUID EOC yang sama (`abc-123-xyz`). *Jangan membuat EpisodeOfCare baru lagi.*
3. **Melahirkan / Nifas Selesai**:
   - Setelah masa nifas berakhir, update status `EpisodeOfCare` tersebut ke `finished` dan masukkan waktu berakhirnya, lalu kirim update ke SATUSEHAT.
