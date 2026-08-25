<?php 
// Chuyển hướng sang trang đăng nhập thống nhất (login.php) với tab=register
// Toàn bộ logic đăng ký đã được tích hợp vào login.php
session_start();
header('Location: login.php?tab=register');
exit();