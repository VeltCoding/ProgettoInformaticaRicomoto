<?php
require_once __DIR__ . '/auth.php';
if (currentShop()) {
    header('Location: ' . appUrl('gestisci_shop.php'));
    exit;
}
header('Location: ' . appUrl('dashboard.php'));
