<?php
require_once __DIR__ . '/config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'login_password') {
    $account = trim((string)($_POST['account'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($account === '' || $password === '') {
        $_SESSION['login_error'] = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.';
        header('Location: login.php'); exit;
    }
    $stmt = $conn->prepare('SELECT id, fullname, password, role, status FROM users WHERE email = ? OR phone = ? LIMIT 1');
    $stmt->bind_param('ss', $account, $account); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
    
    // Kiểm tra nhân viên đã nghỉ việc
    $is_employee_resigned = false;
    if ($user && in_array($user['role'], ['staff', 'employee'])) {
        $chk_emp = $conn->query("SELECT status FROM employees WHERE user_id = " . intval($user['id']) . " LIMIT 1");
        if ($chk_emp && $row_e = $chk_emp->fetch_assoc()) {
            if (intval($row_e['status']) === 0) {
                $is_employee_resigned = true;
            }
        }
    }

    if ($is_employee_resigned) {
        $_SESSION['login_error'] = 'Tài khoản nhân viên này đã NGHỈ VIỆC và không còn quyền truy cập hệ thống.';
        header('Location: login.php'); exit;
    }

    if ($user && (int)$user['status'] === 1 && !empty($user['password']) && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_name']=$user['fullname']; $_SESSION['user_role']=$user['role'];
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php'; unset($_SESSION['redirect_after_login']);
        if (in_array($user['role'], ['admin', 'staff', 'employee'])) {
            $_SESSION['admin_login_transition'] = true;
        }
        header('Location: '.($user['role']==='admin' ? 'admin/index.php' : $redirect)); exit;
    }
    $_SESSION['login_error']='Tài khoản hoặc mật khẩu không chính xác, hoặc tài khoản đã bị khóa.';
    header('Location: login.php'); exit;
}

if ($action === 'send_otp') {
    $target = trim((string)($_POST['target'] ?? ''));
    $isEmail = filter_var($target, FILTER_VALIDATE_EMAIL);
    $isPhone = preg_match('/^[0-9]{9,11}$/', $target);
    if (!$isEmail && !$isPhone) { $_SESSION['login_error']='Email hoặc số điện thoại không hợp lệ.'; header('Location: login.php'); exit; }
    $stmt=$conn->prepare('SELECT id FROM users WHERE (email = ? OR phone = ?) AND status = 1 LIMIT 1');
    $stmt->bind_param('ss',$target,$target); $stmt->execute(); $exists=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$exists) { $_SESSION['login_error']='Không tìm thấy tài khoản đang hoạt động.'; header('Location: login.php'); exit; }
    $otp=(string)random_int(100000,999999); $type=$isEmail?'email':'phone'; $expires=date('Y-m-d H:i:s',time()+300);
    $stmt=$conn->prepare("INSERT INTO otp_codes (target, otp_code, type, action, expires_at) VALUES (?, ?, ?, 'login', ?)");
    $stmt->bind_param('ssss',$target,$otp,$type,$expires); $stmt->execute(); $stmt->close();
    $_SESSION['otp_target']=$target; $_SESSION['otp_demo']=$otp;
    header('Location: otp-verify.php'); exit;
}

if ($action === 'verify_otp') {
    $target=(string)($_SESSION['otp_target'] ?? ''); $otp=trim((string)($_POST['otp_code'] ?? ''));
    if ($target==='' || !preg_match('/^[0-9]{6}$/',$otp)) { $_SESSION['otp_error']='Phiên OTP không hợp lệ.'; header('Location: otp-verify.php'); exit; }
    $conn->begin_transaction();
    try {
        $stmt=$conn->prepare("SELECT id FROM otp_codes WHERE target=? AND otp_code=? AND action='login' AND is_used=0 AND expires_at>NOW() ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->bind_param('ss',$target,$otp); $stmt->execute(); $code=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$code) throw new RuntimeException('Mã OTP không đúng hoặc đã hết hạn.');
        $cid=(int)$code['id']; $stmt=$conn->prepare('UPDATE otp_codes SET is_used=1 WHERE id=?'); $stmt->bind_param('i',$cid); $stmt->execute(); $stmt->close();
        $stmt=$conn->prepare('SELECT id, fullname, role, status FROM users WHERE email=? OR phone=? LIMIT 1');
        $stmt->bind_param('ss',$target,$target); $stmt->execute(); $user=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$user || (int)$user['status']!==1) throw new RuntimeException('Tài khoản không tồn tại hoặc đã bị khóa.');
        $conn->commit(); session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_name']=$user['fullname']; $_SESSION['user_role']=$user['role'];
        if (in_array($user['role'], ['admin', 'staff', 'employee'])) {
            $_SESSION['admin_login_transition'] = true;
        }
        unset($_SESSION['otp_target'],$_SESSION['otp_demo']); 
        header('Location: ' . (in_array($user['role'], ['admin', 'staff', 'employee']) ? 'admin/index.php' : 'index.php')); 
        exit;
    } catch (Throwable $e) { $conn->rollback(); $_SESSION['otp_error']=$e->getMessage(); header('Location: otp-verify.php'); exit; }
}

header('Location: login.php'); exit;
