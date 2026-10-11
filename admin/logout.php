<?php
require_once '../auth/session.php';
session_destroy();
header('Location: login.php');
exit;
?>