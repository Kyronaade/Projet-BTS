<?php
header('Content-Type: application/json');
require 'db.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM ACCESS ORDER BY date DESC LIMIT 100");
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $uid   = $data['uid'] ?? null;

    // Vérifier si l'UID existe dans USERS
    $check = $pdo->prepare("SELECT id_users FROM USERS WHERE uid = ? AND groups = 1");
    $check->execute([$uid]);
    $user  = $check->fetch();

    $access = $user ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO ACCESS (date, access, uid) VALUES (NOW(), ?, ?)");
	$stmt->execute([$access, $uid]);
    $id = $pdo->lastInsertId();

    if ($access == 0) {
        $alert = $pdo->prepare("INSERT INTO ALERTS (date, id_access) VALUES (NOW(), ?)");
        $alert->execute([$id]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'projetval1@gmail.com';
            $mail->Password   = 'jhrfjgbdsijxvfzg';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('projetval1@gmail.com', 'Dashboard RFID');
            $mail->addAddress('projetval1@gmail.com');
            $mail->Subject = 'Alerte RFID - Accès refusé';
            $mail->Body    = "Un accès refusé a été détecté.\n\nDate : " . date('d/m/Y H:i:s') . "\nUID badge : " . $uid . "\nID accès : " . $id;
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur mail: " . $e->getMessage());
        }
    }

    echo json_encode(['access' => $access, 'uid' => $uid]);
} elseif ($method === 'DELETE') {
    $id   = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM ACCESS WHERE id_acces = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
}
?>
