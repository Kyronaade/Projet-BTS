<?php
header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lister les utilisateurs avec leur groupe
    $stmt = $pdo->query("
        SELECT
            u.id_users,
            u.nom,
            u.prenom,
            g.admin,
            g.users AS groupe_users
        FROM USERS u
        LEFT JOIN GROUPS g ON g.id_groups = u.groups
        ORDER BY u.nom
    ");
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    // Ajouter un utilisateur
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("
        INSERT INTO USERS (nom, prenom, groups)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['groups'] ?? null
    ]);
    echo json_encode(['id_users' => $pdo->lastInsertId()]);

} elseif ($method === 'DELETE') {
    // Supprimer un utilisateur
    $id  = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM USERS WHERE id_users = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
}
?>
