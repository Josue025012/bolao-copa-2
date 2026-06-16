<?php
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

$dsn = "pgsql:host=$host;dbname=$db;port=5432";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Conectou!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
