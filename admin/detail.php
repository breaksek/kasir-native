<?php
// Koneksi ke database
include '../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p style='color:red;'>ID Transaksi tidak ditemukan.</p>";
    exit;
}

// 1. Ambil data utama dari tabel transaksi
$stmtTrx = $conn->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmtTrx->bind_param("i", $id);
$stmtTrx->execute();
$trx = $stmtTrx->get_result()->fetch_assoc();

if (!$trx) {
    echo "<p style='color:red;'>Data transaksi tidak ditemukan.</p>";
    exit;
}

// 2. Ambil detail item barang dari tabel transaksi_detail
$stmtDetail = $conn->prepare("SELECT * FROM transaksi_detail WHERE transaksi_id = ?");
$stmtDetail->bind_param("i", $id);
$stmtDetail->execute();
$items = $stmtDetail->get_result();
?>

<div style="font-family: sans-serif; font-size: 14px; color: #333;">
    <!-- Informasi Utama Transaksi -->
    <table style="width:100%; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td style="padding: 4px 0;"><strong>Kode / ID Transaksi:</strong></td>
            <td style="padding: 4px 0; text-align: right;"><?= htmlspecialchars($trx['kode_transaksi'] ?? $trx['id']) ?></td>
        </tr>
        <tr>
            <td style="padding: 4px 0;"><strong>Tanggal:</strong></td>
            <td style="padding: 4px 0; text-align: right;"><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
        </tr>
    </table>

    <!-- Rincian Produk -->
    <table style="width:100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
        <thead>
            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; text-align: left;">
                <th style="padding: 8px;">Nama Produk</th>
                <th style="padding: 8px; text-align: center;">Qty</th>
                <th style="padding: 8px; text-align: right;">Harga</th>
                <th style="padding: 8px; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($items->num_rows > 0): ?>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><?= htmlspecialchars($item['nama_produk']) ?></td>
                        <td style="padding: 8px; text-align: center;"><?= $item['qty'] ?></td>
                        <td style="padding: 8px; text-align: right;">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                        <td style="padding: 8px; text-align: right;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="padding: 10px; text-align: center; color: #888;">Tidak ada item dalam transaksi ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8f9fa; border-top: 2px solid #dee2e6;">
                <td colspan="3" style="padding: 8px; text-align: right;">Total Bayar:</td>
                <td style="padding: 8px; text-align: right; color: #27ae60;">Rp <?= number_format($trx['total'], 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>