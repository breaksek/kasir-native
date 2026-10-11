<?php require_once 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Ulang Struk</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    .reprint-box { max-width: 700px; margin: 30px auto; background: white; padding: 25px; border-radius: 10px; }
    .trx-row { display: flex; justify-content: space-between; align-items: center;
               padding: 12px; border-bottom: 1px solid #eee; }
    .trx-row:hover { background: #f9f9f9; }
    .btn-print { background: #27ae60; color: white; border: none;
                 padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    .btn-print:hover { background: #219a52; }
    .status-bt { font-size: 13px; margin-bottom: 15px; }
    .cari-box { display: flex; gap: 10px; margin-bottom: 20px; }
    .cari-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
    .cari-box button { padding: 10px 20px; background: #3498db; color: white;
                       border: none; border-radius: 6px; cursor: pointer; }
</style>
</head>
<body>

<div class="reprint-box">
    <h1>🧾 Cetak Ulang Struk</h1>
    <div class="status-bt">
        <button id="btnConnect" class="btn-print">🔵 Hubungkan Printer</button>
        <span id="btStatus" class="status-off">Belum terhubung</span>
    </div>

    <div class="cari-box">
        <input type="text" id="inputKode" placeholder="Masukkan kode transaksi (contoh: TRX20240101120000)">
        <button onclick="cetakByKode()">Cetak</button>
    </div>

    <h3>Riwayat Transaksi Terakhir</h3>
    <div id="riwayatList">Memuat...</div>
</div>

<script>
const PENGATURAN = <?= json_encode($conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc()) ?>;
</script>
<script src="assets/js/bluetooth-printer.js"></script>
<script>
const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

// ===== Bluetooth connect =====
document.getElementById('btnConnect').addEventListener('click', async () => {
    const btn = document.getElementById('btnConnect');
    const status = document.getElementById('btStatus');
    try {
        if (BTPrinter.isConnected()) {
            await BTPrinter.testPrint();
            return alert('Test print dikirim!');
        }
        status.textContent = 'Menghubungkan...';
        const d = await BTPrinter.connect();
        status.textContent = '✅ ' + d.name;
        status.className = 'status-on';
        btn.textContent = '🖨️ Test Print';
        btn.classList.add('connected');
    } catch (e) {
        status.textContent = '❌ ' + e.message;
        alert('Gagal: ' + e.message);
    }
});

// ===== Load riwayat =====
async function loadRiwayat() {
    const res = await fetch('api/struk.php?action=list&limit=20');
    const json = await res.json();
    const el = document.getElementById('riwayatList');
    if (!json.success || !json.data.length) {
        el.innerHTML = '<p style="color:#999">Belum ada transaksi</p>';
        return;
    }
    el.innerHTML = json.data.map(t => `
        <div class="trx-row">
            <div>
                <div><strong>${t.kode}</strong></div>
                <div style="font-size:12px;color:#888">${t.tanggal} — ${rupiah(t.total)}</div>
            </div>
            <button class="btn-print" onclick="cetakByKode('${t.kode}')">🖨️ Cetak</button>
        </div>
    `).join('');
}

// ===== Cetak by kode =====
async function cetakByKode(kodeManual) {
    const kode = kodeManual || document.getElementById('inputKode').value.trim();
    if (!kode) return alert('Kode transaksi kosong');
    if (!BTPrinter.isConnected()) return alert('Printer belum terhubung. Klik "Hubungkan Printer" dulu.');

    try {
        const res = await fetch(`api/struk.php?action=detail&kode=${encodeURIComponent(kode)}`);
        const json = await res.json();
        if (!json.success) return alert('Gagal: ' + json.msg);

        await BTPrinter.printStruk(json, PENGATURAN, 32);
        alert('✅ Struk berhasil dicetak!');
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

loadRiwayat();
</script>
</body>
</html>