<?php
/**
 * Router per Multitenancy
 * Gestisce il routing basato su subdomain
 * 
 * Usage: includere questo file all'inizio di ogni pagina
 */

require_once __DIR__ . "/auth.php";

/**
 * Determina il tipo di pagina corrente
 */
function getCurrentPageType(): string {
  $subdomain = getSubdomain();
  
  // Admin tenancy
  if ($subdomain === "admin") {
    return "admin_tenancy";
  }
  
  // Shop specifico (subdomain != null e != www)
  if ($subdomain && $subdomain !== "www") {
    return "shop";
  }
  
  // Homepage / catalogo globale
  return "global";
}

/**
 * Ottiene le info del tenancy corrente
 */
function getCurrentTenancy(): ?array {
  global $conn;
  
  $tenancyId = getCurrentTenancyId();
  if (!$tenancyId) return null;
  
  $stmt = $conn->prepare("SELECT * FROM tenancy WHERE id = ?");
  $stmt->bindParam(1, $tenancyId);
  $stmt->execute();
  return $stmt->fetch();
}

/**
 * Verifica se l'utente può accedere alla pagina corrente
 */
function checkTenancyAccess(): bool {
  $pageType = getCurrentPageType();
  
  // Pagine globali: accesso libero
  if ($pageType === "global") {
    return true;
  }
  
  // Admin tenancy: solo utenti con ruolo admin_tenancy
  if ($pageType === "admin_tenancy") {
    return isAdminTenancy();
  }
  
  // Shop: verifica che l'utente appartenga al tenancy
  if ($pageType === "shop") {
    $tenancyId = getCurrentTenancyId();
    
    // Se non loggato,允许 visualizzazione (catalogo pubblico)
    if (!isLoggedIn()) {
      return true;
    }
    
    // Se loggato come admin_tenancy, può vedere tutto
    if (isAdminTenancy()) {
      return true;
    }
    
    // Se loggato come officina, verifica che appartenga al tenancy
    if (hasPermission("prodotto.crea")) {
      $userTenancyId = loadUserTenancy($_SESSION["user_id"]);
      return $userTenancyId === $tenancyId;
    }
    
    // Cliente può vedere tutti gli shop attivi
    return true;
  }
  
  return true;
}

/**
 * Reindirizza se non hai accesso
 */
function requireTenancyAccess(): void {
  if (!checkTenancyAccess()) {
    http_response_code(403);
    die("Accesso negato a questo shop");
  }
}