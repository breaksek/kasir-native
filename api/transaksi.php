<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $items = $input['items'] ?? [];
    $bayar = (int)($input['bayar'] ?? 0);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'msg' => 'Keranjang kosong']);
        exit;
    }
    
    $total = 0;
    foreach ($items as $it) $total += $it['harga'] * $it['qty'];
    $kembalian = $bayar - $total;
    
    if ($kembalian < 0) {
        echo json_encode(['success' => false, 'msg' => 'Pembayaran kurang']);
        exit;
    }
    
    $kode = 'TRX' . date('YmdHis');
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO transaksi (kode_transaksi, total, bayar, kembalian) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $kode, $total, $bayar, $kembalian);
        $stmt->execute();
        $trx_id = $conn->insert_id;
        
        $stmt2 = $conn->prepare("INSERT INTO transaksi_detail (transaksi_id, produk_id, nama_produk, harga, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt3 = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        
        foreach ($items as $it) {
            $sub = $it['harga'] * $it['qty'];
            $stmt2->bind_param("iisiii", $trx_id, $it['id'], $it['nama'], $it['harga'], $it['qty'], $sub);
            $stmt2->execute();
            $stmt3->bind_param("ii", $it['qty'], $it['id']);
            $stmt3->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'kode' => $kode,
            'total' => $total,
            'bayar' => $bayar,
            'kembalian' => $kembalian,
            'tanggal' => date('d/m/Y H:i:s'),
            'items' => $items
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
?>