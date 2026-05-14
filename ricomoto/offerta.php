<?php
require_once __DIR__ . '/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$prodottoId = (int)($_POST['prodotto_id'] ?? 0);
$prezzoOfferto = (float)str_replace(',', '.', $_POST['prezzo_offerto'] ?? '0');
$messaggio = trim($_POST['messaggio'] ?? '');

if ($prodottoId <= 0 || $prezzoOfferto <= 0) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Offerta non valida.'));
  exit;
}

$stmt = $conn->prepare(
  'SELECT id, officina_id, titolo, prezzo, trattabile, stato, quantita
   FROM prodotto
   WHERE id = ?
   LIMIT 1'
);
$stmt->bind_param('i', $prodottoId);
$stmt->execute();
$prodotto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prodotto) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Prodotto non trovato.'));
  exit;
}

$currentOfficina = currentOfficina();

if ($currentOfficina && (int)$currentOfficina['id'] === (int)$prodotto['officina_id']) {
  header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Non puoi fare offerte sui tuoi prodotti.'));
  exit;
}

if ($prodotto['stato'] !== 'disponibile' || (int)$prodotto['quantita'] <= 0) {
  header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Prodotto non disponibile.'));
  exit;
}

if ((int)$prodotto['trattabile'] !== 1) {
  header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Questo prodotto non è trattabile.'));
  exit;
}

$officinaId = (int)$prodotto['officina_id'];

$stmt = $conn->prepare(
  'INSERT INTO offerta_prezzo(prodotto_id, utente_id, officina_id, prezzo_offerto, messaggio)
   VALUES (?, ?, ?, ?, ?)'
);
$stmt->bind_param('iiids', $prodottoId, $userId, $officinaId, $prezzoOfferto, $messaggio);
$stmt->execute();
$stmt->close();

$notifica = 'Nuova offerta per "' . $prodotto['titolo'] . '": € ' . number_format($prezzoOfferto, 2, ',', '.');

$stmt = $conn->prepare(
  'INSERT INTO notifica(officina_id, prodotto_id, utente_id, messaggio)
   VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('iiis', $officinaId, $prodottoId, $userId, $notifica);
$stmt->execute();
$stmt->close();

header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Offerta inviata correttamente.'));
exit;
