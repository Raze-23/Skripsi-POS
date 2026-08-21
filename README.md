<div align="center">

<img src="amikom.png" width="70" alt="Universitas Amikom"/>&nbsp;&nbsp;&nbsp;×&nbsp;&nbsp;&nbsp;<img src="logo-attiin.png" width="70" alt="CV. Herbal Attiin"/>

<sub>STMIK Amikom Surakarta × CV. Herbal Attiin</sub>

<br/>
<br/>

# 🌿 Sistem Point of Sale — CV. Herbal Attiin

Aplikasi POS berbasis web untuk digitalisasi operasional bisnis herbal tradisional Indonesia

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production-brightgreen?style=flat-square)

</div>

---

## Tentang Project

**Sistem Point of Sale (POS) CV. Herbal Attiin** adalah aplikasi web yang dikembangkan sebagai Tugas Akhir program studi di **STMIK Amikom Surakarta**. Proyek ini mendigitalisasi alur bisnis **CV. Herbal Attiin** — usaha produk herbal tradisional — mulai dari pencatatan transaksi, monitoring stok, hingga pelaporan penjualan.

## Fitur Utama

**Manajemen Inventaris**
- CRUD produk, kategori, dan satuan
- Pemantauan stok real-time dengan notifikasi stok minimum
- Peringatan otomatis produk mendekati kedaluwarsa
- Riwayat perubahan stok (stock log)

**Transaksi Kasir (POS)**
- Input produk ke keranjang otomatis via pemindaian QR Code
- Pencarian produk cepat dengan barcode / nama
- Kalkulasi kembalian otomatis, dukungan tunai & transfer
- Cetak struk langsung atau simpan sebagai PDF

**Role-Based Access Control**
- Admin — akses penuh (produk, laporan, manajemen pengguna)
- Kasir — akses terbatas (transaksi & cetak struk)
- Middleware proteksi rute berbasis role

**Pelaporan & Analitik**
- Laporan penjualan harian, bulanan, tahunan
- Grafik tren penjualan interaktif
- Ekspor laporan ke PDF dan Excel (.xlsx)
- Filter berdasarkan rentang tanggal & kategori produk

**Manajemen Transaksi**
- Riwayat transaksi lengkap dengan status
- Detail per-transaksi dengan breakdown item
- Fitur batalkan transaksi & cetak ulang struk

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.2+, Laravel 11.x |
| Frontend | Blade Templates, Tailwind CSS 3.x |
| Build Tool | Vite |
| Database | MySQL 8.0 |
| Testing | PHPUnit |
| Version Control | Git & GitHub |
