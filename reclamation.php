<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requireLogin();
$user = getUser();
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /dashboard.php'); exit(); }

$stmt = $pdo->prepare('SELECT * FROM reclamations WHERE id = ?');
$stmt->execute([$id]);
$rec = $stmt->fetch();
if (!$rec) { header('Location: /dashboard.php'); exit(); }

$historique = $pdo->prepare('SELECT t.*, u.prenom, u.nom, u.role FROM traitements t LEFT JOIN utilisateurs u ON u.id = t.utilisateur_id WHERE t.reclamation_id = ? ORDER BY t.date_action ASC');
$historique->execute([$id]);
$historique = $historique->fetchAll();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $commentaire = trim($_POST['commentaire'] ?? '');
    $nouveauStatut = $action === 'traiter' ? 'En traitement' : ($action === 'cloturer' ? 'Cloturee' : ($action === 'rejeter' ? 'Rejetee' : $rec['statut']));

    $fichierNom = null; $fichierPath = null;
    if (!empty($_FILES['fichier']['name'])) {
        $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','jpg','jpeg','png','docx']) && $_FILES['fichier']['size'] <= 5*1024*1024) {
            $fichierNom  = $rec['numero_suivi'] . '_' . time() . '.' . $ext;
            $fichierPath = __DIR__ . '/uploads/' . $fichierNom;
            move_uploaded_file($_FILES['fichier']['tmp_name'], $fichierPath);
        }
    }
    $pdo->prepare("UPDATE reclamations SET statut=?, date_traitement=NOW() WHERE id=?")->execute([$nouveauStatut, $id]);
    $pdo->prepare("INSERT INTO traitements (reclamation_id,utilisateur_id,action,commentaire,fichier_nom,fichier_path) VALUES (?,?,?,?,?,?)")->execute([$id,$user['id'],$action,$commentaire,$fichierNom,$fichierPath]);
    $rec['statut'] = $nouveauStatut;
    $message = 'Action enregistree avec succes.';
}

function badge($s){$c=['Nouvelle'=>['#DBEAFE','#1D4ED8'],'Affectee'=>['#FEF9C3','#7B5800'],'En traitement'=>['#FFF3CD','#856404'],'Resolue'=>['#DCFCE7','#166534'],'Cloturee'=>['#DCFCE7','#166534'],'Rejetee'=>['#FEE2E2','#991B1B']];$x=$c[$s]??['#F3F4F6','#374151'];return "<span style='background:{$x[0]};color:{$x[1]};padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>$s</span>";}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réclamation <?= htmlspecialchars($rec['numero_suivi']) ?> — BCEG</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7f5;color:#2c2c2c;}
header{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:16px 28px;display:flex;align-items:center;gap:16px;}
header a{color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;}
header a:hover{color:white;}
header h1{font-size:18px;font-weight:800;}
.container{max-width:900px;margin:24px auto;padding:0 20px 60px;}
.card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.06);margin-bottom:16px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:600px){.grid2{grid-template-columns:1fr;}}
.field{margin-bottom:12px;}
.field label{display:block;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;}
.field .val{font-size:14px;color:#2c2c2c;}
.timeline{padding:0;list-style:none;}
.timeline li{display:flex;gap:14px;padding:12px 0;border-bottom:1px solid #f0f0f0;}
.timeline li:last-child{border-bottom:none;}
.tl-dot{width:36px;height:36px;border-radius:50%;background:#4d553d;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;}
.tl-info{flex:1;}
.tl-info .who{font-size:13px;font-weight:700;color:#2c2c2c;}
.tl-info .what{font-size:12px;color:#888;margin-top:2px;}
.tl-info .comment{font-size:13px;color:#555;margin-top:6px;background:#f8f8f8;padding:8px 12px;border-radius:8px;}
textarea{width:100%;padding:12px;border:2px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;resize:vertical;min-height:100px;}
textarea:focus{outline:none;border-color:#4d553d;}
.btn{padding:12px 24px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;}
.btn-vert{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;}
.btn-bleu{background:#2980b9;color:white;}
.btn-rouge{background:#e74c3c;color:white;}
.success-msg{background:#e8ede8;color:#4d553d;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;font-weight:600;}
</style>
</head>
<body>
<header>
  <a href="/dashboard.php">← Retour</a>
  <h1>Réclamation <?= htmlspecialchars($rec['numero_suivi']) ?></h1>
  <?= badge($rec['statut']) ?>
</header>
<div class="container">
  <?php if ($message): ?><div class="success-msg">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>

  <div class="grid2">
    <div class="card">
      <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:16px;">👤 Client</h3>
      <div class="field"><label>Nom</label><div class="val"><?= htmlspecialchars($rec['nom_client']) ?></div></div>
      <div class="field"><label>Téléphone</label><div class="val"><?= htmlspecialchars($rec['telephone_client']?:'—') ?></div></div>
      <div class="field"><label>Email</label><div class="val"><?= htmlspecialchars($rec['email_client']?:'—') ?></div></div>
      <div class="field"><label>Type</label><div class="val"><?= htmlspecialchars($rec['type_client']) ?></div></div>
    </div>
    <div class="card">
      <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:16px;">📋 Réclamation</h3>
      <div class="field"><label>Catégorie</label><div class="val"><?= htmlspecialchars($rec['categorie']) ?></div></div>
      <div class="field"><label>Agence</label><div class="val"><?= htmlspecialchars($rec['agence']?:'—') ?></div></div>
      <div class="field"><label>Département assigné</label><div class="val"><?= htmlspecialchars($rec['departement_assigne']?:'Non affecté') ?></div></div>
      <div class="field"><label>Date de réception</label><div class="val"><?= date('d/m/Y H:i', strtotime($rec['date_reception'])) ?></div></div>
    </div>
  </div>

  <div class="card">
    <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:12px;">📝 Description</h3>
    <p style="font-size:14px;color:#555;line-height:1.7;"><?= nl2br(htmlspecialchars($rec['description'])) ?></p>
  </div>

  <!-- ACTIONS -->
  <?php if ($user['role'] === 'departement' && in_array($rec['statut'], ['Affectee','En traitement','Complement requis'])): ?>
  <div class="card">
    <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:16px;">⚡ Traiter la réclamation</h3>
    <form method="POST" enctype="multipart/form-data">
      <div style="margin-bottom:14px;"><label style="display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;">Commentaire / Réponse *</label><textarea name="commentaire" required placeholder="Décrivez le traitement effectué..."></textarea></div>
      <div style="margin-bottom:14px;"><label style="display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;">Justificatif (optionnel)</label><input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png,.docx"></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button type="submit" name="action" value="traiter" class="btn btn-bleu">Marquer En traitement</button>
        <button type="submit" name="action" value="resoudre" class="btn btn-vert">Marquer Résolue</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($user['role'] === 'qualite' && in_array($rec['statut'], ['Resolue','En traitement'])): ?>
  <div class="card">
    <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:16px;">✅ Clôturer la réclamation</h3>
    <form method="POST">
      <div style="margin-bottom:14px;"><textarea name="commentaire" placeholder="Commentaire de clôture..."></textarea></div>
      <div style="display:flex;gap:10px;">
        <button type="submit" name="action" value="cloturer" class="btn btn-vert">Clôturer</button>
        <button type="submit" name="action" value="rejeter" class="btn btn-rouge">Rejeter</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- HISTORIQUE -->
  <div class="card">
    <h3 style="font-size:15px;font-weight:800;color:#4d553d;margin-bottom:16px;">🕐 Historique</h3>
    <?php if (empty($historique)): ?>
    <p style="color:#aaa;font-size:13px;">Aucune action pour le moment</p>
    <?php else: ?>
    <ul class="timeline">
      <?php foreach ($historique as $h): ?>
      <li>
        <div class="tl-dot"><?= strtoupper(substr($h['prenom'],0,1)) ?></div>
        <div class="tl-info">
          <div class="who"><?= htmlspecialchars($h['prenom'].' '.$h['nom']) ?> <span style="font-weight:400;color:#888;">— <?= htmlspecialchars($h['action']) ?></span></div>
          <div class="what"><?= date('d/m/Y à H:i', strtotime($h['date_action'])) ?></div>
          <?php if ($h['commentaire']): ?><div class="comment"><?= nl2br(htmlspecialchars($h['commentaire'])) ?></div><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
