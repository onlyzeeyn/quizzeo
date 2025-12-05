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

if (!isset($_GET['quiz_id'])) {
    die("Quiz non spécifié.");
}

$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

// Récupérer le quiz
$stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = :quiz_id");
$stmt_quiz->execute(['quiz_id' => $quiz_id]);
$quiz = $stmt_quiz->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("Quiz introuvable.");
}

// Récupérer les questions et réponses de l'utilisateur
$stmt_answers = $pdo->prepare("
    SELECT q.id AS question_id, q.question_text, q.type,
           a.answer_text AS correct_answer,
           ua.answer_id, ua.answer_text AS user_answer, ua.score
    FROM questions q
    LEFT JOIN answers a ON a.question_id = q.id AND a.is_correct = 1
    LEFT JOIN user_quiz_answers ua ON ua.question_id = q.id AND ua.user_id = :user_id
    WHERE q.quiz_id = :quiz_id
");
$stmt_answers->execute([
    'quiz_id' => $quiz_id,
    'user_id' => $user_id
]);

$questions = $stmt_answers->fetchAll(PDO::FETCH_ASSOC);

// Calculer le score total
$total_score = 0;
$max_score = 0;

foreach ($questions as $q) {
    $max_score++;
    $total_score += $q['score'];
}

?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Résultat du Quiz — Quizzeo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
:root{
  --bg: #f6f7fb;
  --card: rgba(255,255,255,0.9);
  --muted: #7b7f87;
  --text: #0f1724;
  --accent: #7b6df6;
  --accent-2: #F38788;
  --accent-3: #FAB540;
  --glass: rgba(255,255,255,0.65);
  --radius: 16px;
  --shadow-lg: 0 18px 40px rgba(16,24,40,0.08);
  --shadow-sm: 0 6px 18px rgba(16,24,40,0.06);
  --max-width: 900px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); line-height:1.45; min-height:100vh;}
.container{width:95%; max-width: var(--max-width); margin:0 auto; padding:60px 0;}
section{background: var(--card); padding:30px; border-radius: var(--radius); margin-bottom:30px; box-shadow: var(--shadow-lg);}
h2, h3{margin-bottom:20px;}
.correct{color:green;font-weight:600;}
.incorrect{color:red;font-weight:600;}
.btn{display:inline-block; padding:10px 20px; border-radius:12px; font-weight:600; cursor:pointer; border:none; box-shadow: var(--shadow-sm); text-decoration:none; text-align:center; background: linear-gradient(90deg,var(--accent-3),#f5aa2e); color:white; margin-top:10px;}
.btn:hover{transform:translateY(-2px);}
.question-block{margin-bottom:20px; padding:15px; background: rgba(255,255,255,0.9); border-radius:12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
.question-block p{margin:6px 0;}
</style>
</head>
<body>

<div class="container">
  <h2>Résultat du Quiz : <?php echo htmlspecialchars($quiz['titre']); ?></h2>
  <p><strong>Score :</strong> <?php echo $total_score; ?> / <?php echo $max_score; ?></p>

  <?php foreach ($questions as $index => $q): ?>
    <div class="question-block">
      <p><strong>Question <?php echo $index+1; ?> :</strong> <?php echo htmlspecialchars($q['question_text']); ?></p>
      <p>Votre réponse : 
        <?php 
          if ($q['type'] === 'qcm') {
              echo htmlspecialchars($q['user_answer']) ?: "<em>Pas de réponse</em>";
          } else {
              echo htmlspecialchars($q['user_answer']) ?: "<em>Pas de réponse</em>";
          }
        ?>
      </p>
      <p>Réponse correcte : <?php echo htmlspecialchars($q['correct_answer']); ?></p>
      <p>Statut : 
        <span class="<?php echo $q['score'] ? 'correct' : 'incorrect'; ?>">
          <?php echo $q['score'] ? 'Correcte' : 'Incorrecte'; ?>
        </span>
      </p>
    </div>
  <?php endforeach; ?>

  <a class="btn" href="../dashboard-utilisateur.php">⬅ Retour au Dashboard</a>
</div>

</body>
</html>
