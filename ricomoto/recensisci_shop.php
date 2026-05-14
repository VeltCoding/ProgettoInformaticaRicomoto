<?php
require_once __DIR__ . '/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$shopId = (int)($_POST['shop_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$commento = trim($_POST['commento'] ?? '');

if ($shopId <= 0) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Shop non valido.'));
  exit;
}

if ($rating < 1 || $rating > 5) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Rating non valido.'));
  exit;
}

if ($commento === '') {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Scrivi un commento per lasciare la recensione.'));
  exit;
}

$stmt = $conn->prepare(
  'SELECT s.slug, o.utente_id AS proprietario_id
   FROM shop s
   JOIN officina o ON o.id = s.officina_id
   WHERE s.id = ?
   LIMIT 1'
);
$stmt->bind_param('i', $shopId);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shop) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Shop non trovato.'));
  exit;
}

if ((int)$shop['proprietario_id'] === $userId) {
  header('Location: ' . tenantStoreUrl($shop['slug']) . '?msg=' . urlencode('Non puoi recensire il tuo shop.'));
  exit;
}

$stmt = $conn->prepare(
  'INSERT INTO recensione_shop(shop_id, utente_id, rating, commento)
   VALUES (?, ?, ?, ?)
   ON DUPLICATE KEY UPDATE rating = VALUES(rating), commento = VALUES(commento), updated_at = CURRENT_TIMESTAMP'
);
$stmt->bind_param('iiis', $shopId, $userId, $rating, $commento);
$stmt->execute();
$stmt->close();

header('Location: ' . tenantStoreUrl($shop['slug']) . '?msg=' . urlencode('Recensione salvata correttamente.'));
exit;