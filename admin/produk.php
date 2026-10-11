<?php
$pageTitle = 'Manajemen Produk';
$active = 'produk';
require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📦 Manajemen Produk</h1>
        <button class="btn btn-primary" onclick="bukaModal()">➕ Tambah Produk</button>
    </div>

    <div class="card">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Cari produk (nama atau kode)..." onkeyup="if(event.key === 'Enter') loadProduk()">
            <button class="btn btn-primary" onclick="loadProduk()">Cari</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">No</th>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th style="text-align:right">Harga</th>
                        <th style="text-align:center">Stok</th>
                        <th style="width:150px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelProduk">
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:#999">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-bg" id="modalProduk">
    <div class="modal">
        <h2 id="modalTitle">Tambah Produk</h2>
        <form id="formProduk" onsubmit="simpanProduk(event)">
            <input type="hidden" id="inputId">
            <div class="form-group">
                <label>Kode Produk *</label>
                <input type="text" id="inputKode" required maxlength="50">
            </div>
            <div class="form-group">
                <label>Nama Produk *</label>
                <input type="text" id="inputNama" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Harga (Rp) *</label>
                <input type="number" id="inputHarga" required min="0">
            </div>
            <div class="form-group">
                <label>Stok *</label>
                <input type="number" id="inputStok" required min="0" value="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="tutupModal()">Batal</button>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/produk-admin.js"></script>
<?php require_once 'partials/footer.php'; ?>