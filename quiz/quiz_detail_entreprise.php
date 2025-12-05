<?php
session_start();

// Vérifier que l'utilisateur est bien une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['quiz_id'])) {
    die("Quiz non spécifié.");
}

$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

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

// Récupérer le quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :quiz_id AND user_id = :user_id");
$stmt->execute(['quiz_id' => $quiz_id, 'user_id' => $user_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("Quiz introuvable.");
}

// Récupération des questions
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
$stmtQ->execute(['quiz_id' => $quiz_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Charger les réponses associées
$questions_with_answers = [];

foreach ($questions as $q) {
    $stmtA = $pdo->prepare("SELECT * FROM answers WHERE question_id = :question_id");
    $stmtA->execute(['question_id' => $q['id']]);
    $answers = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    $q['answers'] = $answers;
    $questions_with_answers[] = $q;
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<title>Détails du Quiz — Entreprise</title>
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
}

body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); font-family:"Inter"; padding:20px;}
.container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:16px;box-shadow:var(--shadow-lg);}
h2{margin-bottom:10px;}
h3{margin-top:30px;}
.correct{color:green;font-weight:bold;}
.incorrect{color:#555;}
.btn{display:inline-block;margin-top:20px;padding:12px 18px;background:var(--accent);color:white;border-radius:12px;text-decoration:none;}
</style>

</head>
<body>

<div class="container">

<h2>Quiz : <?php echo htmlspecialchars($quiz['titre']); ?></h2>
<p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
<p><strong>Statut :</strong> <?php echo htmlspecialchars($quiz['statut']); ?></p>

<hr><br>
<h3>Réponses des étudiants</h3>

<?php
// --- Récupérer toutes les réponses des utilisateurs pour ce quiz ---
$stmt = $pdo->prepare("
    SELECT 
        u.nom, u.prenom,
        q.question_text,
        a.answer_text,
        ua.answer_text AS libre_text,
        ua.score
    FROM user_quiz_answers ua
    JOIN users u ON ua.user_id = u.id
    JOIN questions q ON ua.question_id = q.id
    LEFT JOIN answers a ON ua.answer_id = a.id
    WHERE ua.quiz_id = :quiz_id
    ORDER BY u.nom, q.id
");
$stmt->execute(['quiz_id' => $quiz_id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$details) {
    echo "<p>Aucun étudiant n’a encore répondu.</p>";
} else {

    $current_student = "";

    foreach ($details as $d):

        $student_name = $d['nom'] . " " . $d['prenom'];

        // Nouveau bloc étudiant
        if ($current_student != $student_name) {
            if ($current_student != "") echo "</div>";
            echo "<div style='padding:15px;background:#fff;border-radius:10px;margin-bottom:20px;'>";
            echo "<h4>Étudiant : " . htmlspecialchars($student_name) . "</h4>";
            $current_student = $student_name;
        }

        echo "<p>";
        echo "<strong>Question :</strong> " . htmlspecialchars($d['question_text']) . "<br>";

        // Réponse du QCM
        if (!empty($d['answer_text'])) {
            echo "<strong>Réponse :</strong> " . htmlspecialchars($d['answer_text']) . "<br>";
        }

        // Réponse libre
        if (!empty($d['libre_text'])) {
            echo "<strong>Réponse libre :</strong> " . htmlspecialchars($d['libre_text']) . "<br>";
        }

        echo "<strong>Score :</strong> " . htmlspecialchars($d['score']);
        echo "</p>";

    endforeach;

    echo "</div>"; // ferme le dernier étudiant
}
?>



<hr>

<h3>Questions</h3>

<?php foreach ($questions_with_answers as $index => $q): ?>
    <h3>Question <?php echo $index + 1; ?></h3>

    <p><strong><?php echo htmlspecialchars($q['question_text']); ?></strong></p>

    <ul>
        <?php foreach ($q['answers'] as $a): ?>
            <li class="<?php echo $a['is_correct'] ? 'correct' : 'incorrect'; ?>">
                <?php echo htmlspecialchars($a['answer_text']); ?>
                <?php if ($a['is_correct']): ?> ✔<?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<?php endforeach; ?>

<a class="btn" href="../dashboard-entreprise.php">⬅ Retour au Dashboard</a>

</div>
</body>
</html>
