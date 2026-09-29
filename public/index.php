<?php
// public/index.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Generate CSRF Token untuk Keamanan Form Hapus
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fitur Search & Filter (GET Method)
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :q OR category LIKE :q ORDER BY id DESC");
    $stmt->execute(['q' => "%{$q}%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
}

$products = $stmt->fetchAll();

// Gambar Default jika produk belum diunggah gambarnya
$default_img = 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&auto=format&fit=crop&q=80';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SummitGear - Katalog Perlengkapan Outdoor</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <div class="container navbar">
            <h1 class="logo">🏔️ SummitGear Outdoor</h1>
            <a href="create.php" class="btn btn-primary">+ Tambah Produk</a>
        </div>
    </header>

    <main class="container">
        <!-- Notifikasi Status PRG -->
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['status'] === 'created') echo "✅ Produk outdoor berhasil ditambahkan!";
                elseif ($_GET['status'] === 'updated') echo "✅ Data produk berhasil diperbarui!";
                elseif ($_GET['status'] === 'deleted') echo "🗑️ Produk berhasil dihapus dari sistem.";
                ?>
            </div>
        <?php endif; ?>

        <!-- Form Pencarian / Filter (GET) -->
        <section class="search-section">
            <form method="GET" action="index.php" class="search-form">
                <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama atau kategori (contoh: tenda, carrier)...">
                <button type="submit" class="btn btn-secondary">🔍 Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="index.php" class="btn btn-reset">Reset</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Product Grid (Flexbox Responsive Layout) -->
        <section class="products-container">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h3>Produk tidak ditemukan</h3>
                    <p>Silakan coba kata kunci lain atau tambahkan produk baru.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <?php 
                            // Tentukan URL gambar (lokal uploads/ atau default)
                            $imgSrc = !empty($p['image_url']) ? htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8') : $default_img;
                        ?>
                        <div class="card">
                            <div class="card-img-wrapper">
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" class="card-img" onerror="this.src='<?= $default_img ?>'">
                                <span class="badge badge-category"><?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            
                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="card-info">
                                    <p class="price">Rp <?= number_format($p['price'], 0, ',', '.') ?></p>
                                    <p class="stock <?= $p['stock'] < 3 ? 'low-stock' : '' ?>">Stok: <?= (int)$p['stock'] ?></p>
                                </div>
                            </div>

                            <div class="card-actions">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                                
                                <!-- Delete Form via POST + CSRF Protection -->
                                <form action="delete.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>