<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    die("Accès refusé.");
}

$pdo = new PDO("mysql:host=localhost;dbname=quizzeo;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$quiz_id = $_POST['quiz_id'];
$titre = $_POST['titre'];
$description = $_POST['description'];

$pdo->prepare("UPDATE quizzes SET titre = :t, description = :d WHERE id = :id")
    ->execute(['t' => $titre, 'd' => $description, 'id' => $quiz_id]);

foreach ($_POST['question_id'] as $i => $qid) {

    $pdo->prepare("UPDATE questions SET question_text = :qt WHERE id = :id")
        ->execute(['qt' => $_POST['question_text'][$i], 'id' => $qid]);

    $correct = $_POST['correct_answer'][$qid] ?? null;

    foreach ($_POST['answer_id'][$qid] as $j => $aid) {

        $pdo->prepare("
            UPDATE answers 
            SET answer_text = :t, is_correct = :c 
            WHERE id = :id")
        ->execute([
            't' => $_POST['answer_text'][$qid][$j],
            'c' => ($aid == $correct) ? 1 : 0,
            'id' => $aid
        ]);
    }
}

header("Location: ../dashboard-entreprise.php");
exit;
?>
