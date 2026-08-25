<?php 
include_once 'includes/header.php'; 

// Cập nhật vai trò & tỉ lệ hoa hồng
if (isset($_POST['update_role'])) {
    $u_id   = intval($_POST['user_id']);
    $role   = addslashes($_POST['role']);
    $comm   = floatval($_POST['commission_rate']);
    $conn->query("UPDATE users SET role = '$role', commission_rate = $comm WHERE id = $u_id");
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-uppercase mb-0"><i class="fa-solid fa-users-gear text-info me-2"></i>Quản Lý Tài Khoản & Hoa Hồng Nhân Viên</h4>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Họ Tên</th>
                    <th>Email</th>
                    <th>Vai Trò</th>
                    <th>Tỉ Lệ Hoa Hồng (%)</th>
                    <th>Hoa Hồng Tích Lũy</th>
                    <th>Cập Nhật</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($u['fullname']); ?></td>
                            <td><?= htmlspecialchars($u['email']); ?></td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                <td>
                                    <select name="role" class="form-select form-select-sm fw-bold">
                                        <option value="customer" <?= $u['role'] == 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                        <option value="employee" <?= $u['role'] == 'employee' ? 'selected' : ''; ?>>Nhân viên Sale</option>
                                        <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.1" name="commission_rate" class="form-control form-control-sm text-center fw-bold" style="width: 100px;" value="<?= $u['commission_rate']; ?>">
                                </td>
                                <td class="fw-bold text-success fs-6"><?= number_format($u['total_commission'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <button type="submit" name="update_role" class="btn btn-sage btn-sm fw-bold rounded-3">Lưu</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
</div>
</body>
</html>