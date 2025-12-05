<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['attempt_id'])) {
    die("Attempt ID manquant.");
}

$attempt_id = intval($_GET['attempt_id']);

$pdo = new PDO("mysql:host=localhost;dbname=quizzeo;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer les informations du quiz
$stmt = $pdo->prepare("
    SELECT qa.*, q.title 
    FROM quiz_attempts qa 
    JOIN quizzes q ON q.id = qa.quiz_id
    WHERE qa.id = :id AND qa.user_id = :uid
");
$stmt->execute(['id' => $attempt_id, 'uid' => $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("Resultat introuvable.");
}

// Récupérer les questions & réponses
$stmtQ = $pdo->prepare("
    SELECT qq.*, qr.answer AS student_answer
    FROM quiz_questions qq
    LEFT JOIN quiz_reponses qr
        ON qr.question_id = qq.id AND qr.attempt_id = :attempt_id
    WHERE qq.quiz_id = :quiz_id
");
$stmtQ->execute(['attempt_id' => $attempt_id, 'quiz_id' => $attempt['quiz_id']]);
$questions = $stmtQ->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Correction du Quiz</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #eef0fb;
  --card: rgba(255,255,255,0.9);
  --right: #4CAF50;
  --wrong: #E74C3C;
  --muted: #6c6f75;
  --accent: #7b6df6;
  --radius: 14px;
  --shadow: 0 10px 25px rgba(0,0,0,0.08);
}

body {
  background: var(--bg);
  font-family: "Inter";
  padding: 30px;
}

.container {
  max-width: 900px;
  margin: auto;
}

.quiz-card {
  background: var(--card);
  padding: 25px;
  border-radius: var(--radius);
  margin-bottom: 25px;
  box-shadow: var(--shadow);
}

h2 {
  font-size: 26px;
  margin-bottom: 10px;
}

.score {
  font-size: 20px;
  margin-bottom: 20px;
  color: var(--accent);
  font-weight: 600;
}

.question {
  margin-bottom: 20px;
  padding: 15px;
  background: white;
  border-radius: 12px;
  border-left: 6px solid var(--accent);
}

.answer {
  padding: 10px;
  margin-top: 8px;
  border-radius: 10px;
}

.correct {
  background: rgba(76, 175, 80, 0.15);
  border-left: 4px solid var(--right);
}

.wrong {
  background: rgba(231, 76, 60, 0.15);
  border-left: 4px solid var(--wrong);
}

.good-answer {
  color: var(--right);
  font-weight: 700;
  margin-top: 5px;
}

.back-btn {
  display: inline-block;
  margin-top: 20px;
  padding: 12px 20px;
  background: var(--accent);
  color: white;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 600;
}

.back-btn:hover {
  opacity: 0.8;
}
</style>
</head>

<body>

<div class="container">

  <div class="quiz-card">
    <h2>📘 Correction du Quiz : <?= htmlspecialchars($attempt['title']) ?></h2>

    <div class="score">
      Score final : <?= $attempt['score'] ?> / <?= count($questions) ?>
    </div>
  </div>

  <?php foreach ($questions as $q): ?>

    <?php
      $is_correct = trim(strtolower($q['student_answer'])) == trim(strtolower($q['correct_answer']));
    ?>

    <div class="quiz-card question">

      <h3><?= htmlspecialchars($q['question']) ?></h3>

      <div class="answer <?= $is_correct ? 'correct' : 'wrong' ?>">
        Votre réponse : <strong><?= htmlspecialchars($q['student_answer'] ?? "Non répondu") ?></strong>
      </div>

      <?php if (!$is_correct): ?>
      <div class="good-answer">
        ✔ Bonne réponse : <?= htmlspecialchars($q['correct_answer']) ?>
      </div>
      <?php endif; ?>

    </div>

  <?php endforeach; ?>

  <a href="dashboard-utilisateur.php" class="back-btn">⬅ Retour au tableau de bord</a>

</div>

</body>
</html>
