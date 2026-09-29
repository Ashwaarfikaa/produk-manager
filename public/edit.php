<?php
// public/edit.php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

$errors = [];
$name     = $product['name'];
$category = $product['category'];
$price    = $product['price'];
$stock    = $product['stock'];
$image_url= $product['image_url'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock    = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama produk minimal 3 karakter.';
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE name = :name AND id != :id");
        $stmtCheck->execute(['name' => $name, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            $errors['name'] = 'Nama produk ini sudah digunakan oleh produk lain.';
        }
    }

    if (empty($category)) {
        $errors['category'] = 'Kategori wajib dipilih.';
    }

    if ($price === false || $price <= 0) {
        $errors['price'] = 'Harga harus lebih dari 0.';
    }

    if ($stock === false || $stock < 0) {
        $errors['stock'] = 'Stok tidak boleh negatif.';
    }

    // Jika pengguna mengunggah gambar baru
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['image_file']['tmp_name'];
        $fileName      = $_FILES['image_file']['name'];
        $fileSize      = $_FILES['image_file']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors['image_file'] = 'Format file gambar harus JPG, JPEG, PNG, atau WEBP.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errors['image_file'] = 'Ukuran gambar maksimal 2 MB.';
        } else {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadDir   = __DIR__ . '/uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Hapus gambar lama jika ada di folder uploads/
                if (!empty($image_url) && file_exists(__DIR__ . '/' . $image_url) && strpos($image_url, 'uploads/') === 0) {
                    unlink(__DIR__ . '/' . $image_url);
                }
                $image_url = 'uploads/' . $newFileName;
            } else {
                $errors['image_file'] = 'Gagal mengunggah file baru.';
            }
        }
    }

    if (empty($errors)) {
        $stmtUpdate = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, stock = :stock, image_url = :image_url WHERE id = :id");
        $stmtUpdate->execute([
            'name'      => $name,
            'category'  => $category,
            'price'     => $price,
            'stock'     => $stock,
            'image_url' => $image_url,
            'id'        => $id
        ]);

        header("Location: index.php?status=updated");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - SummitGear</title>
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
            <h2>✏️ Edit Produk Outdoor</h2>
            <p>Ubah rincian barang perlengkapan outdoor di bawah ini.</p>

            <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label for="name">Nama Produk <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['name'])): ?><span class="error-msg"><?= $errors['name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Kategori <span class="required">*</span></label>
                    <select id="category" name="category" required>
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
                  <input type="number" id="price" name="price" value="<?= (int)$price ?>" step="any" min="1" required>" min="1" required>
                    <?php if (isset($errors['price'])): ?><span class="error-msg"><?= $errors['price'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="stock">Stok Barang <span class="required">*</span></label>
                    <input type="number" id="stock" name="stock" value="<?= htmlspecialchars((string)$stock, ENT_QUOTES, 'UTF-8') ?>" min="0" required>
                    <?php if (isset($errors['stock'])): ?><span class="error-msg"><?= $errors['stock'] ?></span><?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label for="image_file">Ganti Gambar Produk (Opsional)</label>
                    <?php if (!empty($image_url)): ?>
                        <div style="margin-bottom: 10px;">
                            <small>Gambar saat ini:</small><br>
                            <img src="<?= htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') ?>" alt="Preview" style="height: 80px; border-radius: 8px; object-fit: cover; margin-top: 5px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_file" name="image_file" accept="image/png, image/jpeg, image/jpg, image/webp">
                    <small>Biarkan kosong jika tidak ingin mengubah gambar.</small>
                    <?php if (isset($errors['image_file'])): ?><span class="error-msg"><?= $errors['image_file'] ?></span><?php endif; ?>
                </div>

                <div class="form-actions full-width">
                    <button type="submit" class="btn btn-primary">🔄 Perbarui Produk</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>