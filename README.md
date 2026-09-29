1. Peta Lokasi File di Laptop (htdocs)
semua file disimpan di C:\xampp\htdocs\SummitGear_Outdoor\:

Plaintext
C:\xampp\htdocs\SummitGear_Outdoor\
├── config\
│   └── db.php               <-- Koneksi Database PDO
├── database\
│   └── store_db.sql         <-- Query Database
├── public\
│   ├── assets\
│   │   └── style.css        <-- Tampilan & Grid Flexbox
│   ├── uploads\             <-- Folder tempat foto produk tersimpan
│   ├── index.php            <-- Halaman Utama (Katalog & Cari)
│   ├── create.php           <-- Tambah Produk + Upload Foto
│   ├── edit.php             <-- Edit Produk & Ganti Foto
│   └── delete.php           <-- Hapus Produk & Foto
└── README.md                <-- Dokumentasi GitHub

2. Cara Jalankan Aplikasi
Nyalakan XAMPP: Klik Start pada Apache dan MySQL.
Import Database:
Buka browser: http://localhost/phpmyadmin
Buka tab SQL, salin isi file store_db.sql, lalu klik Kirim / Go.
Buka Web:
Akses link ini di browser:
http://localhost/SummitGear_Outdoor/public/

3. Cara Push / Upload ke GitHub (Jika Ada Perubahan)
Buka PowerShell di folder project kamu, lalu jalankan 3 perintah ini:
Bash
git add .
git commit -m "perbarui aplikasi toko outdoor"
git push -u origin main
(Jika minta akses/error 403 lagi, jalankan perintah ini dengan token GitHub kamu):

Bash
git remote set-url origin https://TOKEN_GITHUB_KAMU@github.com/Ashwaarfikaa/produk-manager.git
git push -u origin main
