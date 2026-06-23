<?php
/* =====================================================================
   BCEG — Plateforme Réclamations
   Connexion à la base de données (PDO / MySQL-MariaDB)
   ---------------------------------------------------------------------
   IMPORTANT : sur le serveur BGFI, remplace les 4 valeurs ci-dessous
   par les VRAIS identifiants fournis par Fabrice. Ne committe jamais
   les vrais identifiants dans un dépôt public.
   ===================================================================== */

define('DB_HOST', 'localhost');
define('DB_USER', 'bceg_user');
define('DB_PASS', 'bceg_password');
define('DB_NAME', 'bceg_reclamations');
define('DB_PORT', '3306');

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Erreur BDD Reclamations : ' . $e->getMessage());
    die('Connexion impossible. Contactez l administrateur.');
}
