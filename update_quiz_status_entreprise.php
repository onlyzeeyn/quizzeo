<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['quiz_id']) || !isset($_POST['statut'])) {
    die("Paramètres invalides.");
}

$quiz_id = (int)$_POST['quiz_id'];
$statut = $_POST['statut'];

$host = "localhost";
$db   = "quizzeo";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE quizzes SET statut = :statut WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'statut' => $statut,
        'id' => $quiz_id,
        'user_id' => $_SESSION['user_id']
    ]);

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

header("Location: dashboard-entreprise.php");
exit;
?>
