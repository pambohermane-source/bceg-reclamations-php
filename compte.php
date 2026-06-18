<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requireLogin();
$user = getUser();

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actuel  = $_POST['actuel'] ?? '';
    $nouveau = $_POST['nouveau'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Récupère le hash actuel
    $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmt->execute([$user['id']]);
    $hash = $stmt->fetchColumn();

    if (!$actuel || !$nouveau || !$confirm) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!$hash || !password_verify($actuel, $hash)) {
        $erreur = 'Le mot de passe actuel est incorrect.';
    } elseif (strlen($nouveau) < 8) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($nouveau !== $confirm) {
        $erreur = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } else {
        $nouveauHash = password_hash($nouveau, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')->execute([$nouveauHash, $user['id']]);
        $message = 'Votre mot de passe a été modifié avec succès.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon compte — BCEG Réclamations</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7f5;color:#2c2c2c;}
header{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
header .logo{font-size:20px;font-weight:800;letter-spacing:2px;}
header a{color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;}
header a:hover{color:white;}
.container{max-width:560px;margin:24px auto;padding:0 20px 60px;}
.card{background:white;border-radius:14px;padding:26px;box-shadow:0 2px 10px rgba(0,0,0,0.06);margin-bottom:16px;}
.card h3{font-size:16px;font-weight:800;color:#4d553d;margin-bottom:6px;}
.card .sub{font-size:13px;color:#888;margin-bottom:20px;}
label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
input{width:100%;padding:13px 16px;border:2px solid #e8e8e8;border-radius:10px;font-size:15px;font-family:inherit;margin-bottom:16px;transition:border-color 0.2s;}
input:focus{outline:none;border-color:#4d553d;}
.btn{width:100%;padding:15px;background:linear-gradient(135deg,#4d553d,#3a4130);color:white;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;}
.btn:hover{opacity:0.9;}
.erreur{background:#fde8e8;color:#c0392b;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;border:1px solid #f4ceca;}
.ok{background:#dcfce7;color:#166534;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;border:1px solid #bbf7d0;font-weight:600;}
.infos{font-size:13px;color:#666;line-height:1.8;}
.infos strong{color:#2c2c2c;}
.hint{font-size:11px;color:#aaa;margin:-10px 0 16px;}
</style>
</head>
<body>
<header>
  <div class="logo">BCEG</div>
  <a href="/dashboard.php">← Retour au tableau de bord</a>
</header>
<div class="container">

  <div class="card">
    <h3>👤 Mon compte</h3>
    <div class="infos">
      <div><strong><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></strong></div>
      <div><?= htmlspecialchars($user['email']) ?></div>
      <div>Rôle : <strong><?= htmlspecialchars(ucfirst($user['role'])) ?></strong><?= $user['departement'] ? ' — '.htmlspecialchars($user['departement']) : '' ?></div>
    </div>
  </div>

  <div class="card">
    <h3>🔑 Changer mon mot de passe</h3>
    <div class="sub">Choisissez un mot de passe d'au moins 8 caractères.</div>

    <?php if ($message): ?><div class="ok">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="erreur">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <form method="POST">
      <label>Mot de passe actuel</label>
      <input type="password" name="actuel" required placeholder="••••••••">
      <label>Nouveau mot de passe</label>
      <input type="password" name="nouveau" required placeholder="••••••••">
      <div class="hint">Minimum 8 caractères.</div>
      <label>Confirmer le nouveau mot de passe</label>
      <input type="password" name="confirm" required placeholder="••••••••">
      <button type="submit" class="btn">Mettre à jour</button>
    </form>
  </div>

</div>
</body>
</html>
