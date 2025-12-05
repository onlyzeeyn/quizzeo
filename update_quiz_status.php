<?php
session_start();

// Vérifier que l'utilisateur est une école
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($quiz_id && in_array($statut, ["en cours d'écriture","lancé","terminé"])) {
        $host = "localhost";
        $db   = "quizzeo";
        $user = "root";
        $pass = "";

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Mettre à jour le statut du quiz uniquement si l'école possède ce quiz
            $stmt = $pdo->prepare("UPDATE quizzes SET statut = :statut WHERE id = :quiz_id AND user_id = :user_id");
            $stmt->execute([
                'statut' => $statut,
                'quiz_id' => $quiz_id,
                'user_id' => $user_id
            ]);

        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}

header("Location: ../dashboard-ecole.php");
exit;
?>
