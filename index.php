<?php
require_once 'config/database.php';
$set = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Aplikasi Kasir</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>🛒 <?= htmlspecialchars($set['nama_toko']) ?></h1>
    <div>
        <button id="btnConnect" class="btn-bt">🔵 Hubungkan Printer</button>
        <span id="btStatus" class="status-off">Belum terhubung</span>
    </div>
</header>

<main>
    <section class="produk-section">
        <input type="text" id="searchProduk" placeholder="Cari produk...">
        <div id="produkList" class="produk-grid"></div>
    </section>

    <aside class="keranjang-section">
        <h2>Keranjang</h2>
        <div id="keranjang"></div>
        <div class="total-box">
            <div>Total: <span id="totalHarga">Rp 0</span></div>
            <input type="number" id="inputBayar" placeholder="Jumlah bayar">
            <div>Kembalian: <span id="kembalian">Rp 0</span></div>
            <button id="btnBayar" class="btn-bayar">Bayar & Cetak</button>
        </div>
    </aside>
</main>

<script>
const PENGATURAN = <?= json_encode($set) ?>;
</script>
<script src="assets/js/bluetooth-printer.js"></script>
<script src="assets/js/kasir.js"></script>
</body>
</html>