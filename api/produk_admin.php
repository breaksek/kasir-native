<?php
require_once '../auth/session.php';
require_once '../config/database.php';

requireLoginAPI();

$action = $_GET['action'] ?? 'list';

/* ==========================================================
   READ - List semua produk
   ========================================================== */
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM produk ORDER BY id DESC");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

/* ==========================================================
   READ - Search produk
   ========================================================== */
if ($action === 'search' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = '%' . ($_GET['q'] ?? '') . '%';
    $stmt = $conn->prepare("SELECT * FROM produk WHERE nama LIKE ? OR kode LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ss", $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

/* ==========================================================
   CREATE - Tambah produk baru
   ========================================================== */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $kode  = trim($input['kode'] ?? '');
    $nama  = trim($input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $stok  = (int)($input['stok'] ?? 0);

    if ($kode === '' || $nama === '') {
        echo json_encode(['success' => false, 'msg' => 'Kode dan nama wajib diisi']);
        exit;
    }
    if ($harga < 0 || $stok < 0) {
        echo json_encode(['success' => false, 'msg' => 'Harga & stok tidak boleh negatif']);
        exit;
    }

    // Cek duplikat kode
    $cek = $conn->prepare("SELECT id FROM produk WHERE kode = ?");
    $cek->bind_param("s", $kode);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Kode produk sudah digunakan']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO produk (kode, nama, harga, stok) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $kode, $nama, $harga, $stok);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'msg' => 'Produk berhasil ditambahkan', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'msg' => $conn->error]);
    }
    exit;
}

/* ==========================================================
   UPDATE - Edit produk
   ========================================================== */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id    = (int)($input['id'] ?? 0);
    $kode  = trim($input['kode'] ?? '');
    $nama  = trim($input['nama'] ?? '');
    $harga = (int)($input['harga'] ?? 0);
    $stok  = (int)($input['stok'] ?? 0);

    if ($id <= 0 || $kode === '' || $nama === '') {
        echo json_encode(['success' => false, 'msg' => 'Data tidak lengkap']);
        exit;
    }
    if ($harga < 0 || $stok < 0) {
        echo json_encode(['success' => false, 'msg' => 'Harga & stok tidak boleh negatif']);
        exit;
    }

    // Cek duplikat kode (selain dirinya sendiri)
    $cek = $conn->prepare("SELECT id FROM produk WHERE kode = ? AND id != ?");
    $cek->bind_param("si", $kode, $id);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Kode produk sudah digunakan produk lain']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE produk SET kode=?, nama=?, harga=?, stok=? WHERE id=?");
    $stmt->bind_param("ssiii", $kode, $nama, $harga, $stok, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'msg' => 'Produk berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'msg' => $conn->error]);
    }
    exit;
}

/* ==========================================================
   DELETE - Hapus produk
   ========================================================== */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID tidak valid']);
        exit;
    }

    // Cek apakah produk sudah pernah ada di transaksi
    $cek = $conn->prepare("SELECT COUNT(*) c FROM transaksi_detail WHERE produk_id = ?");
    $cek->bind_param("i", $id);
    $cek->execute();
    $dipakai = $cek->get_result()->fetch_assoc()['c'];

    if ($dipakai > 0) {
        echo json_encode([
            'success' => false,
            'msg' => "Produk tidak bisa dihapus karena sudah pernah terjual ($dipakai transaksi). Silakan edit stok menjadi 0."
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'msg' => 'Produk berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'msg' => $conn->error]);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak valid']);
?>