<?php
// conexao.php - Vercel + Neon Postgres (corrigido SNI + SSL)

$dbUrl = getenv("DATABASE_URL_UNPOOLED") ?: getenv("DATABASE_URL");

if (!$dbUrl) {
    throw new Exception("DATABASE_URL não encontrada no ambiente.");
}

$parsed = parse_url($dbUrl);

if (!$parsed) {
    throw new Exception("DATABASE_URL inválida.");
}

$host   = $parsed["host"] ?? null;
$port   = $parsed["port"] ?? 5432;
$dbname = isset($parsed["path"]) ? ltrim($parsed["path"], "/") : null;
$user   = $parsed["user"] ?? null;
$pass   = $parsed["pass"] ?? null;

// validação básica
if (!$host || !$dbname || !$user) {
    throw new Exception("Falha ao ler DATABASE_URL.");
}

/*
⚠️ IMPORTANTE (Neon fix):
- sslmode=require
- options=endpoint=XXXX (SNI)
*/

// tenta extrair endpoint (Neon)
$endpoint = explode('.', $host)[0]; // ex: ep-cool-123456

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=" . $endpoint;

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ]);
} catch (PDOException $e) {
    throw new Exception("Erro ao conectar no Postgres: " . $e->getMessage());
}

return $pdo;