<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
// Le CEC ET la Qualité (Marcelle) peuvent affecter
requireRole(['cec','qualite']);
$user = getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = intval($_POST['reclamation_id'] ?? 0);
    $dept = trim($_POST['departement'] ?? '');

    if ($id && $dept) {
        // 1) Affectation
        $pdo->prepare("UPDATE reclamations SET departement_assigne=?, statut='Affectee', cec_id=?, date_affectation=NOW() WHERE id=?")
            ->execute([$dept, $user['id'], $id]);
        $pdo->prepare("INSERT INTO traitements (reclamation_id,utilisateur_id,action,commentaire) VALUES (?,?,?,?)")
            ->execute([$id,$user['id'],'Affectation',"Reclamation affectee au departement : $dept"]);

        // 2) Infos de la réclamation pour le mail
        $r = $pdo->prepare("SELECT numero_suivi, categorie, nom_client FROM reclamations WHERE id=?");
        $r->execute([$id]);
        $rec = $r->fetch();

        // 3) Destinataires : tous les agents actifs de ce département
        //    (FIND_IN_SET gère les agents multi-départements, ex. "Informatique,Digital")
        $dst = $pdo->prepare("SELECT email, prenom, nom FROM utilisateurs WHERE role='departement' AND actif=1 AND FIND_IN_SET(?, departement)");
        $dst->execute([$dept]);
        $destinataires = $dst->fetchAll();

        // 4) Lien direct vers la fiche (URL construite dynamiquement, marche partout)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $lien   = "$scheme://$host/reclamation.php?id=$id";

        $sujet   = '[BCEG Reclamations] Nouvelle affectation - ' . ($rec['numero_suivi'] ?? '');
        $headers = "From: BCEG Reclamations <service.clients@bceg.ga>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        foreach ($destinataires as $d) {
            $corps  = "Bonjour " . $d['prenom'] . ",\n\n";
            $corps .= "Une reclamation vient de vous etre affectee (departement : $dept).\n\n";
            $corps .= "  Numero  : " . ($rec['numero_suivi'] ?? '') . "\n";
            $corps .= "  Motif   : " . ($rec['categorie'] ?? '') . "\n";
            $corps .= "  Client  : " . ($rec['nom_client'] ?? '') . "\n";
            $corps .= "  Affectee par : " . $user['prenom'] . " " . $user['nom'] . "\n\n";
            $corps .= "Acceder a la fiche pour la traiter :\n$lien\n\n";
            $corps .= "-- Plateforme Reclamations BCEG";

            // mail() necessite un service d'envoi (SMTP/sendmail) configure sur le serveur BGFI.
            // Si non configure, l'envoi echoue silencieusement : on garde une trace en base.
            $envoye = @mail($d['email'], $sujet, $corps, $headers);

            $pdo->prepare("INSERT INTO notifications (reclamation_id, destinataire, type, message, statut) VALUES (?,?,?,?,?)")
                ->execute([$id, $d['email'], 'email', "Affectation $dept - " . ($rec['numero_suivi'] ?? ''), $envoye ? 'envoye' : 'en_attente']);
        }
    }
}

header('Location: /dashboard.php'); exit();
