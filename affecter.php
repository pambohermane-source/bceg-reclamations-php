<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requireRole('cec');
$user = getUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = intval($_POST['reclamation_id'] ?? 0);
    $dept = trim($_POST['departement'] ?? '');
    if ($id && $dept) {
        $pdo->prepare("UPDATE reclamations SET departement_assigne=?, statut='Affectee', cec_id=?, date_affectation=NOW() WHERE id=?")->execute([$dept, $user['id'], $id]);
        $pdo->prepare("INSERT INTO traitements (reclamation_id,utilisateur_id,action,commentaire) VALUES (?,?,?,?)")->execute([$id,$user['id'],'Affectation',"Reclamation affectee au departement : $dept"]);
    }
}
header('Location: /dashboard.php'); exit();
