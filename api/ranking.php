<?php
require __DIR__ . "/bootstrap.php";
$conn = require __DIR__ . "/conexao.php";
require __DIR__ . "/auth.php";

$user_id = getUser($conn);

if (!$user_id) {
    header("Location: login.php");
    exit;
}


$stmt->execute(["id" => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$id_logado = $usuario['id'];
$nome_logado = $usuario['nome'];

/*
--------------------------------------
1. RANKING
--------------------------------------
*/
$sql_ranking = "
    SELECT 
        u.nome AS nome_usuario,
        SUM(p.pontos) AS total_pontos,
        COUNT(p.id) AS palpites_feitos
    FROM palpites p
    JOIN usuarios u ON p.usuario_id = u.id
    GROUP BY u.nome
    ORDER BY total_pontos DESC, palpites_feitos DESC, nome_usuario ASC
";

$stmt = $conn->prepare($sql_ranking);
$stmt->execute();
$resultado_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
--------------------------------------
2. DETALHES
--------------------------------------
*/
$sql_detalhes = "
    SELECT 
        u.nome AS nome_usuario,
        p.palpite_time_a,
        p.palpite_time_b,
        p.pontos,
        j.time_a,
        j.time_b,
        j.gols_a_real,
        j.gols_b_real,
        j.status,
        TO_CHAR(j.data_jogo, 'DD/MM') AS data_formatada
    FROM palpites p
    JOIN jogos j ON p.jogo_id = j.id
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY u.nome ASC, j.data_jogo DESC
";

$stmt = $conn->prepare($sql_detalhes);
$stmt->execute();
$resultado_detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
--------------------------------------
3. AGRUPAR POR USUÁRIO
--------------------------------------
*/
$palpites_por_usuario = [];

foreach ($resultado_detalhes as $palpite) {
    $palpites_por_usuario[$palpite['nome_usuario']][] = $palpite;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatePalpite - Ranking Unificado</title>
    <!-- Biblioteca de Ícones Modernos do Boxicons via UNPKG corrigida -->
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
            <!-- Exibe dinamicamente o nome do usuário logado na sessão -->
            <i class='bx bx-user-circle'></i> <?php echo htmlspecialchars($nome_logado); ?>
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
                    <a href="ranking.php" class="active">
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
            <div class="container container-ranking">
                <h3>📊 Classificação Geral</h3>
                <div class="subtitle">Pontuação acumulada e detalhes dos palpites de cada participante</div>

                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th style="text-align: left; padding-left: 12px;">Apostador</th>
                            <th>Palpites</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultado_ranking && $resultado_ranking->num_rows > 0) {
                            $posicao = 1;
                            while($linha = $resultado_ranking->fetch_assoc()) {
                                $nome_atual = $linha['nome_usuario'];
                                $classe_pos = "pos"; $exibir_pos = $posicao;
                                if($posicao == 1) { $exibir_pos = "🥇"; $classe_pos .= " pos-1"; }
                                if($posicao == 2) { $exibir_pos = "🥈"; $classe_pos .= " pos-2"; }
                                if($posicao == 3) { $exibir_pos = "🥉"; $classe_pos .= " pos-3"; }

                                echo "<tr class='user-row'>";
                                echo "<td class='{$classe_pos}'>" . $exibir_pos . "</td>";
                                echo "<td class='user-name'>" . htmlspecialchars($nome_atual) . "</td>";
                                echo "<td><span class='badge-qty'>" . $linha['palpites_feitos'] . " jogos</span></td>";
                                echo "<td><span class='badge-total'>" . $linha['total_pontos'] . " pts</span></td>";
                                echo "</tr>";

                                echo "<tr class='details-row'>";
                                echo "<td></td>";
                                echo "<td colspan='3'>";
                                echo "<div class='details-box'>";
                                echo "<div class='details-title'>Histórico de palpites:</div>";

                                if (isset($palpites_por_usuario[$nome_atual])) {
                                    foreach ($palpites_por_usuario[$nome_atual] as $p) {
                                        $placar_oficial = "<span class='badge-waiting'>Aguardando</span>";
                                        if ($p['status'] == 'encerrado') {
                                            $placar_oficial = "Oficial: <b>" . $p['gols_a_real'] . "x" . $p['gols_b_real'] . "</b>";
                                        }

                                        echo "<div class='match-item'>";
                                        echo "  <div class='match-teams'>⚽ " . htmlspecialchars($p['time_a']) . " x " . htmlspecialchars($p['time_b']) . "</div>";
                                        echo "  <div class='match-guesses'>Palpite: " . $p['palpite_time_a'] . " x " . $p['palpite_time_b'] . "</div>";
                                        echo "  <div class='match-real'>{$placar_oficial}</div>";
                                        echo "  <div class='match-points'><span class='badge-pts-earned'>+" . $p['pontos'] . " pts</span></div>";
                                        echo "</div>";
                                    }
                                }
                                echo "</div></td></tr>";
                                $posicao++;
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding:20px; color:#7f8c8d;'>📭 Nenhum palpite registrado ainda.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
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
