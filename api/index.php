<?php
// include 'conexao.php';
$conn = require __DIR__ . "/conexao.php"; 
session_start();

date_default_timezone_set('America/Sao_Paulo');

// Bloqueia o acesso caso o usuário não esteja logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_logado = $_SESSION['usuario_id'];
$nome_logado = $_SESSION['usuario_nome'];
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jogo_id = intval($_POST['jogo_id']);
    $gols_a = intval($_POST['gols_a']);
    $gols_b = intval($_POST['gols_b']);

    if ($jogo_id > 0) {
        $busca_jogo = $conn->query("SELECT data_jogo FROM jogos WHERE id = $jogo_id");
        if ($busca_jogo->num_rows > 0) {
            $dados_jogo = $busca_jogo->fetch_assoc();
            
            // Validações de segurança e tempo
            if (time() >= strtotime($dados_jogo['data_jogo'])) {
                $mensagem = "<div class='alert error'>⏳ Bloqueado! Este jogo já começou.</div>";
            } elseif (isset($_COOKIE['palpite_jogo_' . $jogo_id]) || isset($_SESSION['votou_jogo_' . $jogo_id])) {
                $mensagem = "<div class='alert error'>🚫 Você já enviou um palpite para este jogo!</div>";
            } else {
                // Nova validação baseada no ID único do usuário
                $check = $conn->query("SELECT id FROM palpites WHERE jogo_id = $jogo_id AND usuario_id = $id_logado");
                if ($check->num_rows > 0) {
                    $mensagem = "<div class='alert error'>⚠️ Você já enviou um palpite para este jogo!</div>";
                } else {
                    // Insere o palpite amarrado à coluna relacional usuario_id
                    $sql = "INSERT INTO palpites (jogo_id, usuario_id, palpite_time_a, palpite_time_b) VALUES ($jogo_id, $id_logado, $gols_a, $gols_b)";
                    if ($conn->query($sql) === TRUE) {
                        $_SESSION['votou_jogo_' . $jogo_id] = true;
                        setcookie('palpite_jogo_' . $jogo_id, '1', time() + (86400 * 30), "/");
                        $mensagem = "<div class='alert success'>⚽ Palpite gravado com sucesso!</div>";
                    } else {
                        $mensagem = "<div class='alert error'>❌ Erro ao enviar: " . $conn->error . "</div>";
                    }
                }
            }
        }
    } else {
        $mensagem = "<div class='alert error'>⚠️ Preencha todos os campos corretamente.</div>";
    }
}

$jogos_disponiveis = $conn->query("SELECT id, time_a, time_b, DATE_FORMAT(data_jogo, '%d/%m/%H:%i') as data_formatada FROM jogos WHERE status = 'aberto' AND data_jogo > NOW() ORDER BY data_jogo ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatePalpite - Cadastrar Palpite</title>
    <!-- Biblioteca de Ícones Modernos corrigida para o Boxicons via UNPKG -->
    <link href="https://unpkg.com" rel="stylesheet">
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar">
        <div class="navbar-brand">
            <button class="toggle-btn" id="toggleSidebar">
                ☰
            </button>
            <span>⚽ BatePalpite</span>
        </div>
        <div class="navbar-user">
            <!-- Nome dinâmico exibido no topo -->
            <i class='bx bx-user-circle'></i> <?php echo htmlspecialchars($nome_logado); ?>
        </div>
    </nav>

    <!-- CONTAINER INTEGRADO (SIDEBAR + CONTEÚDO) -->
    <div class="main-wrapper">
        
        <!-- SIDEBAR LATERAL -->
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php" class="active">
                        <i class='bx bx-edit-alt'></i>
                        <span>Novo Palpite</span>
                    </a>
                </li>
                <li>
                    <a href="ranking.php">
                        <i class='bx bx-trophy'></i>
                        <span>Ver Ranking</span>
                    </a>
                </li>
                <li>
                    <a href="admin.php">
                        <i class='bx bx-cog'></i>
                        <span>Painel Admin</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- ÁREA DO CONTEÚDO -->
        <main class="content-area">
            <div class="container">
                <h3>🏆 Novo Palpite</h3>
                <div class="subtitle">Selecione um dos confrontos abertos e envie o seu placar</div>
                
                <?php echo $mensagem; ?>

                <?php if ($jogos_disponiveis->num_rows > 0): ?>
                    <form method="POST" action="">
                        <!-- O campo input text antigo do nome do usuário foi removido daqui por segurança -->

                        <div class="form-group">
                            <label for="jogo_id">Selecione a Partida:</label>
                            <select id="jogo_id" name="jogo_id" required>
                                <option value="">-- Escolha um confronto --</option>
                                <?php while($jogo = $jogos_disponiveis->fetch_assoc()): ?>
                                    <option value="<?php echo $jogo['id']; ?>">
                                        ⚽ <?php echo $jogo['time_a']; ?> x <?php echo $jogo['time_b']; ?> (<?php echo $jogo['data_formatada']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="scoreboard">
                            <div class="score-input">
                                <label>Gols Mandante</label>
                                <input type="number" name="gols_a" min="0" value="0" required>
                            </div>
                            <div class="vs">X</div>
                            <div class="score-input">
                                <label>Gols Visitante</label>
                                <input type="number" name="gols_b" min="0" value="0" required>
                            </div>
                        </div>

                        <button type="submit">Confirmar Palpite</button>
                    </form>
                <?php else: ?>
                    <p style="text-align:center; color:#7f8c8d; font-style:italic; padding:20px;">📭 Sem jogos abertos no momento.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Script de Controle da Sidebar -->
    <script>
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const contentArea = document.querySelector('.content-area');
        
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            contentArea.classList.toggle('sidebar-closed');
        });
    </script>
</body>
</html>
