<?php
session_start();

// Vérifier que l'utilisateur est bien une école
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: login.php");
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

// Récupérer les infos du quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :quiz_id AND user_id = :user_id");
$stmt->execute(['quiz_id' => $quiz_id, 'user_id' => $user_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("Quiz introuvable ou non autorisé.");
}

// Récupérer les questions du quiz
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
$stmtQ->execute(['quiz_id' => $quiz_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réponses données par les étudiants
$stmtA = $pdo->prepare("
    SELECT u.nom, u.prenom, q.question_text, a.answer_text, a.score
    FROM user_quiz_answers a
    JOIN users u ON a.user_id = u.id
    JOIN questions q ON a.question_id = q.id
    WHERE q.quiz_id = :quiz_id
    ORDER BY u.nom, u.prenom
");
$stmtA->execute(['quiz_id' => $quiz_id]);
$responses = $stmtA->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
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
  --max-width: 900px;
}

/* Reset & base */
*{box-sizing:border-box;margin:0;padding:0;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); line-height:1.45; min-height:100vh; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;}
a{color:inherit; text-decoration:none;}
.container{width:95%; max-width: var(--max-width); margin:0 auto;}

/* Header */
header{
  width:100%;
  position: sticky;
  top:0;
  z-index:30;
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(8px);
  box-shadow: var(--shadow-sm);
}
header .nav{
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 20px;
  padding: 10px 24px;
}
header .logo{
  width:46px;
  height:46px;
  border-radius:10px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: linear-gradient(135deg,var(--accent),#cc7af6);
  box-shadow: var(--shadow-sm);
  color:white;
  font-weight:700;
  font-family:"Fredoka One",sans-serif;
  font-size:20px;
}
header .brand-info{
  display:flex;
  flex-direction: column;
  justify-content: center;
}
header .brand-info .title{
  font-weight:700; 
  font-size:18px;
}
header .brand-info .subtitle{
  font-size:12px; 
  color:var(--muted); 
  margin-top:2px;
}
header .logout{
  padding:10px 20px;
  background: linear-gradient(90deg,var(--accent-2),#e26666);
  color:white;
  border-radius:12px;
  font-weight:600;
  box-shadow: var(--shadow-sm);
  transition: all .25s ease;
  text-align:center;
}
header .logout:hover{transform:translateY(-2px);}

/* Main */
main{padding:120px 0 40px;}

/* Sections */
section{
  background: var(--card);
  padding:30px;
  border-radius: var(--radius);
  margin-bottom:30px;
  box-shadow: var(--shadow-lg);
  backdrop-filter: blur(12px);
  transition: transform .3s;
}
section:hover{transform: translateY(-3px);}
section h3{
  font-weight:700;
  font-size:24px;
  margin-bottom:20px;
  color: var(--text);
}

/* Question Blocks */
.question-block{
  margin-bottom:20px;
  padding:15px;
  background: rgba(255,255,255,0.9);
  border-radius:12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.question-block p{margin:6px 0;}
.question-block .free-answer{
  background: #f0f0f0;
  padding:10px;
  border-radius:10px;
  font-style: italic;
  color: #555;
  margin-top:6px;
}

/* Table */
table{width:100%; border-collapse: separate; border-spacing:0 8px; margin-top:10px;}
table th, table td{padding:12px 14px; text-align:left;}
table th{background: var(--accent); font-weight:600; color:white;}
table tr{background:white; border-radius:12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition: background .25s;}
table tr:hover{background: var(--accent-light);}
.Filtrage{display:flex; gap:20px; margin-top:10px; margin-bottom:10px;}
.Filtrage label, .Filtrage select{font-size:16px; font-weight:500;}
.Filtrage select{padding:6px 10px; border-radius:10px; border:none; background: var(--glass); box-shadow: var(--shadow-sm);}
footer{ text-align:center; padding:20px 0; color: var(--muted); font-size:14px; }

/* Responsive */
@media(max-width:600px){ main{padding:120px 15px 40px;} .Filtrage{flex-direction:column; gap:10px;} }
</style>

</head>
<body>

<header>
  <div class="nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="../images/Quizzeo-logo.png" alt="logo" style="height: 35px;">
    </div>
    <div></div>
    <a href="../logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<main class="container">
<h2>Quiz : <?php echo htmlspecialchars($quiz['titre']); ?></h2>
<p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
<p><strong>Statut :</strong> <?php echo htmlspecialchars($quiz['statut']); ?></p>

<h3>Questions</h3>
<?php if($questions): ?>
<table>
<tr><th>#</th><th>Question</th></tr>
<?php foreach($questions as $i => $q): ?>
<tr>
<td><?php echo $i+1; ?></td>
<td><?php echo htmlspecialchars($q['question_text']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>Aucune question pour ce quiz.</p>
<?php endif; ?>

<h3>Réponses des étudiants</h3>
<div class="Filtrage">
  <div>
    <label for="filterStudent">Filtrer par étudiant :</label>
    <select id="filterStudent">
      <option value="all">Tous</option>
      <?php
      $students = [];
      foreach($responses as $r) { $students[$r['nom'].' '.$r['prenom']] = true; }
      foreach($students as $name => $v) echo "<option>".htmlspecialchars($name)."</option>";
      ?>
    </select>
  </div>
  <div>
    <label for="filterQuestion">Filtrer par question :</label>
    <select id="filterQuestion">
      <option value="all">Toutes</option>
      <?php
      $q_seen = [];
      foreach($responses as $r) {
          $qname = $r['question_text'];
          if(!in_array($qname, $q_seen)) { echo "<option>".htmlspecialchars($qname)."</option>"; $q_seen[] = $qname; }
      }
      ?>
    </select>
  </div>
</div>

<table id="responsesTable">
<tr><th>Étudiant</th><th>Question</th><th>Réponse</th><th>Score</th></tr>
<?php
foreach($responses as $r):
    $student = htmlspecialchars($r['nom'].' '.$r['prenom']);
    $question = htmlspecialchars($r['question_text']);
    $answer = htmlspecialchars($r['answer_text']);
    $score = htmlspecialchars($r['score']);
    echo "<tr class='response-row' data-student='$student' data-question='$question'>
            <td>$student</td>
            <td>$question</td>
            <td>$answer</td>
            <td>$score</td>
          </tr>";
endforeach;
?>
</table>

<a class="btn-outline" href="../dashboard-entreprise.php">⬅ Retour au Dashboard</a>
</main>

<footer class="container">&copy; 2025 Quizzeo — Tous droits réservés</footer>

<script>
const filterStudent = document.getElementById('filterStudent');
const filterQuestion = document.getElementById('filterQuestion');
const rows = document.querySelectorAll('.response-row');

function applyFilters() {
    const student = filterStudent.value;
    const question = filterQuestion.value;
    rows.forEach(row => {
        const matchesStudent = (student === 'all' || row.dataset.student === student);
        const matchesQuestion = (question === 'all' || row.dataset.question === question);
        row.style.display = (matchesStudent && matchesQuestion) ? 'table-row' : 'none';
    });
}

filterStudent.addEventListener('change', applyFilters);
filterQuestion.addEventListener('change', applyFilters);
</script>

</body>
</html>

<?php $pdo = null; ?>
