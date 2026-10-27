<?php
$title = 'Đăng ký';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<main class="auth-page">
    <div class="auth-card">
        <div class="brand-mark">NS</div>
        <h1>Đăng ký tài khoản</h1>
        <p class="muted">Tạo tài khoản khách hàng để đặt hàng và theo dõi đơn mua.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/register" class="form-grid">
            <label>Họ và tên<input type="text" name="name" required value="<?= htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Email<input type="email" name="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Số điện thoại<input type="text" name="phone" value="<?= htmlspecialchars((string) ($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Địa chỉ<input type="text" name="address" value="<?= htmlspecialchars((string) ($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Mật khẩu<input type="password" name="password" required></label>
            <label>Xác nhận mật khẩu<input type="password" name="confirm_password" required></label>
            <button class="btn btn-primary" type="submit">Đăng ký</button>
        </form>

        <p class="demo-note">Đã có tài khoản? <a href="/login">Đăng nhập ngay</a></p>
    </div>
</main>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
