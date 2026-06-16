<?php
include 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: login.php");
    exit;
}


$senha_correta = "admin123";
$mensagem = "";

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logado']);
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if ($_POST['senha_admin'] === $senha_correta) {
        $_SESSION['admin_logado'] = true;
    } else {
        $mensagem = "<div class='alert error'>🔒 Senha incorreta!</div>";
    }
}

if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_jogo'])) {
        $time_a = $conn->real_escape_string($_POST['time_a']);
        $time_b = $conn->real_escape_string($_POST['time_b']);
        $data_jogo = $_POST['data_jogo'];

        if (!empty($time_a) && !empty($time_b) && !empty($data_jogo)) {
            $sql = "INSERT INTO jogos (time_a, time_b, data_jogo, status) VALUES ('$time_a', '$time_b', '$data_jogo', 'aberto')";
            if ($conn->query($sql) === TRUE) {
                $mensagem = "<div class='alert success'>🎮 Jogo cadastrado com sucesso!</div>";
            }
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['encerrar_jogo'])) {
        $jogo_id = intval($_POST['jogo_id']);
        $gols_a_real = intval($_POST['gols_a_real']);
        $gols_b_real = intval($_POST['gols_b_real']);

        $sql_update_jogo = "UPDATE jogos SET gols_a_real = $gols_a_real, gols_b_real = $gols_b_real, status = 'encerrado' WHERE id = $jogo_id";
        if ($conn->query($sql_update_jogo) === TRUE) {
            $palpites = $conn->query("SELECT id, palpite_time_a, palpite_time_b FROM palpites WHERE jogo_id = $jogo_id");
            $resultado_real = ($gols_a_real > $gols_b_real) ? 1 : (($gols_b_real > $gols_a_real) ? 2 : 0);

            while ($row = $palpites->fetch_assoc()) {
                $p_id = $row['id']; $pa = $row['palpite_time_a']; $pb = $row['palpite_time_b']; $pontos = 0;
                $resultado_palpite = ($pa > $pb) ? 1 : (($pb > $pa) ? 2 : 0);

                if ($pa == $gols_a_real && $pb == $gols_b_real) { $pontos = 10; } 
                elseif ($resultado_palpite == $resultado_real) { $pontos = 5; }

                $conn->query("UPDATE palpites SET pontos = $pontos WHERE id = $p_id");
            }
            $mensagem = "<div class='alert success'>🏁 Pontos calculados com sucesso!</div>";
        }
    }
    $jogos_abertos = $conn->query("SELECT id, time_a, time_b FROM jogos WHERE status = 'aberto' ORDER BY data_jogo ASC");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatePalpite - Admin</title>
    <!-- Biblioteca de Ícones Modernos -->
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
            <?php if (isset($_SESSION['admin_logado'])): ?>
                <span style="color: #2ecc71; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                    <i class='bx bx-shield-quarter'></i> Organizado (Admin)
                </span>
                <a href="admin.php?action=logout" style="color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; display: flex; align-items: center; gap: 3px;">
                    <i class='bx bx-log-out'></i> Sair
                </a>
            <?php else: ?>
                <span><i class='bx bx-user-circle'></i> Visitante</span>
            <?php endif; ?>
        </div>
    </nav>

    <!-- CONTAINER INTEGRADO (SIDEBAR + CONTEÚDO) -->
    <div class="main-wrapper">
        
        <!-- SIDEBAR LATERAL -->
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php">
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
                    <a href="admin.php" class="active">
                        <i class='bx bx-cog'></i>
                        <span>Painel Admin</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- ÁREA DO CONTEÚDO -->
        <main class="content-area">
            <div class="container">
                <?php if (!isset($_SESSION['admin_logado'])): ?>
                    <h3>🔒 Área Restrita</h3>
                    <div class="subtitle">Apenas o organizador do bolão possui acesso a esta tela</div>
                    
                    <?php echo $mensagem; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        <div class="form-group">
                            <label for="senha_admin">Senha Mestra:</label>
                            <input type="password" id="senha_admin" name="senha_admin" required>
                        </div>
                        <button type="submit">Entrar no Painel</button>
                    </form>

                <?php else: ?>
                    <h3>⚙️ Gerenciar Bolão</h3>
                    <div class="subtitle">Cadastre novas partidas e encerre confrontos para rodar a pontuação</div>
                    
                    <?php echo $mensagem; ?>

                    <h2>➕ Cadastrar Nova Partida</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="cadastrar_jogo" value="1">
                        <div class="row">
                            <div class="form-group">
                                <label>Time Mandante (A):</label>
                                <input type="text" name="time_a" placeholder="Ex: Flamengo" required>
                            </div>
                            <div class="form-group">
                                <label>Time Visitante (B):</label>
                                <input type="text" name="time_b" placeholder="Ex: Vasco" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Data e Horário:</label>
                            <input type="datetime-local" name="data_jogo" required>
                        </div>
                        <button type="submit">Salvar Jogo</button>
                    </form>

                    <h2>🏁 Encerrar Jogo e Computar Pontos</h2>
                    <?php if ($jogos_abertos->num_rows > 0): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="encerrar_jogo" value="1">
                            <div class="form-group">
                                <label>Selecione a Partida:</label>
                                <select name="jogo_id" required>
                                    <option value="">-- Escolha o jogo terminado --</option>
                                    <?php while($jogo = $jogos_abertos->fetch_assoc()): ?>
                                        <option value="<?php echo $jogo['id']; ?>">
                                            ⚽ <?php echo $jogo['time_a'] . " x " . $jogo['time_b']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="form-group">
                                    <label>Gols Mandante:</label>
                                    <input type="number" name="gols_a_real" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Gols Visitante:</label>
                                    <input type="number" name="gols_b_real" min="0" value="0" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-danger">Computar Pontuação</button>
                        </form>
                    <?php else: ?>
                        <p style="color: #7f8c8d; font-style: italic; font-size:14px; text-align:center; padding: 15px;">📭 Não há partidas abertas no momento.</p>
                    <?php endif; ?>
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
