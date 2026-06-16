<?php
$conn = require __DIR__ . "/conexao.php";
session_start();

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($nome) && !empty($email) && !empty($senha)) {

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("
                INSERT INTO usuarios (nome, email, senha)
                VALUES (:nome, :email, :senha)
            ");

            $stmt->execute([
                "nome"  => $nome,
                "email" => $email,
                "senha" => $senha_hash
            ]);

            $mensagem = "<div class='alert success'>✅ Cadastro realizado! <a href='login.php'>Faça login</a></div>";

        } catch (PDOException $e) {

            // erro típico de email duplicado
            if ($e->getCode() == 23505) {
                $mensagem = "<div class='alert error'>⚠️ Este e-mail já está cadastrado.</div>";
            } else {
                $mensagem = "<div class='alert error'>⚠️ Erro ao cadastrar usuário.</div>";
            }
        }

    } else {
        $mensagem = "<div class='alert error'>⚠️ Preencha todos os campos.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>BatePalpite - Cadastrar Conta</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
        <div class="container">
            <h3>📝 Criar Nova Conta</h3>
            <?php echo $mensagem; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nome / Apelido:</label>
                    <input type="text" name="nome" placeholder="Ex: Lucas Silva" required>
                </div>
                <div class="form-group">
                    <label>E-mail:</label>
                    <input type="email" style="padding:12px; border:1px solid #cbd5e1; border-radius:8px; width:100%;" name="email" placeholder="seuemail@teste.com" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
            <p style="text-align:center; margin-top:15px; font-size:14px;">Já tem conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>
</body>
</html>
