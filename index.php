<?php
session_start();
if (!isset($_SESSION['logged'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Filtre date
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin']   ?? '';

// Stats
$total  = $pdo->query("SELECT COUNT(*) FROM ACCESS")->fetchColumn();
$ok     = $pdo->query("SELECT COUNT(*) FROM ACCESS WHERE access = 1")->fetchColumn();
$ko     = $pdo->query("SELECT COUNT(*) FROM ACCESS WHERE access = 0")->fetchColumn();
$alerts = $pdo->query("SELECT COUNT(*) FROM ALERTS")->fetchColumn();

// Logs avec filtre
if ($date_debut && $date_fin) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nom, u.prenom
        FROM ACCESS a
        LEFT JOIN USERS u ON u.uid = a.uid
        WHERE a.date BETWEEN ? AND ?
        ORDER BY a.date DESC LIMIT 100
    ");
    $stmt->execute([$date_debut . ' 00:00:00', $date_fin . ' 23:59:59']);
    $logs = $stmt->fetchAll();
} else {
    $logs = $pdo->query("
        SELECT a.*, u.nom, u.prenom
        FROM ACCESS a
        LEFT JOIN USERS u ON u.uid = a.uid
        ORDER BY a.date DESC LIMIT 100
    ")->fetchAll();
}

$users   = $pdo->query("SELECT * FROM USERS ORDER BY nom")->fetchAll();
$alertes = $pdo->query("SELECT a.*, ac.access, ac.uid, u.nom, u.prenom FROM ALERTS a LEFT JOIN ACCESS ac ON ac.id_access = a.id_access LEFT JOIN USERS u ON u.uid = ac.uid ORDER BY a.date DESC LIMIT 50")->fetchAll();

// Historique par utilisateur
$histo_user = $_GET['histo_user'] ?? '';
$histo = [];
if ($histo_user) {
    $stmt = $pdo->prepare("SELECT a.date, a.access, a.uid FROM ACCESS a LEFT JOIN USERS u ON u.uid = a.uid WHERE u.uid = ? ORDER BY a.date DESC LIMIT 50");
    $stmt->execute([$histo_user]);
    $histo = $stmt->fetchAll();
}

// Ajout utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    $stmt = $pdo->prepare("INSERT INTO USERS (nom, prenom, groups, uid) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['groups'] ?? null, $_POST['uid'] ?? null]);
    header('Location: index.php?tab=users');
    exit;
}

// Suppression utilisateur
if (isset($_GET['del_user'])) {
    $pdo->prepare("DELETE FROM USERS WHERE id_users = ?")->execute([$_GET['del_user']]);
    header('Location: index.php?tab=users');
    exit;
}

// Suppression log
if (isset($_GET['del_log'])) {
    $pdo->prepare("DELETE FROM ACCESS WHERE id_acces = ?")->execute([$_GET['del_log']]);
    header('Location: index.php?tab=logs');
    exit;
}

$tab = $_GET['tab'] ?? 'logs';
?>
<!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8">
<title>Dashboard RFID</title>
<style>
  body { font-family: sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
  h1 { margin-bottom: 0.5rem; }
  .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
  .logout { font-size: 13px; color: #c00; text-decoration: none; }
  .tabs { display: flex; gap: 8px; margin-bottom: 1.5rem; }
  .tab { padding: 7px 18px; border: 1px solid #ccc; border-radius: 6px;
         background: #eee; color: #333; font-size: 14px; text-decoration: none; }
  .tab.on { background: #333; color: #fff; border-color: #333; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
  th { background: #f5f5f5; font-weight: 500; }
  tr:hover td { background: #fafafa; }
  .ok { color: #00c41c; font-weight: 500; }
  .ko { color: #ff0000; font-weight: 500; }
  form { display: flex; gap: 8px; margin: 1rem 0; flex-wrap: wrap; align-items: center; }
  input { padding: 6px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
  .btn { padding: 6px 14px; border-radius: 6px; border: none;
         background: #333; color: #fff; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
  .btn-blue { background: #1565c0; }
  .btn-sm { font-size: 12px; padding: 4px 10px; }
  .del { background: #c00; color: #fff; padding: 4px 10px;
         border-radius: 4px; font-size: 13px; text-decoration: none; }
  .stats { display: flex; gap: 12px; margin-bottom: 1.5rem; }
  .stat { flex: 1; background: #f5f5f5; border-radius: 8px; padding: 1rem; text-align: center; }
  .stat span { display: block; font-size: 2rem; font-weight: 600; }
  .stat label { font-size: 13px; color: #666; }
  .col-action { text-align: right; width: 100px; }
  .histo-box { margin-top: 2rem; }
  .histo-box h2 { font-size: 1rem; margin-bottom: 0.8rem; }
  .uid { font-family: monospace; font-size: 12px; color: #666; }
  .filtre { display: flex; gap: 8px; align-items: center; margin-bottom: 1rem; font-size: 13px; color: #666; }
  .filtre input[type=date] { padding: 4px 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 6px; }
  .filtre .btn-sm { background: #555; }
  .filtre a.btn-sm { background: #999; }
</style>
</head><body>

<div class="topbar">
  <h1>Dashboard RFID</h1>
  <a class="logout" href="logout.php">Déconnexion</a>
</div>

<div class="stats">
  <div class="stat"><span><?= $total ?></span><label>Passages total</label></div>
  <div class="stat"><span style="color:#16a34a"><?= $ok ?></span><label>Autorisés</label></div>
  <div class="stat"><span style="color:#dc2626"><?= $ko ?></span><label>Refusés</label></div>
  <div class="stat"><span><?= $alerts ?></span><label>Alertes</label></div>
</div>

<div class="tabs">
  <a class="tab <?= $tab==='logs'   ? 'on' : '' ?>" href="?tab=logs">Logs accès</a>
  <a class="tab <?= $tab==='users'  ? 'on' : '' ?>" href="?tab=users">Utilisateurs</a>
  <a class="tab <?= $tab==='alerts' ? 'on' : '' ?>" href="?tab=alerts">Alertes</a>
</div>

<?php if ($tab === 'logs'): ?>

<form method="GET" action="" class="filtre">
  <input type="hidden" name="tab" value="logs" />
  <span>Du</span>
  <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>" />
  <span>au</span>
  <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>" />
  <button class="btn btn-sm" type="submit">Filtrer</button>
  <?php if ($date_debut): ?>
    <a class="btn btn-sm" href="?tab=logs">Effacer</a>
  <?php endif; ?>
</form>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>UID badge</th>
      <th>Utilisateur</th>
      <th>Accès</th>
      <th class="col-action">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($logs as $log): ?>
    <tr>
      <td><?= htmlspecialchars($log['date']) ?></td>
      <td class="uid"><?= htmlspecialchars($log['uid'] ?? '—') ?></td>
      <td>
        <?php if ($log['nom']): ?>
          <?= htmlspecialchars($log['nom'] . ' ' . $log['prenom']) ?>
        <?php else: ?>
          <span style="color:#999">Inconnu</span>
        <?php endif; ?>
      </td>
      <td class="<?= $log['access'] == 1 ? 'ok' : 'ko' ?>">
        <?= $log['access'] == 1 ? 'Autorisé' : 'Refusé' ?>
      </td>
      <td class="col-action">
        <a class="del" href="?del_log=<?= $log['id_acces'] ?>&tab=logs">Supprimer</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($logs)): ?>
      <tr><td colspan="5" style="text-align:center;color:#999">Aucun résultat</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php elseif ($tab === 'users'): ?>
<form method="POST" action="?tab=users">
  <input name="nom"    placeholder="Nom"       required />
  <input name="prenom" placeholder="Prénom"    required />
  <input name="groups" placeholder="ID groupe" type="number" />
  <input name="uid"    placeholder="UID badge" />
  <button class="btn" type="submit">Ajouter</button>
</form>

<table>
  <thead>
    <tr>
      <th>Nom</th>
      <th>Prénom</th>
      <th>Groupe</th>
      <th>UID badge</th>
      <th>Historique</th>
      <th class="col-action">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['nom']) ?></td>
      <td><?= htmlspecialchars($u['prenom']) ?></td>
      <td><?= htmlspecialchars($u['groups'] ?? '—') ?></td>
      <td class="uid"><?= htmlspecialchars($u['uid'] ?? '—') ?></td>
      <td>
        <?php if ($u['uid']): ?>
          <a class="btn btn-blue btn-sm" href="?tab=users&histo_user=<?= urlencode($u['uid']) ?>">Voir</a>
        <?php else: ?>
          <span style="color:#999;font-size:13px">—</span>
        <?php endif; ?>
      </td>
      <td class="col-action">
        <a class="del" href="?del_user=<?= $u['id_users'] ?>&tab=users">Supprimer</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if ($histo_user && !empty($histo)): ?>
<div class="histo-box">
  <h2>Historique pour UID : <span class="uid"><?= htmlspecialchars($histo_user) ?></span></h2>
  <table>
    <thead><tr><th>Date</th><th>Accès</th></tr></thead>
    <tbody>
      <?php foreach ($histo as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['date']) ?></td>
        <td class="<?= $h['access'] == 1 ? 'ok' : 'ko' ?>">
          <?= $h['access'] == 1 ? 'Autorisé' : 'Refusé' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php elseif ($histo_user): ?>
  <p style="margin-top:1rem;color:#999">Aucun historique pour cet utilisateur.</p>
<?php endif; ?>

<?php elseif ($tab === 'alerts'): ?>
<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>UID badge</th>
      <th>Utilisateur</th>
      <th>Statut</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($alertes as $a): ?>
    <tr>
      <td><?= htmlspecialchars($a['date']) ?></td>
      <td class="uid"><?= htmlspecialchars($a['uid'] ?? '—') ?></td>
      <td>
        <?php if (!empty($a['nom'])): ?>
          <?= htmlspecialchars($a['nom'] . ' ' . $a['prenom']) ?>
        <?php else: ?>
          <span style="color:#999">Inconnu</span>
        <?php endif; ?>
      </td>
      <td class="<?= $a['access'] == 1 ? 'ok' : 'ko' ?>">
        <?= $a['access'] == 1 ? 'Autorisé' : 'Refusé' ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

</body></html>
