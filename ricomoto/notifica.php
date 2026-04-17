<?php
require_once __DIR__ . "/auth.php";
requirePermission('acquisto.crea');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

$pid = (int)($_POST["prodotto_id"] ?? 0);
if ($pid <= 0) { header("Location: prodotti.php?msg=Prodotto+non+valido"); exit; }

$messaggio = trim($_POST["messaggio"] ?? "");

if ($messaggio === "") {
  header("Location: prodotti.php?msg=" . urlencode("Inserisci un messaggio prima di inviare la richiesta di info."));
  exit;
}

try {
  $conn->beginTransaction();

  $stmt = $conn->prepare("SELECT id, officina_id, titolo FROM prodotto WHERE id=? FOR UPDATE");
  $stmt->bindParam(1, $pid);
  $stmt->execute();
  $r = $stmt->fetch();

  if (!$r) throw new Exception("Prodotto non trovato.");

  $officinaId = (int)$r["officina_id"];
  $titolo = $r["titolo"];

  $fullMessage = "Richiesta di info su '$titolo': " . $messaggio;

  $stmt = $conn->prepare("INSERT INTO notifica(officina_id, prodotto_id, utente_id, messaggio) VALUES (?,?,?,?)");
  $stmt->bindParam(1, $officinaId);
  $stmt->bindParam(2, $pid);
  $stmt->bindParam(3, $userId);
  $stmt->bindParam(4, $fullMessage);
  $stmt->execute();

  $conn->commit();

  header("Location: prodotti.php?msg=" . urlencode("Richiesta inviata all'officina."));
  exit;

} catch (Throwable $e) {
  try { $conn->rollback(); } catch (Throwable $t) {}
  header("Location: prodotti.php?msg=" . urlencode("Errore: " . $e->getMessage()));
  exit;
}
