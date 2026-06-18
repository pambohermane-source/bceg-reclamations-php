<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requireRole(['qualite','direction']);

// Stats generales
$total     = $pdo->query('SELECT COUNT(*) FROM reclamations')->fetchColumn();
$nouvelles = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut='Nouvelle'")->fetchColumn();
$cloturees = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut IN ('Cloturee','Resolue')")->fetchColumn();
$rejetees  = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut='Rejetee'")->fetchColumn();

// Par departement
$parDept = $pdo->query("SELECT departement_assigne, COUNT(*) as total, SUM(statut IN ('Cloturee','Resolue')) as resolues FROM reclamations WHERE departement_assigne IS NOT NULL GROUP BY departement_assigne ORDER BY total DESC")->fetchAll();

// Par mois (6 derniers mois)
$parMois = $pdo->query("SELECT DATE_FORMAT(date_reception,'%Y-%m') as mois, COUNT(*) as total FROM reclamations WHERE date_reception >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mois ORDER BY mois ASC")->fetchAll();

// Delai moyen traitement (jours)
$delaiMoyen = $pdo->query("SELECT ROUND(AVG(DATEDIFF(date_cloture, date_reception)),1) as delai FROM reclamations WHERE date_cloture IS NOT NULL")->fetchColumn();

// Export CSV
if (isset($_GET['export'])) {
    $rows = $pdo->query('SELECT numero_suivi, nom_client, telephone_client, email_client, agence, departement_assigne, categorie, statut, date_reception, date_cloture FROM reclamations ORDER BY date_reception DESC')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="BCEG_Reclamations_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo "N Suivi,Client,Telephone,Email,Agence,Departement,Categorie,Statut,Date Reception,Date Cloture\n";
    foreach ($rows as $r) {
        echo implode(',', array_map(function($v){ return '"'.str_replace('"','""',$v).'"'; }, $r)) . "\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistiques — BCEG Réclamations</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7f5;color:#2c2c2c;}
header{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
header .logo{font-size:20px;font-weight:800;letter-spacing:2px;}
.nav{background:#3a4130;display:flex;gap:4px;padding:0 28px;}
.nav a{padding:12px 16px;color:rgba(255,255,255,0.5);text-decoration:none;font-size:13px;font-weight:600;border-bottom:3px solid transparent;}
.nav a:hover,.nav a.active{color:white;border-bottom-color:#a6c47a;}
.container{max-width:1200px;margin:0 auto;padding:24px 20px 60px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.kpi{background:white;border-radius:14px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);border-top:5px solid #4d553d;}
.kpi.rouge{border-top-color:#e74c3c;}
.kpi.vert{border-top-color:#27ae60;}
.kpi.orange{border-top-color:#f39c12;}
.kpi .val{font-size:42px;font-weight:900;color:#4d553d;}
.kpi.rouge .val{color:#e74c3c;}
.kpi.vert .val{color:#27ae60;}
.kpi.orange .val{color:#f39c12;}
.kpi .lbl{font-size:13px;color:#888;margin-top:6px;}
.kpi .sub{font-size:11px;color:#aaa;margin-top:4px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:800px){.grid2{grid-template-columns:1fr;}}
.card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.card-hdr h3{font-size:15px;font-weight:800;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:10px 14px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;border-bottom:2px solid #f0f0f0;}
td{padding:12px 14px;font-size:13px;border-bottom:1px solid #f5f5f5;}
.progress{height:8px;background:#f0f0f0;border-radius:8px;overflow:hidden;margin-top:6px;}
.progress-bar{height:8px;background:linear-gradient(90deg,#4d553d,#a6c47a);border-radius:8px;}
.export-btn{background:linear-gradient(135deg,#4d553d,#3a4130);color:white;padding:10px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;}
</style>
</head>
<body>
<header>
  <div class="logo">BCEG — Statistiques Réclamations</div>
  <a href="/statistiques.php?export=1" class="export-btn">⬇️ Exporter CSV</a>
</header>
<div class="nav">
  <a href="/dashboard.php">📋 Réclamations</a>
  <a href="/statistiques.php" class="active">📊 Statistiques</a>
  <a href="/compte.php">🔑 Mon compte</a>
</div>
<div class="container">
  <div class="kpi-grid">
    <div class="kpi"><div class="val"><?= $total ?></div><div class="lbl">📋 Total réclamations</div></div>
    <div class="kpi rouge"><div class="val"><?= $nouvelles ?></div><div class="lbl">🔴 En attente de traitement</div></div>
    <div class="kpi vert"><div class="val"><?= $cloturees ?></div><div class="lbl">🟢 Clôturées / Résolues</div></div>
    <div class="kpi orange"><div class="val"><?= $delaiMoyen ?: '—' ?></div><div class="lbl">⏱️ Jours moyen de traitement</div><div class="sub">Délai COBAC : 45 jours max</div></div>
  </div>

  <div class="grid2">
    <!-- PAR DEPARTEMENT -->
    <div class="card">
      <div class="card-hdr"><h3>🏢 Par département</h3></div>
      <?php if (empty($parDept)): ?>
      <p style="color:#aaa;font-size:13px;">Aucune donnée</p>
      <?php else: ?>
      <table>
        <thead><tr><th>Département</th><th>Total</th><th>Résolues</th></tr></thead>
        <tbody>
          <?php foreach ($parDept as $d): $pct = $d['total'] > 0 ? round($d['resolues']/$d['total']*100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars($d['departement_assigne']) ?><div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></td>
            <td><?= $d['total'] ?></td>
            <td><?= $d['resolues'] ?> (<?= $pct ?>%)</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- PAR MOIS -->
    <div class="card">
      <div class="card-hdr"><h3>📅 Évolution mensuelle</h3></div>
      <?php if (empty($parMois)): ?>
      <p style="color:#aaa;font-size:13px;">Aucune donnée</p>
      <?php else: ?>
      <?php $maxMois = max(array_column($parMois, 'total')) ?: 1; ?>
      <?php foreach ($parMois as $m): ?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:70px;font-size:12px;color:#888;"><?= $m['mois'] ?></div>
        <div style="flex:1;"><div class="progress"><div class="progress-bar" style="width:<?= round($m['total']/$maxMois*100) ?>%"></div></div></div>
        <div style="width:30px;font-size:13px;font-weight:700;color:#4d553d;"><?= $m['total'] ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
