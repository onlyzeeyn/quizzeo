<?php
session_start();

// Vérifier si l'utilisateur est connecté et a le rôle "user"
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
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

// Vérifier quiz_id
if (!isset($_POST['quiz_id'])) {
    die("Quiz non spécifié.");
}
$quiz_id = (int)$_POST['quiz_id'];
$user_id = $_SESSION['user_id'];

// Récupérer toutes les questions du quiz
$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
$stmt_q->execute(['quiz_id' => $quiz_id]);
$questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

$total_score = 0;

// Préparer l'insertion des réponses
$stmt_insert = $pdo->prepare("
    INSERT INTO user_quiz_answers (user_id, quiz_id, question_id, answer_id, answer_text, score)
    VALUES (:user_id, :quiz_id, :question_id, :answer_id, :answer_text, :score)
");

// Parcourir toutes les questions et calculer le score
foreach ($questions as $q) {
    $q_id = $q['id'];
    $q_type = $q['type'];
    $user_answer = $_POST["q$q_id"] ?? '';

    $score = 0;
    $answer_id = null;
    $answer_text = null;

    if ($q_type === 'qcm') {
        // Vérifier la bonne réponse
        $stmt_a = $pdo->prepare("SELECT id, is_correct FROM answers WHERE id = :id");
        $stmt_a->execute(['id' => $user_answer]);
        $answer = $stmt_a->fetch(PDO::FETCH_ASSOC);
        if ($answer) {
            $answer_id = $answer['id'];
            $score = ($answer['is_correct'] == 1) ? 1 : 0;
        }
    } else {
        // Question libre
        $answer_text = $user_answer;
        $score = 0; // À noter, tu peux gérer la correction manuelle pour les questions libres
    }

    $total_score += $score;

    // Insérer dans la table user_quiz_answers
    $stmt_insert->execute([
        'user_id' => $user_id,
        'quiz_id' => $quiz_id,
        'question_id' => $q_id,
        'answer_id' => $answer_id,
        'answer_text' => $answer_text,
        'score' => $score
    ]);
}

// Rediriger vers une page de résultats
header("Location: quiz_result.php?quiz_id=$quiz_id");
exit;
?>
