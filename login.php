<?php
session_start();

$user = 'root';
$pass = 'ValGoat1@';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['user'] === $user && $_POST['pass'] === $pass) {
        $_SESSION['logged'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
    }
}
?>
<!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8">
<title>Connexion RFID</title>
<style>
  body { font-family: sans-serif; background: #f0f2f5;
         display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .box { background: #fff; padding: 2rem; border-radius: 12px;
         border: 0.5px solid #e0e0e0; width: 340px; }
  h1 { font-size: 1.3rem; margin-bottom: 1.5rem; text-align: center; }
  label { font-size: 13px; color: #666; display: block; margin-bottom: 4px; }
  input { width: 100%; padding: 8px 12px; border: 1px solid #ddd;
          border-radius: 8px; font-size: 14px; margin-bottom: 1rem; }
  button { width: 100%; padding: 10px; background: #1565c0;
           color: #fff; border: none; border-radius: 8px;
           font-size: 15px; cursor: pointer; }
  .error { color: #c62828; font-size: 13px; margin-bottom: 1rem; text-align: center; }
</style>
</head><body>
<div class="box">
  <h1>Dashboard RFID</h1>
  <?php if (isset($error)): ?>
    <p class="error"><?= $error ?></p>
  <?php endif; ?>
  <form method="POST">
    <label>Utilisateur</label>
    <input name="user" type="text" required />
    <label>Mot de passe</label>
    <input name="pass" type="password" required />
    <button type="submit">Se connecter</button>
  </form>
</div>
</body></html>
