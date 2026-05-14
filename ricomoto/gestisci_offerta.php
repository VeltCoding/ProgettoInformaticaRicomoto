<?php
require_once __DIR__ . '/auth.php';

requireLogin();

if (!hasRole('officina')) {
  http_response_code(403);
  die('Solo officina.');
}

$officina = currentOfficina();

if (!$officina) {
  http_response_code(403);
  die('Officina non trovata.');
}

$offertaId = (int)($_POST['offerta_id'] ?? 0);
$azione = $_POST['azione'] ?? '';

if ($offertaId <= 0 || !in_array($azione, ['accetta', 'rifiuta'], true)) {
  header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Richiesta non valida.'));
  exit;
}

try {
  $conn->begin_transaction();

  $officinaId = (int)$officina['id'];

  $stmt = $conn->prepare(
    'SELECT op.*, p.titolo, p.stato AS stato_prodotto, p.quantita
     FROM offerta_prezzo op
     JOIN prodotto p ON p.id = op.prodotto_id
     WHERE op.id = ?
     AND op.officina_id = ?
     FOR UPDATE'
  );
  $stmt->bind_param('ii', $offertaId, $officinaId);
  $stmt->execute();
  $offerta = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$offerta) {
    throw new Exception('Offerta non trovata.');
  }

  $statoOfferta = trim(strtolower($offerta['stato'] ?? ''));

  if ($statoOfferta === '') {
    $statoOfferta = 'in_attesa';
  }

  if ($statoOfferta !== 'in_attesa') {
    throw new Exception('Questa offerta è già stata gestita. Stato attuale: ' . $statoOfferta);
  }

  if ($offerta['stato_prodotto'] !== 'disponibile' || (int)$offerta['quantita'] <= 0) {
    throw new Exception('Il prodotto è già venduto o non disponibile.');
  }

  $prodottoId = (int)$offerta['prodotto_id'];
  $utenteId = (int)$offerta['utente_id'];
  $prezzoOfferto = (float)$offerta['prezzo_offerto'];
  $titolo = $offerta['titolo'];

  if ($azione === 'accetta') {
    $stmt = $conn->prepare("UPDATE offerta_prezzo SET stato = 'accettata' WHERE id = ?");
    $stmt->bind_param('i', $offertaId);
    $stmt->execute();
    $stmt->close();

    $messaggio = 'La tua offerta per "' . $titolo . '" è stata accettata. Puoi acquistare il prodotto a € ' . number_format($prezzoOfferto, 2, ',', '.') . '.';
  } else {
    $stmt = $conn->prepare("UPDATE offerta_prezzo SET stato = 'rifiutata' WHERE id = ?");
    $stmt->bind_param('i', $offertaId);
    $stmt->execute();
    $stmt->close();

    $messaggio = 'La tua offerta per "' . $titolo . '" è stata rifiutata.';
  }

  $stmt = $conn->prepare(
    'INSERT INTO notifica_utente(utente_id, prodotto_id, messaggio)
     VALUES (?, ?, ?)'
  );
  $stmt->bind_param('iis', $utenteId, $prodottoId, $messaggio);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Offerta gestita correttamente.'));
  exit;

} catch (Throwable $e) {
  try {
    $conn->rollback();
  } catch (Throwable $t) {}

  header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Errore: ' . $e->getMessage()));
  exit;
}
