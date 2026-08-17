<div align="center">

<table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center" width="140">
      <img src="amikom.png" width="80" alt="Universitas Amikom"/>
      <br/>
      <sub><b>STMIK Amikom Surakarta</b></sub>
    </td>
    <td align="center" width="40">
      <sub>×</sub>
    </td>
    <td align="center" width="140">
      <img src="logo-attiin.png" width="80" alt="CV. Herbal Attiin"/>
      <br/>
      <sub><b>CV. Herbal Attiin</b></sub>
    </td>
  </tr>
</table>

# 🌿 Sistem Point of Sale — CV. Herbal Attiin

*Aplikasi POS berbasis web untuk digitalisasi operasional bisnis herbal tradisional Indonesia*

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-Academic-green?style=flat-square)]()
[![Status](https://img.shields.io/badge/Status-In_Development-yellow?style=flat-square)]()

</div>

---

## 📌 Tentang Project

**Sistem Point of Sale (POS) CV. Herbal Attiin** adalah aplikasi web yang dikembangkan sebagai Tugas Akhir program studi di **Universitas Amikom**. Proyek ini bertujuan mendigitalisasi dan mengoptimalkan alur bisnis **CV. Herbal Attiin** — sebuah usaha produk herbal tradisional — yang sebelumnya masih berjalan secara konvensional, mencakup pencatatan transaksi, monitoring stok, hingga pelaporan penjualan.

---

## ✨ Fitur Utama

<details>
<summary><b>📦 Manajemen Inventaris</b></summary>
<br/>

- CRUD lengkap untuk produk, kategori, dan satuan
- Pemantauan stok secara *real-time*
- Notifikasi otomatis saat stok mendekati batas minimum
- Peringatan otomatis untuk produk yang **mendekati masa kedaluwarsa**
- Riwayat perubahan stok (stock log)

</details>

<details>
<summary><b>🛒 Transaksi Kasir (Point of Sale)</b></summary>
<br/>

- Antarmuka kasir yang responsif dan intuitif
- **Input produk ke keranjang secara otomatis melalui pemindaian QR Code**
- Pencarian produk cepat dengan barcode / nama
- Kalkulasi kembalian otomatis
- Dukungan multiple metode pembayaran (tunai, transfer)
- Cetak struk langsung atau simpan sebagai PDF

</details>

<details>
<summary><b>👥 Role-Based Access Control (RBAC)</b></summary>
<br/>

- **Admin** — akses penuh: produk, laporan, manajemen pengguna
- **Kasir** — akses terbatas: transaksi & cetak struk
- Middleware proteksi rute berbasis role

</details>

<details>
<summary><b>📊 Pelaporan & Analitik</b></summary>
<br/>

- Laporan penjualan: harian, bulanan, tahunan
- Grafik tren penjualan interaktif
- Ekspor laporan ke PDF dan Excel (`.xlsx`)
- Filter laporan berdasarkan rentang tanggal & kategori produk

</details>

<details>
<summary><b>🧾 Manajemen Transaksi</b></summary>
<br/>

- Riwayat transaksi lengkap dengan status
- Detail per-transaksi dengan breakdown item
- Fitur batalkan transaksi
- Cetak ulang struk dari riwayat

</details>

---

## 🧰 Tech Stack

| Layer | Teknologi |
|---|---|
| **Backend** | PHP 8.2+, Laravel 11.x |
| **Frontend** | Blade Templates, Tailwind CSS 3.x |
| **Build Tool** | Vite |
| **Database** | MySQL 8.0 |
| **Testing** | PHPUnit |
| **Version Control** | Git & GitHub |
