# ♻️ PlasticPoint - Reverse Vending Machine (RVM) Web Dashboard & API

Sistem berbasis web dan API terintegrasi yang berfungsi sebagai pusat kendali (*command center*), pemantauan (*real-time monitoring*), dan manajemen sistem untuk alat **Reverse Vending Machine (RVM)** penukar sampah plastik menjadi poin[cite: 1, 2, 4]. Proyek ini dirancang untuk menghubungkan perangkat keras (seperti ESP32 dan sensor pemindai) dengan antarmuka web responsif (desktop & mobile).

---

## 📌 Deskripsi Proyek

PlasticPoint dikembangkan untuk mendukung otomatisasi pengelolaan sampah plastik jenis **PET** dan **PP**[cite: 1, 4]. Pengguna dapat menyetorkan sampah botol plastik ke mesin RVM, yang kemudian diproses melalui sistem klasifikasi berbasis sensor/CNN[cite: 2], dan hasilnya dikonversi secara otomatis menjadi poin akun pengguna[cite: 1, 4]. Administrator dapat memantau seluruh aktivitas perangkat, riwayat transaksi, serta mengelola data pengguna melalui *dashboard* web yang interaktif[cite: 1, 2, 3, 4].

---

## ⚙️ Fitur Utama

- **Dashboard Admin & Statistik:** Memantau ringkasan total pengguna, total transaksi, total sampah terkumpul, serta grafik distribusi jenis plastik (PET & PP) secara visual[cite: 1].
- **Monitoring Sensor Real-Time:** Memantau status koneksi perangkat, sensor infrared, status servo, hasil klasifikasi CNN, hingga *confidence score* secara langsung (*live update*)[cite: 2].
- **Manajemen Pengguna (Admin & User):** Pengelolaan akun lengkap dengan fitur tambah, edit, dan hapus data pengguna/admin[cite: 3].
- **Sistem Transaksi & Input Manual:** Pencatatan riwayat setor sampah secara otomatis via API dari perangkat RVM atau input manual oleh admin[cite: 4].
- **Laporan & Ekspor Data:** Fitur filter transaksi berdasarkan rentang tanggal serta unduh laporan dalam format **Excel (.xlsx)** dan **PDF**[cite: 5].
- **Autentikasi & Sesi Aman:** Sistem *login*, *register*, *logout*, dan pemeriksaan sesi (*session checking*) yang terstruktur untuk melindungi halaman hak akses khusus[cite: 11, 12].
- **Antarmuka Responsif (Mobile-Friendly):** Desain tata letak yang optimal untuk diakses melalui perangkat bergerak maupun komputer.

---

## 📁 Struktur Repositori & Penjelasan Folder

```text
├── admin/          # Halaman khusus admin (Dashboard, Monitoring, Laporan, Kelola User & Transaksi)[cite: 8]
├── api/            # Endpoint backend untuk komunikasi data dengan ESP32 / perangkat RVM & sesi[cite: 9]
├── assets/         # Berisi file pendukung tampilan seperti CSS (admin & user)[cite: 10]
├── auth/           # Modul otentikasi (Login, Register, Logout, & Cek Sesi)[cite: 11]
├── config/         # Konfigurasi koneksi database (`db.php`)[cite: 12]
├── includes/       # Komponen tata letak berulang (Navbar & Sidebar untuk Admin/User)[cite: 13]
├── user/           # Halaman khusus pengguna / mobile interface (Dashboard, Profil, Riwayat)[cite: 14]
├── index.php       # Halaman utama / gerbang masuk aplikasi[cite: 7]
└── README.md       # Dokumentasi resmi proyek
