<?php
session_start();

// Vérifier que l'utilisateur est bien une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    header("Location: login.php");
    exit;
}

// Connexion PDO
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

// ID utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer quiz créés
$stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE user_id = :user_id ORDER BY date_creation DESC");
$stmt_quiz->execute(['user_id' => $user_id]);
$quizzes = $stmt_quiz->fetchAll(PDO::FETCH_ASSOC);

// Récupérer résultats des étudiants
$stmt_results = $pdo->prepare("
    SELECT u.nom AS etudiant, u.prenom AS prenom, q.titre AS quiz, SUM(a.score) AS note
    FROM user_quiz_answers a
    JOIN users u ON a.user_id = u.id
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE q.user_id = :user_id
    GROUP BY a.user_id, a.quiz_id
    ORDER BY q.date_creation DESC
");
$stmt_results->execute(['user_id' => $user_id]);
$results = $stmt_results->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizzeo — Dashboard Entreprise</title>
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
  --max-width: 1100px;
}

/* Base */
*{box-sizing:border-box;margin:0;padding:0;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); line-height:1.45; min-height:100vh;}
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
header .brand-info{display:flex; flex-direction: column; justify-content: center;}
header .brand-info .title{font-weight:700; font-size:18px;}
header .brand-info .subtitle{font-size:12px; color:var(--muted); margin-top:2px;}
header .logout{padding:10px 20px; background: linear-gradient(90deg,var(--accent-2),#e26666); color:white; border-radius:12px; font-weight:600; box-shadow: var(--shadow-sm); transition: all .25s ease; text-align:center;}
header .logout:hover{transform:translateY(-2px);}

/* Main */
main{padding:120px 0 40px;}
section{background: var(--card); padding:30px; border-radius: var(--radius); margin-bottom:30px; box-shadow: var(--shadow-lg); backdrop-filter: blur(12px); transition: transform .3s;}
section:hover{transform: translateY(-3px);}
section h3{font-weight:700; font-size:24px; margin-bottom:20px; color: var(--text);}
.btn, .btn-outline{display:inline-block; padding:10px 18px; border-radius:12px; font-weight:600; cursor:pointer; border:none; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s; text-decoration:none; text-align:center;}
.btn{background:linear-gradient(90deg,var(--accent-3),#f5aa2e); color:white;}
.btn:hover{transform:translateY(-2px);}
.btn-outline{background:transparent; border:1px solid rgba(15,23,36,0.1); color:var(--text);}
.btn-outline:hover{transform:translateY(-2px);}
table{width:100%; border-collapse: separate; border-spacing:0 8px; margin-top:10px;}
table th, table td{padding:12px 14px; text-align:left;}
table th{background: var(--accent); font-weight:600; color:white;}
table tr{background:white; border-radius:12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition: background .25s;}
table tr:hover{background: var(--accent-light);}
.Filtrage{display:flex; gap:20px; margin-top:10px; margin-bottom:10px;}
.Filtrage label, .Filtrage select{font-size:16px; font-weight:500;}
.Filtrage select{padding:6px 10px; border-radius:10px; border:none; background: var(--glass); box-shadow: var(--shadow-sm);}
#showMore,#showLess,#showMoreResults,#showLessResults{color: var(--accent-3); cursor:pointer; text-align:center; margin-top:12px; font-weight:600;}
#showMore:hover,#showLess:hover,#showMoreResults:hover,#showLessResults:hover{text-decoration: underline;}
footer{ text-align:center; padding:20px 0; color: var(--muted); font-size:14px; }

/* Statuts */
.status { padding:4px 10px; border-radius:8px; font-weight:600; color:white; display:inline-block; font-size:14px;}
.status-termine { background-color:#4CAF50; }
.status-en-cours { background-color:#FF9800; }
.status-brouillon { background-color:#9E9E9E; }

/* Responsive */
@media(max-width:850px){ main{padding:80px 15px 40px;} table th, table td{padding:10px;} .Filtrage{flex-direction:column; gap:10px;} }
</style>
</head>
<body>

<header>
  <div class="nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="images/Quizzeo-logo.png" alt="logo" style="height: 35px;">
    </div>
    <div></div>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<main class="container">

<!-- Quiz Créés -->
<section>
  <h3>📘 Quiz Créés</h3>
  <a href="quiz/create_quiz_entreprise.php" class="btn">➕ Créer un nouveau quiz</a>

  <table id="quizTable">
    <tr><th>Titre</th><th>Statut</th><th>Réponses</th><th>Action</th></tr>
    <?php foreach ($quizzes as $quiz):
        $quiz_id = $quiz['id'];
        $res_stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM user_quiz_answers WHERE quiz_id = :quiz_id");
        $res_stmt->execute(['quiz_id' => $quiz_id]);
        $res_count = $res_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $statut = $quiz['statut'];
        $action_text = ($statut == "terminé") ? "Voir résultats" : "Voir détails";
        $status_class = ($statut=='terminé') ? 'status-termine' : 
                        (($statut=='en cours') ? 'status-en-cours' : 'status-brouillon');
    ?>
    <tr>
        <td><?= htmlspecialchars($quiz['titre']); ?></td>
        <td><span class="status <?= $status_class ?>"><?= htmlspecialchars($statut); ?></span></td>
        <td><?= $res_count; ?></td>
        <td><a class="btn-outline" href="quiz/quiz_detail_entreprise.php?quiz_id=<?= $quiz_id; ?>"><?= $action_text; ?></a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$quizzes) echo "<tr><td colspan='4'>Aucun quiz créé pour le moment.</td></tr>"; ?>
  </table>
  <p id="showMore">Plus de détails...</p>
  <p id="showLess">Moins de détails...</p>
</section>

<!-- Résultats Étudiants -->
<section>
  <h3>📊 Résultats des Étudiants</h3>
  <div class="Filtrage">
    <div>
      <label for="filterStudent">Filtrer par étudiant:</label>
      <select id="filterStudent">
        <option value="all">Tous</option>
        <?php
        $students = [];
        foreach ($results as $row) { $students[$row['etudiant']] = true; }
        foreach ($students as $name => $v) echo "<option>".htmlspecialchars($name)."</option>";
        ?>
      </select>
    </div>
    <div>
      <label for="filterQuiz">Filtrer par quiz:</label>
      <select id="filterQuiz">
        <option value="all">Tous</option>
        <?php
        $quizzes_seen = [];
        foreach ($results as $row) {
            if (!in_array($row['quiz'], $quizzes_seen)) {
                echo "<option>".htmlspecialchars($row['quiz'])."</option>";
                $quizzes_seen[] = $row['quiz'];
            }
        }
        ?>
      </select>
    </div>
  </div>

  <table id="resultsTable">
    <tr><th>Étudiant</th><th>Quiz</th><th>Note / Pourcentage</th></tr>
    <?php foreach ($results as $row): ?>
    <tr class="result-row">
        <td><?= htmlspecialchars($row['etudiant']." ".$row['prenom']); ?></td>
        <td><?= htmlspecialchars($row['quiz']); ?></td>
        <td><?= htmlspecialchars($row['note']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$results) echo "<tr><td colspan='3'>Aucun résultat pour le moment.</td></tr>"; ?>
  </table>

  <p id="showMoreResults">Plus de détails...</p>
  <p id="showLessResults">Moins de détails...</p>
</section>

</main>

<footer class="container">&copy; 2025 Quizzeo — Tous droits réservés</footer>

<script>
const quizRows = document.querySelectorAll("#quizTable tr:not(:first-child)");
const resultRows = document.querySelectorAll(".result-row");
let showingAll = false;

function updateTables(){
  let qCount=0;
  quizRows.forEach(row=>{
    if(!showingAll && qCount>=3){row.style.display='none';} 
    else {row.style.display='table-row'; qCount++;}
  });
  document.getElementById('showMore').style.display = (!showingAll && quizRows.length>3)?'block':'none';
  document.getElementById('showLess').style.display = showingAll?'block':'none';

  let rCount=0;
  resultRows.forEach(row=>{
    if(!showingAll && rCount>=3){row.style.display='none';} 
    else {row.style.display='table-row'; rCount++;}
  });
  document.getElementById('showMoreResults').style.display = (!showingAll && resultRows.length>3)?'block':'none';
  document.getElementById('showLessResults').style.display = showingAll?'block':'none';
}

document.getElementById('showMore').addEventListener('click',()=>{showingAll=true; updateTables();});
document.getElementById('showLess').addEventListener('click',()=>{showingAll=false; updateTables();});
document.getElementById('showMoreResults').addEventListener('click',()=>{showingAll=true; updateTables();});
document.getElementById('showLessResults').addEventListener('click',()=>{showingAll=false; updateTables();});
updateTables();
</script>

</body>
</html>

<?php $pdo = null; ?>
