<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'detail';
$kode   = $_GET['kode'] ?? '';

/* ==========================================================
   GET DETAIL TRANSAKSI (untuk cetak ulang struk)
   ========================================================== */
if ($action === 'detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($kode)) {
        echo json_encode(['success' => false, 'msg' => 'Kode transaksi wajib diisi']);
        exit;
    }

    // Ambil header transaksi
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE kode_transaksi = ?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $trx = $stmt->get_result()->fetch_assoc();

    if (!$trx) {
        echo json_encode(['success' => false, 'msg' => 'Transaksi tidak ditemukan']);
        exit;
    }

    // Ambil detail items
    $stmt2 = $conn->prepare("SELECT * FROM transaksi_detail WHERE transaksi_id = ?");
    $stmt2->bind_param("i", $trx['id']);
    $stmt2->execute();
    $detail = $stmt2->get_result();

    $items = [];
    while ($row = $detail->fetch_assoc()) {
        $items[] = [
            'id'       => $row['produk_id'],
            'nama'     => $row['nama_produk'],
            'harga'    => (int)$row['harga'],
            'qty'      => (int)$row['qty'],
            'subtotal' => (int)$row['subtotal']
        ];
    }

    echo json_encode([
        'success'   => true,
        'kode'      => $trx['kode_transaksi'],
        'total'     => (int)$trx['total'],
        'bayar'     => (int)$trx['bayar'],
        'kembalian' => (int)$trx['kembalian'],
        'tanggal'   => date('d/m/Y H:i:s', strtotime($trx['created_at'])),
        'items'     => $items
    ]);
    exit;
}

/* ==========================================================
   LIST RIWAYAT TRANSAKSI (untuk halaman reprint)
   ========================================================== */
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit  = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);

    $stmt = $conn->prepare("SELECT id, kode_transaksi, total, created_at 
                            FROM transaksi 
                            ORDER BY id DESC 
                            LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id'     => $row['id'],
            'kode'   => $row['kode_transaksi'],
            'total'  => (int)$row['total'],
            'tanggal'=> date('d/m/Y H:i', strtotime($row['created_at']))
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

/* ==========================================================
   PENGATURAN TOKO (untuk header struk)
   ========================================================== */
if ($action === 'toko' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $set = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $set]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak valid']);