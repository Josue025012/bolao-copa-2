<?php
// db.php - Conexão PostgreSQL para Vercel (serverless)

// Pega a URL do Vercel Postgres
$dbUrl = getenv("DATABASE_URL") ?: getenv("DATABASE_URL_UNPOOLED");

if (!$dbUrl) {
    throw new Exception("DATABASE_URL não definida nas variáveis de ambiente.");
}

// Faz parse da URL
$parsed = parse_url($dbUrl);

if (!$parsed) {
    throw new Exception("DATABASE_URL inválida.");
}

$host = $parsed["host"] ?? null;
$port = $parsed["port"] ?? 5432;
$dbname = isset($parsed["path"]) ? ltrim($parsed["path"], "/") : null;
$user = $parsed["user"] ?? null;
$pass = $parsed["pass"] ?? null;

// Validação básica
if (!$host || !$dbname || !$user) {
    throw new Exception("Falha ao extrair dados da DATABASE_URL.");
}

// DSN PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

// Opções PDO (importante no Vercel)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false, // serverless NÃO usar persistente
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Mostra erro controlado (bom pra debug no Vercel logs)
    throw new Exception("Erro ao conectar no Postgres: " . $e->getMessage());
}

// função opcional pra reutilizar conexão
