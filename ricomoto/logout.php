<?php
require_once __DIR__ . '/tenant.php';
session_start();
session_destroy();
header('Location: ' . appUrl('index.php'));
exit;
