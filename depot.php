<?php
require_once __DIR__ . '/config/db.php';

$CATEGORIES = [
    'Comptabilite' => ['Contestation d agios','Contestation de date de valeur','Interets non credites','Interets mal calcules','Contestation frais de forcage','Perte de TPE','Litige sur transaction TPE'],
    'Informatique' => ['Parametrage comptes BCEGMobile','Compte BCEGMobile non visible','Virement via BCEGMobile non parvenu','Demande d avis d operation','Extrait de compte non parvenu'],
    'Engagements'  => ['Main levee sur caution douaniere','Contestation des frais de dossier','Decouvert non parametre','Contestation echeance credit','Conditions particulieres non parametrees'],
    'Digital'      => ['Difficulte de connexion sur B-Online','Mot de passe B-Online oublie ou bloque','Compte B-Online inaccessible','Demande de dechargement CVP','Rechargement CVP non credite','Virement compte virtuel infructueux','Achat EDAN infructueux','Achat unites telephoniques infructueux','Retrait GAB BCEG infructueux et comptabilise','Transfert GIMAC wallet to wallet infructueux'],
    'Operations'   => ['Virement intra non parvenu','Virement bilateral non parvenu','Remise cheque non creditee','Cheque non credite','Contestation de frais','Versement guichet non credite','Operation non reconnue','Opposition carte non traitee','Operation debitee en double','Paiement TPE infructueux et comptabilise','Virement TRF international non parvenu','Rapatriement non recu'],
    'Commercial'   => ['Cloture de compte','Changement de gestionnaire','Duree de traitement de dossier','Agios trop percu'],
    'Achats et Logistique' => ['Facture impayee','Bon de commande non traite'],
    'Recouvrement et Juridique' => ['Attestation d endettement','Attestation de fin de credit','Trop percu contentieux','Mainlevee deblocage garantie'],
    'Monetique'    => ['Paiement internet VISA infructueux','Paiement TPE VISA infructueux','Retrait GAB infructueux','Paiements non reconnus','Contestation solde CVP'],
];

// Icone par domaine (affichage etape 1)
$ICONES = [
    'Comptabilite'=>'🧮','Informatique'=>'💻','Engagements'=>'📑','Digital'=>'📱',
    'Operations'=>'🔄','Commercial'=>'🤝','Achats et Logistique'=>'📦',
    'Recouvrement et Juridique'=>'⚖️','Monetique'=>'💳',
];

$AGENCES = ['Agence Okoume (Siege)','Agence Movingui','Agence Bilinga','Point Cash Tali','Point Cash Akanda','Bureau Ozigo (Port-Gentil)','Agence Azobe'];

$success = false;
$numero  = '';
$erreur  = '';

// Domaine choisi (etape 2). Valide uniquement s'il existe dans CATEGORIES.
$domaine = $_POST['domaine'] ?? $_GET['domaine'] ?? '';
if ($domaine !== '' && !isset($CATEGORIES[$domaine])) { $domaine = ''; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom_client'] ?? '');
    $tel    = trim($_POST['telephone_client'] ?? '');
    $email  = trim($_POST['email_client'] ?? '');
    $type   = trim($_POST['type_client'] ?? 'Particulier');
    $agence = trim($_POST['agence'] ?? '');
    $motif  = trim($_POST['motif'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $canal  = 'Formulaire en ligne';

    // Le motif doit appartenir au domaine choisi
    $motifValide = $domaine !== '' && in_array($motif, $CATEGORIES[$domaine], true);

    if (!$domaine) {
        $erreur = 'Veuillez choisir un domaine.';
    } elseif (!$nom || !$tel || !$motifValide || !$desc) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        $numero = 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Upload fichier
        $fichierNom = null; $fichierPath = null;
        if (!empty($_FILES['fichier']['name'])) {
            $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','jpg','jpeg','png','docx'];
            if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= 5*1024*1024) {
                $fichierNom  = $numero . '.' . $ext;
                $fichierPath = __DIR__ . '/uploads/' . $fichierNom;
                move_uploaded_file($_FILES['fichier']['tmp_name'], $fichierPath);
            }
        }

        $stmt = $pdo->prepare('INSERT INTO reclamations (numero_suivi, nom_client, telephone_client, email_client, type_client, agence, canal, departement_assigne, categorie, description, fichier_nom, fichier_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$numero, $nom, $tel, $email, $type, $agence, $canal, $domaine, $motif, $desc, $fichierNom, $fichierPath]);

        if ($email) {
            $pdo->prepare('INSERT INTO notifications (reclamation_id, destinataire, type, message, statut) VALUES (?,?,?,?,?)')->execute([
                $pdo->lastInsertId(), $email, 'email',
                "Votre reclamation $numero a bien ete enregistree. Notre equipe vous contactera dans les 48h ouvrables.", 'envoye'
            ]);
        }
        $success = true;
    }
}

// Quelle etape afficher ?
//  - succes        => ecran de confirmation
//  - pas de domaine => etape 1 (choix du domaine)
//  - domaine choisi => etape 2 (motifs + formulaire)
$etape = $success ? 'ok' : ($domaine === '' ? 1 : 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Déposer une réclamation — BCEG</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#f3f6f3,#e8ede8);min-height:100vh;}
header{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:20px 32px;}
header h1{font-size:22px;font-weight:800;letter-spacing:2px;}
header p{font-size:12px;color:rgba(255,255,255,0.6);margin-top:2px;}
.container{max-width:680px;margin:24px auto;padding:0 16px 60px;}
.steps{display:flex;align-items:center;gap:8px;margin-bottom:18px;font-size:12px;font-weight:700;color:#9aa39a;}
.steps .on{color:#c0622a;}
.steps .bar{flex:1;height:3px;border-radius:3px;background:#dfe5df;}
.steps .bar.on{background:#c0622a;}
.intro{background:white;border-left:6px solid #c0622a;border-radius:14px;padding:20px 24px;margin-bottom:20px;box-shadow:0 4px 16px rgba(0,0,0,0.07);}
.intro h2{color:#c0622a;font-size:18px;font-weight:800;margin-bottom:6px;}
.intro p{font-size:13px;color:#666;line-height:1.6;}
.card{background:white;border-radius:14px;padding:24px;margin-bottom:16px;box-shadow:0 4px 16px rgba(0,0,0,0.07);}
.section-titre{font-size:13px;font-weight:800;color:#4d553d;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #e8ede8;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:600px){.grid2{grid-template-columns:1fr;}}
label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;}
input,select,textarea{width:100%;padding:13px 16px;border:2px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;transition:border-color 0.2s;}
input:focus,select:focus,textarea:focus{outline:none;border-color:#4d553d;}
textarea{resize:vertical;min-height:120px;}
.form-group{margin-bottom:16px;}
.btn{width:100%;padding:16px;background:linear-gradient(135deg,#c0622a,#e07b39);color:white;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;}
.btn:hover{opacity:0.9;}
.erreur{background:#fde8e8;color:#c0392b;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;}
.success{text-align:center;background:white;border-radius:20px;padding:48px 24px;box-shadow:0 8px 32px rgba(0,0,0,0.1);}
.success .icon{font-size:72px;margin-bottom:20px;}
.success h2{font-size:26px;font-weight:900;color:#4d553d;margin-bottom:12px;}
.success .numero{font-size:22px;font-weight:900;color:#4d553d;background:#e8ede8;padding:14px 24px;border-radius:12px;display:inline-block;margin:16px 0;letter-spacing:2px;font-family:monospace;}
.success p{font-size:14px;color:#666;line-height:1.7;}

/* Etape 1 : grille des domaines */
.domaines{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:600px){.domaines{grid-template-columns:1fr;}}
.dom{display:flex;align-items:center;gap:14px;background:white;border:2px solid #eef1ee;border-radius:14px;padding:16px 18px;text-decoration:none;box-shadow:0 4px 14px rgba(0,0,0,0.05);transition:border-color .2s,transform .2s;}
.dom:hover{border-color:#c0622a;transform:translateY(-2px);}
.dom .ico{width:48px;height:48px;border-radius:12px;background:linear-gradient(160deg,#4d553d,#3a4130);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.dom .t{font-size:15px;font-weight:800;color:#2c2c2c;}
.dom .s{font-size:11px;color:#999;margin-top:2px;}

/* Etape 2 : liste des motifs */
.retour{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#4d553d;text-decoration:none;margin-bottom:14px;}
.motifs{display:flex;flex-direction:column;gap:8px;}
.motif{display:flex;align-items:center;gap:12px;border:2px solid #e8e8e8;border-radius:10px;padding:13px 15px;cursor:pointer;transition:border-color .15s,background .15s;}
.motif:hover{border-color:#c0c8bd;background:#fafbfa;}
.motif input{width:auto;margin:0;accent-color:#4d553d;}
.motif span{font-size:14px;color:#333;}
.motif.sel{border-color:#4d553d;background:#f3f6f3;}
.badge-dom{display:inline-flex;align-items:center;gap:8px;background:#e8ede8;color:#4d553d;font-size:13px;font-weight:800;padding:8px 14px;border-radius:999px;margin-bottom:14px;}
</style>
</head>
<body>
<header>
  <h1>BCEG</h1>
  <p>Banque pour le Commerce et l'Entrepreneuriat du Gabon</p>
</header>
<div class="container">

<?php if ($etape === 'ok'): ?>
  <div class="success">
    <div class="icon">✅</div>
    <h2>Réclamation enregistrée !</h2>
    <p>Votre numéro de suivi :</p>
    <div class="numero"><?= htmlspecialchars($numero) ?></div>
    <p>Notre équipe vous contactera dans les <strong>48 heures ouvrables</strong>.<br>Conservez ce numéro pour tout suivi.</p>
  </div>

<?php elseif ($etape === 1): ?>
  <div class="steps">
    <span class="on">1. Domaine</span><span class="bar on"></span>
    <span>2. Motif &amp; détails</span><span class="bar"></span>
  </div>
  <div class="intro">
    <h2>⚠️ Déposer une réclamation</h2>
    <p>Choisissez d'abord le <strong>domaine</strong> concerné. Vous préciserez ensuite le motif exact. Le dépôt est <strong>gratuit</strong>.</p>
  </div>
  <?php if ($erreur): ?><div class="erreur">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
  <div class="domaines">
    <?php foreach($CATEGORIES as $dom => $items): ?>
    <a class="dom" href="?domaine=<?= urlencode($dom) ?>">
      <div class="ico"><?= $ICONES[$dom] ?? '📋' ?></div>
      <div>
        <div class="t"><?= htmlspecialchars($dom) ?></div>
        <div class="s"><?= count($items) ?> motifs</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

<?php else: /* etape 2 */ ?>
  <div class="steps">
    <span>1. Domaine</span><span class="bar on"></span>
    <span class="on">2. Motif &amp; détails</span><span class="bar on"></span>
  </div>
  <a class="retour" href="depot.php">← Changer de domaine</a>
  <div class="badge-dom"><?= $ICONES[$domaine] ?? '📋' ?> <?= htmlspecialchars($domaine) ?></div>
  <?php if ($erreur): ?><div class="erreur">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="domaine" value="<?= htmlspecialchars($domaine) ?>">

    <div class="card">
      <div class="section-titre">🎯 Précisez le motif *</div>
      <div class="motifs">
        <?php foreach($CATEGORIES[$domaine] as $item): ?>
        <label class="motif">
          <input type="radio" name="motif" value="<?= htmlspecialchars($item) ?>" required <?= (($_POST['motif']??'')===$item)?'checked':'' ?>>
          <span><?= htmlspecialchars($item) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="section-titre">👤 Vos informations</div>
      <div class="grid2">
        <div class="form-group"><label>Nom complet *</label><input type="text" name="nom_client" required placeholder="ONDO Jean-Baptiste" value="<?= htmlspecialchars($_POST['nom_client']??'') ?>"></div>
        <div class="form-group"><label>Téléphone *</label><input type="tel" name="telephone_client" required placeholder="06 12 34 56" value="<?= htmlspecialchars($_POST['telephone_client']??'') ?>"></div>
      </div>
      <div class="grid2">
        <div class="form-group"><label>Email (optionnel)</label><input type="email" name="email_client" placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email_client']??'') ?>"></div>
        <div class="form-group"><label>Type de client</label>
          <select name="type_client">
            <?php foreach(['Particulier','Entreprise inf. 500 MF','Entreprise sup. 500 MF','Institutionnel','Fournisseur'] as $t): ?>
            <option <?= (($_POST['type_client']??'Particulier')===$t)?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Agence concernée</label>
        <select name="agence">
          <option value="">-- Sélectionnez --</option>
          <?php foreach($AGENCES as $a): ?><option <?= (($_POST['agence']??'')===$a)?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="section-titre">📝 Description</div>
      <div class="form-group"><label>Description détaillée *</label><textarea name="description" required placeholder="Décrivez précisément votre problème : date, montant, numéro d'opération..."><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
      <div class="form-group"><label>Document justificatif (optionnel — PDF, image, max 5 Mo)</label><input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png,.docx"></div>
    </div>

    <button type="submit" class="btn">Envoyer ma réclamation →</button>
  </form>

  <script>
  // surligne le motif choisi
  document.querySelectorAll('.motif input').forEach(function(r){
    r.addEventListener('change',function(){
      document.querySelectorAll('.motif').forEach(m=>m.classList.remove('sel'));
      if(r.checked) r.closest('.motif').classList.add('sel');
    });
    if(r.checked) r.closest('.motif').classList.add('sel');
  });
  </script>
<?php endif; ?>

</div>
</body>
</html>
