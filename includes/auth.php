<?php
/* =====================================================================
   BCEG — Plateforme Réclamations
   Gestion des sessions et des accès
   ---------------------------------------------------------------------
   Fournit les fonctions appelées par les pages :
     estConnecte(), connecterUser($u), deconnecterUser(),
     getUser(), requireLogin(), requireRole($roles)
   ===================================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* L'utilisateur est-il connecté ? */
function estConnecte(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/* Enregistre l'utilisateur en session après un login réussi.
   On ne stocke JAMAIS le mot de passe en session. */
function connecterUser(array $u): void {
    $_SESSION['user'] = [
        'id'          => $u['id'],
        'email'       => $u['email']       ?? '',
        'prenom'      => $u['prenom']      ?? '',
        'nom'         => $u['nom']         ?? '',
        'role'        => $u['role']        ?? '',
        'departement' => $u['departement'] ?? '',
    ];
    session_regenerate_id(true); // sécurité : évite la fixation de session
}

/* Déconnexion complète puis retour à la page de login. */
function deconnecterUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: /login.php');
    exit();
}

/* Renvoie le tableau de l'utilisateur courant (ou null). */
function getUser(): ?array {
    return estConnecte() ? $_SESSION['user'] : null;
}

/* Bloque l'accès si non connecté. */
function requireLogin(): void {
    if (!estConnecte()) {
        header('Location: /login.php');
        exit();
    }
}

/* Bloque l'accès si le rôle n'est pas autorisé.
   Ex : requireRole(['cec','qualite']) */
function requireRole(array $roles): void {
    requireLogin();
    $u = getUser();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('Accès refusé : vous n\'avez pas les droits nécessaires pour cette page.');
    }
}
