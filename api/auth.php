<?php

function getUser($conn) {

    if (!isset($_COOKIE['session_id'])) {
        return null;
    }

    $session_id = $_COOKIE['session_id'];

    $stmt = $conn->prepare("
        SELECT user_id
        FROM sessions
        WHERE session_id = :sid
        AND expires_at > NOW()
    ");

    $stmt->execute(["sid" => $session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    return $session ? $session['user_id'] : null;
}


// // parte do logout
// $conn = require __DIR__ . "/conexao.php";

// if (isset($_COOKIE['session_id'])) {

//     $stmt = $conn->prepare("
//         DELETE FROM sessions
//         WHERE session_id = :sid
//     ");

//     $stmt->execute([
//         "sid" => $_COOKIE['session_id']
//     ]);

//     setcookie("session_id", "", time() - 3600, "/");
// }

// header("Location: /api/login.php");
// exit;