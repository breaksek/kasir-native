<aside class="sidebar">
    <div class="brand">🛒 Kasir Admin</div>

    <nav>
        <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="produk.php" class="<?= $active === 'produk' ? 'active' : '' ?>">📦 Produk</a>
        <a href="../index.php" target="_blank">🧾 Buka Kasir</a>
    </nav>

    <div class="sidebar-footer" style="padding: 15px 20px; border-top: 1px solid #34495e; margin-top: auto;">
        <a href="logout.php" class="logout" style="display: block; width: 100%; padding: 10px 15px; background: #e74c3c; color: #ffffff !important; text-align: center; text-decoration: none; font-weight: bold; font-size: 14px; border-radius: 6px; box-sizing: border-box;">
            🚪 LOGOUT
        </a>
    </div>
</aside>