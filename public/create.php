<?php
// public/create.php
session_start();
require_once __DIR__ . '/../config/db.php';

$errors = [];
$name = $category = $price = $stock = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock    = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    // 1. Validasi Input Teks
    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama produk minimal 3 karakter.';
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE name = :name");
        $stmtCheck->execute(['name' => $name]);
        if ($stmtCheck->fetch()) {
            $errors['name'] = 'Nama produk sudah ada di database (harus unik).';
        }
    }

    if (empty($category)) {
        $errors['category'] = 'Kategori produk wajib dipilih.';
    }

    if ($price === false || $price <= 0) {
        $errors['price'] = 'Harga produk harus angka lebih dari 0.';
    }

    if ($stock === false || $stock < 0) {
        $errors['stock'] = 'Stok produk tidak boleh negatif.';
    }

    // 2. Validasi & Proses Upload File Gambar
    $image_path = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName    = $_FILES['image_file']['name'];
        $fileSize    = $_FILES['image_file']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors['image_file'] = 'Format file gambar harus JPG, JPEG, PNG, atau WEBP.';
        } elseif ($fileSize > 2 * 1024 * 1024) { // Maksimal 2 MB
            $errors['image_file'] = 'Ukuran gambar maksimal 2 MB.';
        } else {
            // Buat nama file unik agar tidak bertabrakan dengan file lain
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadDir   = __DIR__ . '/uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = 'uploads/' . $newFileName;
            } else {
                $errors['image_file'] = 'Gagal menyimpan gambar di server.';
            }
        }
    }

    // 3. Simpan Ke Database jika tidak ada eror
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, image_url) VALUES (:name, :category, :price, :stock, :image_url)");
        $stmt->execute([
            'name'      => $name,
            'category'  => $category,
            'price'     => $price,
            'stock'     => $stock,
            'image_url' => $image_path
        ]);

        header("Location: index.php?status=created");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Perlengkapan Outdoor - SummitGear</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <div class="container navbar">
            <h1 class="logo">🏔️ SummitGear Outdoor</h1>
            <a href="index.php" class="btn btn-secondary">← Kembali ke Katalog</a>
        </div>
    </header>

    <main class="container">
        <div class="form-card">
            <h2>➕ Tambah Produk Outdoor Baru</h2>
            <p>Isi formulir di bawah ini untuk menambahkan perlengkapan outdoor baru.</p>

            <form action="create.php" method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label for="name">Nama Produk <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Tenda Dome 4 Person" required>
                    <?php if (isset($errors['name'])): ?><span class="error-msg"><?= $errors['name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Kategori <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Tenda & Shelter" <?= $category === 'Tenda & Shelter' ? 'selected' : '' ?>>Tenda & Shelter</option>
                        <option value="Tas & Carrier" <?= $category === 'Tas & Carrier' ? 'selected' : '' ?>>Tas & Carrier</option>
                        <option value="Pakaian Outdoor" <?= $category === 'Pakaian Outdoor' ? 'selected' : '' ?>>Pakaian Outdoor</option>
                        <option value="Alat Alas Kaki" <?= $category === 'Alat Alas Kaki' ? 'selected' : '' ?>>Alat Alas Kaki / Sepatu</option>
                        <option value="Peralatan Masak" <?= $category === 'Peralatan Masak' ? 'selected' : '' ?>>Peralatan Masak & Makan</option>
                        <option value="Lainnya" <?= $category === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                    <?php if (isset($errors['category'])): ?><span class="error-msg"><?= $errors['category'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="price">Harga (Rp) <span class="required">*</span></label>
                    <input type="number" id="price" name="price" value="<?= (int)$price ?>" placeholder="1250000" step="any" min="1" required>
                    <?php if (isset($errors['price'])): ?><span class="error-msg"><?= $errors['price'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="stock">Stok Barang <span class="required">*</span></label>
                    <input type="number" id="stock" name="stock" value="<?= htmlspecialchars((string)$stock, ENT_QUOTES, 'UTF-8') ?>" placeholder="10" min="0" required>
                    <?php if (isset($errors['stock'])): ?><span class="error-msg"><?= $errors['stock'] ?></span><?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label for="image_file">Upload File Gambar Produk</label>
                    <input type="file" id="image_file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp">
                    <small>Format yang diperbolehkan: JPG, PNG, WEBP. Maksimal ukuran: 2 MB.</small>
                    <?php if (isset($errors['image_file'])): ?><span class="error-msg"><?= $errors['image_file'] ?></span><?php endif; ?>
                </div>

                <div class="form-actions full-width">
                    <button type="submit" class="btn btn-primary">💾 Simpan Produk</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>