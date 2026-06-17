<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (estConnecte()) { header('Location: /dashboard.php'); exit(); }

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');
    if ($email && $mdp) {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            connecterUser($user);
            $pdo->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')->execute([$user['id']]);
            header('Location: /dashboard.php');
            exit();
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    } else {
        $erreur = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — BCEG Réclamations</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#f3f6f3,#e8ede8);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:white;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 12px 48px rgba(77,85,61,0.15);}
.logo{text-align:center;margin-bottom:32px;}
.logo h1{font-size:36px;font-weight:900;color:#4d553d;letter-spacing:3px;}
.logo p{font-size:12px;color:#aaa;margin-top:4px;}
.titre{font-size:22px;font-weight:800;color:#2c2c2c;margin-bottom:6px;}
.sous-titre{font-size:13px;color:#888;margin-bottom:28px;}
label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
input{width:100%;padding:13px 16px;border:2px solid #e8e8e8;border-radius:10px;font-size:15px;font-family:inherit;margin-bottom:16px;transition:border-color 0.2s;}
input:focus{outline:none;border-color:#4d553d;}
.btn{width:100%;padding:15px;background:linear-gradient(135deg,#4d553d,#3a4130);color:white;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;letter-spacing:0.3px;}
.btn:hover{opacity:0.9;}
.erreur{background:#fde8e8;color:#c0392b;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;border:1px solid #f4ceca;}
.reclamation-link{text-align:center;margin-top:20px;font-size:13px;color:#888;}
.reclamation-link a{color:#c0622a;font-weight:700;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>BCEG</h1>
    <p>Banque pour le Commerce et l'Entrepreneuriat du Gabon</p>
  </div>
  <div class="titre">Connexion</div>
  <div class="sous-titre">Espace agents — Gestion des réclamations</div>
  <?php if ($erreur): ?>
  <div class="erreur">⚠️ <?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" placeholder="votre@bceg.ga" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>Mot de passe</label>
    <input type="password" name="mot_de_passe" placeholder="••••••••" required>
    <button type="submit" class="btn">Se connecter</button>
  </form>
  <div class="reclamation-link">
    <a href="/depot.php">⚠️ Déposer une réclamation client</a>
  </div>
</div>
</body>
</html>
