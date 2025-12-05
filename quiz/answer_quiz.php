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

// Vérifier qu'un quiz est sélectionné
if (!isset($_GET['quiz_id'])) {
    die("Quiz non spécifié.");
}
$quiz_id = (int)$_GET['quiz_id'];

// Récupérer le quiz
$stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id");
$stmt_quiz->execute(['id' => $quiz_id]);
$quiz = $stmt_quiz->fetch(PDO::FETCH_ASSOC);
if (!$quiz) {
    die("Quiz introuvable.");
}

// Récupérer les questions du quiz
$stmt_questions = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
$stmt_questions->execute(['quiz_id' => $quiz_id]);
$questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque question, récupérer ses réponses si c'est un QCM
$answers_by_question = [];
foreach ($questions as $q) {
    if ($q['type'] === 'qcm') {
        $stmt_ans = $pdo->prepare("SELECT * FROM answers WHERE question_id = :question_id");
        $stmt_ans->execute(['question_id' => $q['id']]);
        $answers_by_question[$q['id']] = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizzeo — Répondre au Quiz</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
/* CSS copié et adapté de ton template dashboard/quiz */
:root{
  --bg: #f6f7fb; --card: rgba(255,255,255,0.9); --muted: #7b7f87;
  --text: #0f1724; --accent: #7b6df6; --accent-2: #F38788; --accent-3: #FAB540;
  --glass: rgba(255,255,255,0.65); --radius: 16px; --shadow-lg: 0 18px 40px rgba(16,24,40,0.08);
  --shadow-sm: 0 6px 18px rgba(16,24,40,0.06); --max-width: 900px;
}
body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); font-family:"Inter", sans-serif; min-height:100vh;}
a{text-decoration:none;color:inherit;}
.container{width:95%; max-width: var(--max-width); margin:0 auto;}
header{width:100%;position:sticky;top:0;background: rgba(255,255,255,0.6);backdrop-filter:blur(8px);box-shadow: var(--shadow-sm);}
header .nav{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:20px;padding:10px 24px;}
header .logo{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg,var(--accent),#cc7af6);color:white;font-weight:700;font-family:"Fredoka One";font-size:20px;}
header .brand-info{display:flex;flex-direction: column;justify-content:center;}
header .brand-info .title{font-weight:700;font-size:18px;}
header .brand-info .subtitle{font-size:12px;color:var(--muted); margin-top:2px;}
header .logout{padding:10px 20px;background: linear-gradient(90deg,var(--accent-2),#e26666);color:white;border-radius:12px;font-weight:600;box-shadow: var(--shadow-sm);text-align:center;}
header .logout:hover{transform:translateY(-2px);}
main{padding:120px 0 40px;}
.section{background: var(--card);padding:30px;border-radius: var(--radius);margin-bottom:30px;box-shadow: var(--shadow-lg);backdrop-filter: blur(12px);}
.section:hover{transform: translateY(-3px);}
.section h3{font-weight:700;font-size:24px;margin-bottom:20px;}
.question{margin-bottom:20px;}
.question p{margin-bottom:10px;font-weight:500;}
.question label{display:block;margin-bottom:8px;cursor:pointer;padding:6px 10px;border-radius:8px;transition: background .2s;}
.question input[type="radio"]{margin-right:8px;}
.question label:hover{background:rgba(123,109,246,0.1);}
.btn{display:inline-block;padding:10px 20px;border-radius:12px;font-weight:600;cursor:pointer;border:none;box-shadow: var(--shadow-sm);background: linear-gradient(90deg,var(--accent-3),#f5aa2e);color:white;margin-top:10px;}
.btn:hover{transform:translateY(-2px);}
footer{text-align:center;padding:20px 0;color: var(--muted);font-size:14px;}
</style>
</head>
<body>
<header>
  <div class="nav">
    <div style="display:flex;align-items:center;gap:12px;">
      <img src="../images/Quizzeo-logo.png" alt="logo" style="height: 35px;">
    </div>
    <div></div>
    <a href="../dashboard/dashboard-utilisateur.php" class="logout">⬅ Retour</a>
  </div>
</header>

<main class="container">
  <div class="section">
    <h3>📝 Quiz : <?= htmlspecialchars($quiz['titre']); ?></h3>
    <form action="submit_answers.php" method="POST">
      <input type="hidden" name="quiz_id" value="<?= $quiz['id']; ?>">
      <?php foreach ($questions as $index => $q): ?>
        <div class="question">
          <p><?= ($index+1) . ". " . htmlspecialchars($q['question_text']); ?></p>
          <?php if ($q['type'] === 'qcm' && isset($answers_by_question[$q['id']])): ?>
            <?php foreach ($answers_by_question[$q['id']] as $ans): ?>
              <label>
                <input type="radio" name="q<?= $q['id']; ?>" value="<?= $ans['id']; ?>" required>
                <?= htmlspecialchars($ans['answer_text']); ?>
              </label>
            <?php endforeach; ?>
          <?php else: ?>
            <input type="text" name="q<?= $q['id']; ?>" required style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn">Soumettre mes réponses</button>
    </form>
  </div>
</main>

<footer>
  &copy; 2025 Quizzeo — Tous droits réservés
</footer>
</body>
</html>
