<style>
    .navbar {
        background: #2e7d32;
        color: #fff;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .navbar .menu a {
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        margin-right: 18px;
        opacity: 0.9;
    }
    .navbar .menu a:hover { opacity: 1; text-decoration: underline; }
    .navbar .logout {
        background: #1b5e20;
        padding: 8px 14px;
        border-radius: 6px;
    }
</style>
<div class="navbar">
    <div class="menu">
        <strong style="margin-right:24px;">RVM Admin</strong>
        <a href="dashboard.php">Dashboard</a>
        <a href="kelola_user.php">Kelola User</a>
        <a href="data_sampah.php">Data Sampah</a>
    </div>
    <a href="../auth/logout.php" class="logout" style="color:#fff; text-decoration:none;">Logout</a>
</div>
