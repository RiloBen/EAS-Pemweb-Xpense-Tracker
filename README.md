# Xpense Tracker

Aplikasi manajemen keuangan pribadi berbasis Native PHP 8 dengan sistem autentikasi multi-user.

## Persyaratan

- **Laragon** (PHP 8.x, MySQL 8.x)
- Browser modern (Chrome, Firefox, Edge)

## Instalasi

### 1. Setup Database

1. Buka **phpMyAdmin** di Laragon (`http://localhost/phpmyadmin`)
2. Klik tab **Import**
3. Pilih file `database.sql` dari folder project ini
4. Klik **Go / Eksekusi**

Database `xpense_tracker` beserta tabel `users` dan `transaksi` akan terbuat otomatis.

### 2. Konfigurasi Koneksi

File `config/database.php` tidak ikut di-commit (ada di `.gitignore`). Salin dari template:

```bash
# Salin file template
copy config\database.example.php config\database.php
```

Lalu buka `config/database.php` dan sesuaikan kredensial:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // username MySQL Anda
define('DB_PASS', 'your_password'); // password MySQL Anda
define('DB_NAME', 'xpense_tracker');
```

### 3. Permission Folder Uploads

Pastikan folder `uploads/` dapat ditulis oleh server:

- **Windows (Laragon):** Klik kanan folder `uploads/` → Properties → pastikan **tidak** centang *Read-only*
- Folder ini sudah ada dalam project (berisi `.gitkeep`)

### 4. Akses Aplikasi

Buka browser dan akses:

```
http://localhost/EAS Pemweb Xpense Tracker/
```

Anda akan diarahkan ke halaman **Login**. Daftar akun baru terlebih dahulu melalui halaman Register.

---

## Fitur

| Fitur | Keterangan |
|-------|-----------|
| **Register & Login** | Autentikasi dengan bcrypt password hashing |
| **Session Protection** | Semua halaman terlindungi dari akses tanpa login |
| **Data Isolation** | Setiap pengguna hanya bisa melihat data miliknya |
| **CRUD Transaksi** | Create, Read, Update, Delete |
| **Upload Foto Nota** | JPG/PNG, maks 2MB, preview instan sebelum submit |
| **Ringkasan Bulanan** | Total pendapatan, pengeluaran, dan saldo bulan ini |
| **Filter Bulan**      | Filter tabel & ringkasan per bulan/tahun via Flatpickr |
| **Pencarian** | Filter transaksi berdasarkan keterangan |
| **Pagination** | 10 data per halaman dengan info status |
| **Responsive UI** | Tailwind CSS, mendukung mobile dan desktop |

## Keamanan

- Password disimpan dengan `password_hash()` (BCrypt), **tidak** plain text
- Semua query menggunakan **Prepared Statements** (mysqli) untuk mencegah SQL Injection
- Input yang ditampilkan ke HTML di-sanitasi dengan `htmlspecialchars()`
- Session ID diperbarui setelah login (`session_regenerate_id`)
- Validasi kepemilikan data: setiap operasi CRUD memfilter `WHERE user_id = ?`

---

*EAS Pemrograman Web — PHP 8 Native*
