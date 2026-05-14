<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "127.0.0.1";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "ricomoto";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");

$columnCheck = $conn->query("SHOW COLUMNS FROM prodotto LIKE 'quantita'");
if ($columnCheck && $columnCheck->num_rows === 0) {
  $conn->query("ALTER TABLE prodotto ADD quantita int(11) NOT NULL DEFAULT 1 AFTER trattabile");
  $conn->query("UPDATE prodotto SET quantita = 0 WHERE stato = 'venduto'");
}

$shippingColumns = [
  'nome_spedizione' => "ALTER TABLE acquisto ADD nome_spedizione varchar(120) DEFAULT NULL AFTER note_ordine",
  'telefono_spedizione' => "ALTER TABLE acquisto ADD telefono_spedizione varchar(30) DEFAULT NULL AFTER nome_spedizione",
  'indirizzo_spedizione' => "ALTER TABLE acquisto ADD indirizzo_spedizione varchar(255) DEFAULT NULL AFTER telefono_spedizione",
  'citta_spedizione' => "ALTER TABLE acquisto ADD citta_spedizione varchar(100) DEFAULT NULL AFTER indirizzo_spedizione",
  'provincia_spedizione' => "ALTER TABLE acquisto ADD provincia_spedizione varchar(50) DEFAULT NULL AFTER citta_spedizione",
  'cap_spedizione' => "ALTER TABLE acquisto ADD cap_spedizione varchar(10) DEFAULT NULL AFTER provincia_spedizione",
  'note_spedizione' => "ALTER TABLE acquisto ADD note_spedizione text DEFAULT NULL AFTER cap_spedizione",
];

foreach ($shippingColumns as $column => $sql) {
  $columnCheck = $conn->query("SHOW COLUMNS FROM acquisto LIKE '{$column}'");
  if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query($sql);
  }
}
