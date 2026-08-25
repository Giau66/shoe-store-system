<?php
$root=dirname(__DIR__); $errors=[]; $warnings=[];
foreach(['mysqli','curl','mbstring','openssl'] as $ext) if(!extension_loaded($ext)) $errors[]="Thiếu PHP extension: $ext";
foreach(['config/db.php','database/web_shoe.sql','index.php','login.php','cart.php','checkout.php','admin/index.php'] as $f) if(!is_file("$root/$f")) $errors[]="Thiếu file: $f";
if(!is_dir("$root/uploads") || !is_writable("$root/uploads")) $warnings[]='Thư mục uploads chưa có quyền ghi.';
$local="$root/config/local-config.php"; if(!is_file($local)) $warnings[]='Chưa có config/local-config.php: Google/Gemini sẽ bị tắt.';
echo "SHOES STORE AUDIT\n==================\n";
foreach($errors as $x) echo "[ERROR] $x\n"; foreach($warnings as $x) echo "[WARN ] $x\n";
if(!$errors) echo "[OK] Kiểm tra môi trường cơ bản đạt.\n";
exit($errors?1:0);
