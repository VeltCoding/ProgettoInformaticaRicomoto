<?php
require_once __DIR__ . "/auth.php";
requirePermission('acquisto.crea');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

$pid = (int)($_POST["prodotto_id"] ?? 0);
if ($pid <= 0) { header("Location: prodotti.php?msg=Prodotto+non+valido"); exit; }

try {
  $conn->beginTransaction();

    // blocco riga prodotto (evita doppio acquisto)
      $stmt = $conn->prepare("SELECT id, officina_id, stato, titolo FROM prodotto WHERE id=? FOR UPDATE");
        $stmt->bindParam(1, $pid);
          $stmt->execute();
            $r = $stmt->fetch();

              if (!$r) throw new Exception("Prodotto non trovato.");
                if ($r["stato"] !== "disponibile") throw new Exception("Prodotto già venduto.");

                  // aggiorna stato
                    $stmt = $conn->prepare("UPDATE prodotto SET stato='venduto' WHERE id=?");
                      $stmt->bindParam(1, $pid);
                        $stmt->execute();

                          // crea notifica per officina
                            $officinaId = (int)$r["officina_id"];
                              $titolo = $r["titolo"];
                                $messaggio = "Hai ricevuto un acquisto per: " . $titolo;

                                  $stmt = $conn->prepare("INSERT INTO notifica(officina_id, prodotto_id, utente_id, messaggio) VALUES (?,?,?,?)");
                                    $stmt->bindParam(1, $officinaId);
                                    $stmt->bindParam(2, $pid);
                                    $stmt->bindParam(3, $userId);
                                    $stmt->bindParam(4, $messaggio);
                                      $stmt->execute();

                                        $conn->commit();

                                          header("Location: prodotti.php?msg=Acquisto+effettuato!");
                                            exit;

                                            } catch (Throwable $e) {
                                              try { $conn->rollback(); } catch (Throwable $t) {}
                                                header("Location: prodotti.php?msg=" . urlencode("Errore: " . $e->getMessage()));
                                                  exit;
                                                  }
                                                  