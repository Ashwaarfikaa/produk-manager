<?php
// public/delete.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $token = $_POST['csrf_token'] ?? '';

    // Proteksi Keamanan CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Akses ditolak! Token CSRF tidak valid.');
    }

    if ($id) {
        // Ambil data gambar sebelum dihapus dari database
        $stmtSelect = $pdo->prepare("SELECT image_url FROM products WHERE id = :id");
        $stmtSelect->execute(['id' => $id]);
        $prod = $stmtSelect->fetch();

        if ($prod && !empty($prod['image_url'])) {
            $filePath = __DIR__ . '/' . $prod['image_url'];
            // Hapus file gambar fisik dari folder uploads jika ada
            if (file_exists($filePath) && strpos($prod['image_url'], 'uploads/') === 0) {
                unlink($filePath);
            }
        }

        // Hapus record dari database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}

header("Location: index.php?status=deleted");
exit;