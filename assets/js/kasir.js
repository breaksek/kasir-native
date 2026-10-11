let produkData = [];
let keranjang = [];

const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

// Load produk
async function loadProduk(q = '') {
    const url = q ? `api/produk.php?action=search&q=${encodeURIComponent(q)}` 
                  : 'api/produk.php?action=list';
    const res = await fetch(url);
    const json = await res.json();
    produkData = json.data;
    renderProduk();
}

function renderProduk() {
    const el = document.getElementById('produkList');
    el.innerHTML = produkData.map(p => `
        <div class="produk-card" onclick="tambahKeKeranjang(${p.id})">
            <div class="nama">${p.nama}</div>
            <div class="harga">${rupiah(p.harga)}</div>
            <div class="sub">Stok: ${p.stok}</div>
        </div>
    `).join('');
}

function tambahKeKeranjang(id) {
    const p = produkData.find(x => x.id == id);
    if (!p) return;
    const item = keranjang.find(x => x.id == id);
    if (item) item.qty++;
    else keranjang.push({ id: p.id, nama: p.nama, harga: +p.harga, qty: 1 });
    renderKeranjang();
}

function ubahQty(id, delta) {
    const item = keranjang.find(x => x.id == id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) keranjang = keranjang.filter(x => x.id != id);
    renderKeranjang();
}

function renderKeranjang() {
    const el = document.getElementById('keranjang');
    if (!keranjang.length) {
        el.innerHTML = '<p style="color:#999;text-align:center;padding:20px">Belum ada item</p>';
    } else {
        el.innerHTML = keranjang.map(it => `
            <div class="item-cart">
                <div class="info">
                    <div class="nama">${it.nama}</div>
                    <div class="sub">${rupiah(it.harga)} × ${it.qty} = ${rupiah(it.harga * it.qty)}</div>
                </div>
                <div class="qty-control">
                    <button onclick="ubahQty(${it.id}, -1)">−</button>
                    <span class="qty">${it.qty}</span>
                    <button onclick="ubahQty(${it.id}, 1)">+</button>
                </div>
            </div>
        `).join('');
    }
    hitungTotal();
}

function hitungTotal() {
    const total = keranjang.reduce((s, it) => s + it.harga * it.qty, 0);
    document.getElementById('totalHarga').textContent = rupiah(total);
    const bayar = +document.getElementById('inputBayar').value || 0;
    const kembali = bayar - total;
    document.getElementById('kembalian').textContent = rupiah(kembali > 0 ? kembali : 0);
    return { total, bayar, kembali };
}

// Bluetooth
document.getElementById('btnConnect').addEventListener('click', async () => {
    const btn = document.getElementById('btnConnect');
    const status = document.getElementById('btStatus');
    try {
        if (BTPrinter.isConnected()) {
            await BTPrinter.testPrint();
            alert('Test print dikirim!');
            return;
        }
        status.textContent = 'Menghubungkan...';
        const d = await BTPrinter.connect();
        status.textContent = '✅ ' + d.name;
        status.className = 'status-on';
        btn.textContent = '🖨️ Test Print';
        btn.classList.add('connected');
    } catch (e) {
        status.textContent = '❌ Gagal: ' + e.message;
        status.className = 'status-off';
        alert('Gagal koneksi printer: ' + e.message);
    }
});

window.onPrinterDisconnect = () => {
    const btn = document.getElementById('btnConnect');
    const status = document.getElementById('btStatus');
    status.textContent = 'Printer terputus';
    status.className = 'status-off';
    btn.textContent = '🔵 Hubungkan Printer';
    btn.classList.remove('connected');
};

// Bayar
document.getElementById('inputBayar').addEventListener('input', hitungTotal);

document.getElementById('btnBayar').addEventListener('click', async () => {
    if (!keranjang.length) return alert('Keranjang kosong');

    const bayar = +document.getElementById('inputBayar').value || 0;
    const total = keranjang.reduce((s, it) => s + it.harga * it.qty, 0);
    if (bayar < total) return alert('Pembayaran kurang!');

    if (!confirm(`Total: ${rupiah(total)}\nBayar: ${rupiah(bayar)}\n\nProses pembayaran?`)) return;

    try {
        const res = await fetch('api/transaksi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: keranjang, bayar })
        });
        const json = await res.json();

        if (!json.success) return alert('Gagal: ' + json.msg);

        // Cetak struk jika printer terhubung
        if (BTPrinter.isConnected()) {
            try {
                await BTPrinter.printStruk(json, PENGATURAN, 32);
            } catch (e) {
                alert('Transaksi sukses, tapi cetak struk gagal: ' + e.message);
            }
        } else {
            alert('Transaksi sukses!\n(Printer belum terhubung)');
        }

        keranjang = [];
        document.getElementById('inputBayar').value = '';
        renderKeranjang();
        loadProduk();
    } catch (e) {
        alert('Error: ' + e.message);
    }
});

// Search
let searchTimer;
document.getElementById('searchProduk').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProduk(e.target.value), 300);
});

// Init
loadProduk();