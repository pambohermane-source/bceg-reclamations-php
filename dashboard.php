<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requireLogin();

$user = getUser();
$role = $user['role'];
$dept = $user['departement'];

// Stats globales
$totalRec    = $pdo->query('SELECT COUNT(*) FROM reclamations')->fetchColumn();
$nouvelles   = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'Nouvelle'")->fetchColumn();
$enCours     = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut IN ('Affectee','En traitement')")->fetchColumn();
$cloturees   = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut IN ('Cloturee','Resolue')")->fetchColumn();

// Reclamations selon le role
if ($role === 'cec' || $role === 'qualite' || $role === 'direction') {
    $stmt = $pdo->query('SELECT r.*, u.prenom as cec_prenom, u.nom as cec_nom FROM reclamations r LEFT JOIN utilisateurs u ON u.id = r.cec_id ORDER BY r.date_reception DESC LIMIT 50');
} elseif ($role === 'departement') {
    $depts = explode(',', $dept);
    $placeholders = implode(',', array_fill(0, count($depts), '?'));
    $stmt = $pdo->prepare("SELECT r.* FROM reclamations r WHERE r.departement_assigne IN ($placeholders) ORDER BY r.date_reception DESC LIMIT 50");
    $stmt->execute($depts);
} else {
    $stmt = $pdo->query('SELECT * FROM reclamations ORDER BY date_reception DESC LIMIT 50');
}
$reclamations = $stmt->fetchAll();

// Liste des utilisateurs departements pour affectation (CEC)
$utilisateurs_dept = [];
if ($role === 'cec') {
    $utilisateurs_dept = $pdo->query("SELECT id, nom, prenom, departement FROM utilisateurs WHERE role = 'departement' AND actif = 1 ORDER BY departement")->fetchAll();
}

function badgeStatut($s) {
    $colors = ['Nouvelle'=>['#DBEAFE','#1D4ED8'],'Affectee'=>['#FEF9C3','#7B5800'],'En traitement'=>['#FFF3CD','#856404'],'Resolue'=>['#DCFCE7','#166534'],'Cloturee'=>['#DCFCE7','#166534'],'Rejetee'=>['#FEE2E2','#991B1B'],'Complement requis'=>['#F3E8FF','#6B21A8']];
    $c = $colors[$s] ?? ['#F3F4F6','#374151'];
    return "<span style='background:{$c[0]};color:{$c[1]};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;'>$s</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — BCEG Réclamations</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7f5;color:#2c2c2c;}
header{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
header .logo{font-size:20px;font-weight:800;letter-spacing:2px;}
header .user-info{text-align:right;font-size:12px;color:rgba(255,255,255,0.7);}
header .user-info strong{display:block;font-size:14px;color:white;}
.nav{background:#3a4130;display:flex;gap:4px;padding:0 28px;}
.nav a{padding:12px 16px;color:rgba(255,255,255,0.5);text-decoration:none;font-size:13px;font-weight:600;border-bottom:3px solid transparent;}
.nav a:hover,.nav a.active{color:white;border-bottom-color:#a6c47a;}
.badge{background:#e74c3c;color:white;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:800;margin-left:4px;}
.container{max-width:1200px;margin:0 auto;padding:24px 20px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.kpi{background:white;border-radius:14px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);border-top:5px solid #4d553d;}
.kpi.orange{border-top-color:#f39c12;}
.kpi.rouge{border-top-color:#e74c3c;}
.kpi.bleu{border-top-color:#2980b9;}
.kpi .val{font-size:40px;font-weight:900;color:#4d553d;line-height:1;}
.kpi.orange .val{color:#f39c12;}
.kpi.rouge .val{color:#e74c3c;}
.kpi.bleu .val{color:#2980b9;}
.kpi .lbl{font-size:13px;color:#888;margin-top:6px;}
.card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.card-hdr h3{font-size:16px;font-weight:800;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:10px 14px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #f0f0f0;}
td{padding:12px 14px;font-size:13px;border-bottom:1px solid #f5f5f5;}
tr:hover td{background:#fafafa;}
.btn-sm{padding:6px 14px;border-radius:8px;border:none;font-size:12px;font-weight:700;cursor:pointer;}
.btn-affecter{background:#4d553d;color:white;}
.btn-traiter{background:#2980b9;color:white;}
.btn-cloturer{background:#27ae60;color:white;}
.btn-voir{background:#f5f5f5;color:#555;}
.logout{color:rgba(255,255,255,0.7);text-decoration:none;font-size:12px;}
.logout:hover{color:white;}
.role-badge{background:rgba(255,255,255,0.2);border-radius:20px;padding:3px 10px;font-size:11px;}
</style>
</head>
<body>
<header>
  <div>
    <div class="logo">BCEG</div>
    <div style="font-size:11px;color:rgba(255,255,255,0.5);margin-top:2px;">Gestion des Réclamations</div>
  </div>
  <div class="user-info">
    <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
    <span class="role-badge"><?= strtoupper($role) ?></span>
    &nbsp;<a href="/logout.php" class="logout">⎋ Déconnexion</a>
  </div>
</header>
<div class="nav">
  <a href="/dashboard.php" class="active">📋 Réclamations</a>
  <?php if (in_array($role, ['qualite','direction'])): ?><a href="/statistiques.php">📊 Statistiques</a><?php endif; ?>
  <a href="/depot.php" target="_blank">⚠️ Formulaire client</a>
  <a href="/compte.php">🔑 Mon compte</a>
</div>
<div class="container">

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi rouge"><div class="val"><?= $nouvelles ?></div><div class="lbl">🔴 À traiter</div></div>
    <div class="kpi orange"><div class="val"><?= $enCours ?></div><div class="lbl">🟡 En cours</div></div>
    <div class="kpi"><div class="val"><?= $cloturees ?></div><div class="lbl">🟢 Clôturées</div></div>
    <div class="kpi bleu"><div class="val"><?= $totalRec ?></div><div class="lbl">📋 Total</div></div>
  </div>

  <!-- TABLEAU -->
  <div class="card">
    <div class="card-hdr">
      <h3>📋 Réclamations <?= $role === 'departement' ? "— " . htmlspecialchars($dept) : '' ?></h3>
      <?php if ($role === 'cec'): ?>
      <a href="/depot.php" style="background:linear-gradient(135deg,#c0622a,#e07b39);color:white;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700;">+ Nouvelle</a>
      <?php endif; ?>
    </div>
    <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>N° Suivi</th>
          <th>Client</th>
          <th>Catégorie</th>
          <th>Agence</th>
          <th>Département</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reclamations)): ?>
        <tr><td colspan="8" style="text-align:center;color:#aaa;padding:32px;">Aucune réclamation pour le moment</td></tr>
        <?php else: ?>
        <?php foreach ($reclamations as $r): ?>
        <tr>
          <td><strong style="font-family:monospace;color:#4d553d;"><?= htmlspecialchars($r['numero_suivi']) ?></strong></td>
          <td><?= htmlspecialchars($r['nom_client']) ?><br><small style="color:#aaa;"><?= htmlspecialchars($r['telephone_client']) ?></small></td>
          <td><?= htmlspecialchars(substr($r['categorie'],0,35)) ?>...</td>
          <td><?= htmlspecialchars($r['agence'] ?: '—') ?></td>
          <td><?= htmlspecialchars($r['departement_assigne'] ?: '—') ?></td>
          <td><?= badgeStatut($r['statut']) ?></td>
          <td><?= date('d/m/Y', strtotime($r['date_reception'])) ?></td>
          <td style="white-space:nowrap;">
            <a href="/reclamation.php?id=<?= $r['id'] ?>" class="btn-sm btn-voir">Voir</a>
            <?php if (in_array($role, ['cec','qualite']) && $r['statut'] === 'Nouvelle'): ?>
            <button class="btn-sm btn-affecter" onclick="affecterModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['numero_suivi']) ?>')">Affecter</button>
            <?php endif; ?>
            <?php if ($role === 'departement' && in_array($r['statut'], ['Affectee','En traitement'])): ?>
            <a href="/reclamation.php?id=<?= $r['id'] ?>" class="btn-sm btn-traiter">Traiter</a>
            <?php endif; ?>
            <?php if ($role === 'qualite' && in_array($r['statut'], ['Resolue','En traitement'])): ?>
            <a href="/reclamation.php?id=<?= $r['id'] ?>" class="btn-sm btn-cloturer">Clôturer</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<?php if (in_array($role, ['cec','qualite'])): ?>
<!-- Modal Affectation -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:16px;padding:28px;width:100%;max-width:400px;margin:20px;">
    <h3 style="margin-bottom:20px;color:#4d553d;">Affecter la réclamation</h3>
    <form method="POST" action="/affecter.php">
      <input type="hidden" name="reclamation_id" id="rec_id">
      <p id="rec_num" style="font-size:13px;color:#888;margin-bottom:16px;"></p>
      <label style="display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:6px;text-transform:uppercase;">Département</label>
      <select name="departement" required style="width:100%;padding:12px;border:2px solid #e8e8e8;border-radius:10px;font-size:14px;margin-bottom:16px;">
        <option value="">-- Sélectionnez --</option>
        <?php foreach(['Comptabilite','Informatique','Engagements','Digital','Operations','Commercial','Achats et Logistique','Recouvrement et Juridique','Monetique'] as $d): ?>
        <option><?= $d ?></option>
        <?php endforeach; ?>
      </select>
      <div style="display:flex;gap:10px;">
        <button type="button" onclick="document.getElementById('modal').style.display='none'" style="flex:1;padding:12px;border:2px solid #e8e8e8;border-radius:10px;background:white;cursor:pointer;font-weight:700;">Annuler</button>
        <button type="submit" style="flex:2;padding:12px;background:linear-gradient(135deg,#4d553d,#3a4130);color:white;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Affecter</button>
      </div>
    </form>
  </div>
</div>
<script>
function affecterModal(id, num) {
  document.getElementById('rec_id').value = id;
  document.getElementById('rec_num').textContent = 'Réclamation : ' + num;
  document.getElementById('modal').style.display = 'flex';
}
</script>
<?php endif; ?>
</body>
</html>
