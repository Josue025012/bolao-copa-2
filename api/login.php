<?php
// include 'conexao.php';
$conn = require __DIR__ . "/conexao.php"; 
session_start();

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // Verifica se a senha confere com o hash criptografado
        if (password_verify($senha, $usuario['senha'])) {
            // Proteção contra fixação de sessão
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            header("Location: index.php");
            exit;
        } else {
            $mensagem = "<div class='alert error'>🔒 E-mail ou senha incorretos!</div>";
        }
    } else {
        $mensagem = "<div class='alert error'>🔒 E-mail ou senha incorretos!</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>BatePalpite - Entrar</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
        <div class="container">
            <h3>🔐 Acessar o Bolão</h3>
            <?php echo $mensagem; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>E-mail:</label>
                    <input type="email" style="padding:12px; border:1px solid #cbd5e1; border-radius:8px; width:100%;" name="email" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <p style="text-align:center; margin-top:15px; font-size:14px;">Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>
