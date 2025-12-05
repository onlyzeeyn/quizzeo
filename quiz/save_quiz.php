<?php
session_start();

// Vérifier que l'utilisateur est bien une école
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$db   = "quizzeo";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $user_id = $_SESSION['user_id'];

    if (!empty($title)) {
        // Convertir le statut pour correspondre à la DB
        $db_status = ($status === 'published') ? 'lancé' : 'en cours d\'écriture';

        // Insérer le quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (user_id, titre, description, statut) VALUES (:user_id, :titre, :description, :statut)");
        $stmt->execute([
            'user_id' => $user_id,
            'titre' => $title,
            'description' => $description,
            'statut' => $db_status
        ]);

        $quiz_id = $pdo->lastInsertId();

        // Insérer les questions et réponses
        foreach ($_POST as $key => $value) {
            if (preg_match('/^question_(\d+)$/', $key, $matches)) {
                $q_num = $matches[1];
                $question_text = $value;

                // Ajouter question
                $stmtQ = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, points, type) VALUES (:quiz_id, :question_text, :points, 'qcm')");
                $stmtQ->execute([
                    'quiz_id' => $quiz_id,
                    'question_text' => $question_text,
                    'points' => 1
                ]);

                $question_id = $pdo->lastInsertId();

                // Ajouter les réponses
                for ($i = 1; $i <= 3; $i++) {
                    $rep_key = "question_{$q_num}_rep{$i}";
                    $answer_text = $_POST[$rep_key] ?? '';
                    $is_correct = 0;
                    if (isset($_POST["question_{$q_num}_correct"]) && $_POST["question_{$q_num}_correct"] === "Réponse $i") {
                        $is_correct = 1;
                    }

                    $stmtA = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, :is_correct)");
                    $stmtA->execute([
                        'question_id' => $question_id,
                        'answer_text' => $answer_text,
                        'is_correct' => $is_correct
                    ]);
                }
            }
        }

        header("Location: ../dashboard-ecole.php");
        exit;
    } else {
        $error = "Le titre du quiz est obligatoire.";
    }
}
?>
