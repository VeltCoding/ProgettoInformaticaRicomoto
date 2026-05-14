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

$ordineId = (int)($_POST['ordine_id'] ?? 0);
$azione = $_POST['azione'] ?? '';

if ($ordineId <= 0 || !in_array($azione, ['spedisci', 'annulla', 'completa'], true)) {
  header('Location: ' . appUrl('ordini_officina.php') . '?msg=' . urlencode('Richiesta non valida.'));
  exit;
}

try {
  $conn->begin_transaction();

  $officinaId = (int)$officina['id'];

  $stmt = $conn->prepare(
    'SELECT a.*, p.titolo
     FROM acquisto a
     JOIN prodotto p ON p.id = a.prodotto_id
     WHERE a.id = ?
     AND a.officina_id = ?
     FOR UPDATE'
  );
  $stmt->bind_param('ii', $ordineId, $officinaId);
  $stmt->execute();
  $ordine = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$ordine) {
    throw new Exception('Ordine non trovato.');
  }

  $utenteId = (int)$ordine['utente_id'];
  $prodottoId = (int)$ordine['prodotto_id'];
  $titolo = $ordine['titolo'];

  if ($azione === 'spedisci') {
    $corriere = trim($_POST['corriere'] ?? '');
    $codice = trim($_POST['codice_spedizione'] ?? '');
    $note = trim($_POST['note_ordine'] ?? '');

    if ($codice === '') {
      throw new Exception('Inserisci il codice spedizione.');
    }

    $stmt = $conn->prepare(
      "UPDATE acquisto
       SET stato_ordine = 'spedito',
           corriere = ?,
           codice_spedizione = ?,
           note_ordine = ?
       WHERE id = ?"
    );
    $stmt->bind_param('sssi', $corriere, $codice, $note, $ordineId);
    $stmt->execute();
    $stmt->close();

    $messaggio = 'Il tuo ordine per "' . $titolo . '" è stato spedito. Tracking: ' . $codice;
  }

  if ($azione === 'annulla') {
    if ($ordine['stato_ordine'] === 'annullato') {
      throw new Exception('Ordine già annullato.');
    }

    $stmt = $conn->prepare(
      "UPDATE acquisto
       SET stato_ordine = 'annullato',
           note_ordine = 'Ordine annullato dal venditore.'
       WHERE id = ?"
    );
    $stmt->bind_param('i', $ordineId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE prodotto SET quantita = quantita + 1, stato = 'disponibile' WHERE id = ?");
    $stmt->bind_param('i', $prodottoId);
    $stmt->execute();
    $stmt->close();

    $messaggio = 'Il tuo ordine per "' . $titolo . '" è stato annullato dal venditore.';
  }

  if ($azione === 'completa') {
    $stmt = $conn->prepare("UPDATE acquisto SET stato_ordine = 'completato' WHERE id = ?");
    $stmt->bind_param('i', $ordineId);
    $stmt->execute();
    $stmt->close();

    $messaggio = 'Il tuo ordine per "' . $titolo . '" è stato segnato come completato.';
  }

  $stmt = $conn->prepare(
    'INSERT INTO notifica_utente(utente_id, prodotto_id, messaggio)
     VALUES (?, ?, ?)'
  );
  $stmt->bind_param('iis', $utenteId, $prodottoId, $messaggio);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  header('Location: ' . appUrl('ordini_officina.php') . '?msg=' . urlencode('Ordine aggiornato correttamente.'));
  exit;

} catch (Throwable $e) {
  try {
    $conn->rollback();
  } catch (Throwable $t) {}

  header('Location: ' . appUrl('ordini_officina.php') . '?msg=' . urlencode('Errore: ' . $e->getMessage()));
  exit;
}
