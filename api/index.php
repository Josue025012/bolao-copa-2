<?php
$conn = require __DIR__ . "/conexao.php";
require __DIR__ . "/auth.php";
date_default_timezone_set('America/Sao_Paulo');

$user_id = getUser($conn);

if (!$user_id) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT id, nome
    FROM usuarios
    WHERE id = :id
");

$stmt->execute(["id" => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$id_logado = $usuario['id'];
$nome_logado = $usuario['nome'];



if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_COOKIE['session_id'])) {

        $stmt = $conn->prepare("
            DELETE FROM sessions
            WHERE session_id = :sid
        ");

        $stmt->execute([
            "sid" => $id_logado
        ]);

        setcookie("session_id", "", time() - 3600, "/");
        header("Location: login.php");
        exit;
    }

}




/*
--------------------------------------
ENVIAR PALPITE
--------------------------------------
*/





$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $jogo_id = (int) $_POST['jogo_id'];
    $gols_a  = (int) $_POST['gols_a'];
    $gols_b  = (int) $_POST['gols_b'];

    if ($jogo_id > 0) {

        // busca jogo
        $stmt = $conn->prepare("
            SELECT data_jogo
            FROM jogos
            WHERE id = :id
        ");

        $stmt->execute(["id" => $jogo_id]);
        $dados_jogo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados_jogo) {

            // jogo já começou
            if (time() >= strtotime($dados_jogo['data_jogo'])) {
                $mensagem = "<div class='alert error'>⏳ Bloqueado! Este jogo já começou.</div>";
            }

            // já enviou (cookie/session ainda mantido como extra)
            elseif (
                isset($_COOKIE['palpite_jogo_' . $jogo_id]) ||
                isset($_SESSION['votou_jogo_' . $jogo_id])
            ) {
                $mensagem = "<div class='alert error'>🚫 Você já enviou um palpite para este jogo!</div>";
            }

            else {

                // valida no banco (REGRA REAL)
                $stmt = $conn->prepare("
                    SELECT id
                    FROM palpites
                    WHERE jogo_id = :jogo_id
                    AND usuario_id = :usuario_id
                ");

                $stmt->execute([
                    "jogo_id" => $jogo_id,
                    "usuario_id" => $id_logado
                ]);

                $existe = $stmt->fetch();

                if ($existe) {
                    $mensagem = "<div class='alert error'>⚠️ Você já enviou um palpite para este jogo!</div>";
                } else {

                    // INSERIR PALPITE
                    $stmt = $conn->prepare("
                        INSERT INTO palpites
                        (jogo_id, usuario_id, palpite_time_a, palpite_time_b)
                        VALUES (:jogo_id, :usuario_id, :a, :b)
                    ");

                    $stmt->execute([
                        "jogo_id" => $jogo_id,
                        "usuario_id" => $id_logado,
                        "a" => $gols_a,
                        "b" => $gols_b
                    ]);

                    $_SESSION['votou_jogo_' . $jogo_id] = true;
                    setcookie('palpite_jogo_' . $jogo_id, '1', time() + (86400 * 30), "/");

                    $mensagem = "<div class='alert success'>⚽ Palpite gravado com sucesso!</div>";
                }
            }
        }
    } else {
        $mensagem = "<div class='alert error'>⚠️ Preencha todos os campos corretamente.</div>";
    }
}

/*
--------------------------------------
JOGOS DISPONÍVEIS
--------------------------------------
*/
// $stmt = $conn->prepare("
//     SELECT
//         id,
//         time_a,
//         time_b,
//         TO_CHAR(data_jogo, 'DD/MM HH24:MI') AS data_formatada
//     FROM jogos
//     WHERE status = 'aberto'
//     AND data_jogo > NOW()
//     ORDER BY data_jogo ASC
// ");

// $stmt->execute();
// $jogos_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);



/*
--------------------------------------
JOGOS DISPONÍVEIS (FILTRADOS)
--------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        j.id,
        j.time_a,
        j.time_b,
        TO_CHAR(j.data_jogo, 'DD/MM HH24:MI') AS data_formatada
    FROM jogos j
    LEFT JOIN palpites p ON j.id = p.jogo_id AND p.usuario_id = :usuario_id
    WHERE j.status = 'aberto'
    AND j.data_jogo > NOW()
    AND p.id IS NULL -- Filtra apenas jogos que NÃO possuem palpite deste usuário
    ORDER BY j.data_jogo ASC
");

// Passa o ID do usuário logado para a consulta
$stmt->execute(["usuario_id" => $id_logado]);
$jogos_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
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
            <span class="material-symbols-outlined">account_circle</span> <?php echo htmlspecialchars($nome_logado); ?>
            <a href="index.php?action=logout" style="color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; display: flex; align-items: center; gap: 3px;">
                <span class="material-symbols-outlined">logout</span>
            </a>
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

                <?php if (count($jogos_disponiveis) > 0): ?>
                    <form method="POST" action="">
                        <!-- O campo input text antigo do nome do usuário foi removido daqui por segurança -->

                        <div class="form-group">
                            <label for="jogo_id">Selecione a Partida:</label>
                            <select id="jogo_id" name="jogo_id" required>
                                <option value="">-- Escolha um confronto --</option>
                                <?php foreach ($jogos_disponiveis as $jogo): ?>
                                    <option value="<?php echo $jogo['id']; ?>">
                                        ⚽ <?php echo $jogo['time_a']; ?> x <?php echo $jogo['time_b']; ?> (<?php echo $jogo['data_formatada']; ?>)
                                    </option>
                                <?php endforeach; ?>
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
