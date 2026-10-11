let produkList = [];

const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

// ===== Load data =====
async function loadProduk() {
    const q = document.getElementById('searchInput').value.trim();
    const action = q ? 'search' : 'list';
    const url = `../api/produk_admin.php?action=${action}${q ? '&q=' + encodeURIComponent(q) : ''}`;

    try {
        const res = await fetch(url);
        const json = await res.json();
        if (!json.success) throw new Error(json.msg);
        produkList = json.data;
        renderTabel();
    } catch (e) {
        document.getElementById('tabelProduk').innerHTML =
            `<tr><td colspan="6" style="text-align:center;color:#e74c3c">${e.message}</td></tr>`;
    }
}

function renderTabel() {
    const tbody = document.getElementById('tabelProduk');
    if (!produkList.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:#999">Tidak ada produk</td></tr>`;
        return;
    }
    tbody.innerHTML = produkList.map((p, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><code>${escapeHtml(p.kode)}</code></td>
            <td>${escapeHtml(p.nama)}</td>
            <td style="text-align:right">${rupiah(p.harga)}</td>
            <td style="text-align:center">
                <span style="color:${p.stok < 10 ? '#e74c3c' : '#27ae60'};font-weight:600">
                    ${p.stok}
                </span>
            </td>
            <td style="text-align:center">
                <button class="btn btn-warning btn-sm" onclick="editProduk(${p.id})">✏️ Edit</button>
                <button class="btn btn-danger btn-sm" onclick="hapusProduk(${p.id}, '${escapeHtml(p.nama)}')">🗑️</button>
            </td>
        </tr>
    `).join('');
}

// ===== Modal =====
function bukaModal(data = null) {
    document.getElementById('modalProduk').classList.add('show');
    if (data) {
        document.getElementById('modalTitle').textContent = 'Edit Produk';
        document.getElementById('inputId').value = data.id;
        document.getElementById('inputKode').value = data.kode;
        document.getElementById('inputNama').value = data.nama;
        document.getElementById('inputHarga').value = data.harga;
        document.getElementById('inputStok').value = data.stok;
    } else {
        document.getElementById('modalTitle').textContent = 'Tambah Produk';
        document.getElementById('formProduk').reset();
        document.getElementById('inputId').value = '';
    }
}

function tutupModal() {
    document.getElementById('modalProduk').classList.remove('show');
}

function editProduk(id) {
    const p = produkList.find(x => x.id == id);
    if (p) bukaModal(p);
}

// ===== Simpan (Create/Update) =====
async function simpanProduk(e) {
    e.preventDefault();
    const id = document.getElementById('inputId').value;
    const payload = {
        id: id ? parseInt(id) : null,
        kode: document.getElementById('inputKode').value.trim(),
        nama: document.getElementById('inputNama').value.trim(),
        harga: parseInt(document.getElementById('inputHarga').value),
        stok: parseInt(document.getElementById('inputStok').value)
    };

    const action = id ? 'update' : 'create';
    try {
        const res = await fetch(`../api/produk_admin.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.msg);

        tutupModal();
        loadProduk();
        alert('✅ ' + json.msg);
    } catch (e) {
        alert('❌ Gagal: ' + e.message);
    }
}

// ===== Hapus =====
async function hapusProduk(id, nama) {
    if (!confirm(`Hapus produk "${nama}"?\n\nProduk yang sudah pernah terjual mungkin gagal dihapus karena constraint.`)) return;

    try {
        const res = await fetch(`../api/produk_admin.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.msg);
        loadProduk();
        alert('✅ ' + json.msg);
    } catch (e) {
        alert('❌ Gagal: ' + e.message);
    }
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// ===== Live search =====
let timer;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(loadProduk, 300);
});

// Init
loadProduk();