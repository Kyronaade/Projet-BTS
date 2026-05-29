<?php
require 'db.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$uid  = isset($data['uid']) ? trim($data['uid']) : '';
if (!$uid) {
    http_response_code(400);
    die(json_encode(['error' => 'UID manquant']));
}
// Cherche l'utilisateur dans USERS
$stmt = $pdo->prepare("SELECT * FROM USERS WHERE uid = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$access = $user ? 1 : 0;
$date   = date('Y-m-d H:i:s');
// Insère dans ACCESS
$pdo->prepare("INSERT INTO ACCESS (uid, date, access) VALUES (?, ?, ?)")
    ->execute([$uid, $date, $access]);
// Si refusé → insère dans ALERTS + envoie mail
if (!$access) {
    $stmt2 = $pdo->prepare("INSERT INTO ALERTS (date, id_access) VALUES (?, LAST_INSERT_ID())");
    $stmt2->execute([$date]);
    mail(
        'projetval1@gmail.com',
        'ALERTE - Badge non autorisé',
        "Badge inconnu : $uid\nDate : $date",
        'From: rfid@tonserveur.com'
    );
}
echo json_encode([
    'access' => $access,
    'uid'    => $uid,
    'user'   => $user ? $user['nom'].' '.$user['prenom'] : 'Inconnu'
]);
