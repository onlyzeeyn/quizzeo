<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['quiz_id'])) {
    die("Aucun quiz sélectionné.");
}

$quiz_id = (int)$_GET['quiz_id'];

$pdo = new PDO("mysql:host=localhost;dbname=quizzeo;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération du quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $quiz_id, 'uid' => $_SESSION['user_id']]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) die("Quiz introuvable.");

// Récupération des questions
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :qid");
$stmtQ->execute(['qid' => $quiz_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Récupération des réponses
foreach ($questions as &$q) {
    $stmtA = $pdo->prepare("SELECT * FROM answers WHERE question_id = :qid");
    $stmtA->execute(['qid' => $q['id']]);
    $q['answers'] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier le Quiz</title>
</head>
<body>

<h2>Modifier le quiz</h2>

<form method="POST" action="save_edit_quiz_entreprise.php">

    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

    <label>Titre :</label>
    <input type="text" name="titre" value="<?= htmlspecialchars($quiz['titre']) ?>" required><br><br>

    <label>Description :</label><br>
    <textarea name="description" required><?= htmlspecialchars($quiz['description']) ?></textarea><br><br>

    <h3>Questions</h3>

    <?php foreach ($questions as $index => $q): ?>
        <div style="border:1px solid #ccc; padding:10px;margin-bottom:10px;">
            <input type="hidden" name="question_id[]" value="<?= $q['id'] ?>">

            <label>Question <?= $index+1 ?> :</label><br>
            <input type="text" name="question_text[]" value="<?= htmlspecialchars($q['question_text']) ?>" required><br><br>

            <label>Réponses :</label><br>

            <?php foreach ($q['answers'] as $a): ?>
                <input type="hidden" name="answer_id[<?= $q['id'] ?>][]" value="<?= $a['id'] ?>">

                <input type="text" name="answer_text[<?= $q['id'] ?>][]" 
                value="<?= htmlspecialchars($a['answer_text']) ?>" required>

                <input type="radio" name="correct_answer[<?= $q['id'] ?>]" 
                value="<?= $a['id'] ?>" <?= $a['is_correct'] ? 'checked' : '' ?>>

                Réponse correcte<br><br>
            <?php endforeach; ?>

        </div>
    <?php endforeach; ?>

    <button type="submit">Enregistrer les modifications</button>
</form>

</body>
</html>
