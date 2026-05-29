<?php
$host = 'localhost';
$db   = 'access_rfid';   // ta base
$user = 'rfid_user';           // ton user phpMyAdmin
$pass = 'root';               // ton mot de passe

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}
?>
