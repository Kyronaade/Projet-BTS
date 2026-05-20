<?php
header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer les alertes avec le détail de l'accès lié
    $stmt = $pdo->query("
        SELECT
            al.id_alerts,
            al.date,
            al.id_acces,
            a.acces
        FROM ALERTS al
        LEFT JOIN ACCESS a ON a.id_acces = al.id_acces
        ORDER BY al.date DESC
        LIMIT 50
    ");
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    // Créer une alerte manuellement
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("
        INSERT INTO ALERTS (date, id_acces)
        VALUES (NOW(), ?)
    ");
    $stmt->execute([$data['id_acces']]);
    echo json_encode(['id_alerts' => $pdo->lastInsertId()]);
}
?>
