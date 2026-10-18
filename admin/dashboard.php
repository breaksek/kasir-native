<?php
$pageTitle = 'Dashboard';
$active = 'dashboard';

require_once 'partials/header.php';
require_once 'partials/sidebar.php';

$totalProduk = $conn->query("SELECT COUNT(*) c FROM produk WHERE is_active = 1")->fetch_assoc()['c'];
$totalStok   = $conn->query("SELECT COALESCE(SUM(stok),0) c FROM produk WHERE is_active = 1")->fetch_assoc()['c'];
$trxHariIni  = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$pendapatan  = $conn->query("SELECT COALESCE(SUM(total),0) c FROM transaksi WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];

$riwayatTrx  = $conn->query("SELECT * FROM transaksi ORDER BY created_at DESC");
?>

<main class="content">
    <div class="page-header">
        <h1>📊 Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Produk</h3>
            <div class="value"><?= $totalProduk ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#27ae60">
            <h3>Total Stok</h3>
            <div class="value"><?= $totalStok ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#f39c12">
            <h3>Transaksi Hari Ini</h3>
            <div class="value"><?= $trxHariIni ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#e74c3c">
            <h3>Pendapatan Hari Ini</h3>
            <div class="value">Rp <?= number_format($pendapatan, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="card" style="margin-top:20px">
        <h2 style="margin-bottom:15px;color:#2c3e50;font-size:17px">📜 Riwayat Transaksi</h2>
        
        <?php if ($riwayatTrx->num_rows === 0): ?>
            <p style="color:#7f8c8d">Belum ada riwayat transaksi.</p>
        <?php else: ?>
            <div class="table-wrap" style="max-height: 400px; overflow-y: auto;">
                <table>
                    <thead style="position: sticky; top: 0; background: #34495e; z-index: 1;">
                        <tr>
                            <th>No</th>
                            <th>Kode / ID</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($t = $riwayatTrx->fetch_assoc()): 
                            $trxId = $t['id'];
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($t['kode_transaksi'] ?? $t['id']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                            <td>Rp <?= number_format($t['total'], 0, ',', '.') ?></td>
                            <td>
                                <button type="button" onclick="showDetail(<?= $trxId ?>)" class="btn btn-primary btn-sm">
                                    Detail
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<div id="modalDetail" class="modal-bg">
    <div class="modal">
        <h3 style="margin-top:0; color:#2c3e50;">📋 Detail Transaksi</h3>
        <div id="modalContent">Memuat data...</div>
        <div class="modal-actions">
            <button onclick="closeModal()" class="btn btn-danger">Tutup</button>
        </div>
    </div>
</div>

<script>
function showDetail(id) {
    const modal = document.getElementById('modalDetail');
    const content = document.getElementById('modalContent');
    modal.classList.add('show');
    content.innerHTML = 'Memuat data...';

    fetch('detail.php?id=' + id)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = '<p style="color:red">Gagal memuat detail transaksi.</p>';
        });
}

function closeModal() {
    document.getElementById('modalDetail').classList.remove('show');
}
</script>

<?php require_once 'partials/footer.php'; ?>