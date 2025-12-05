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

// Récupérer les informations de l'utilisateur
$stmt_user = $pdo->prepare("SELECT nom, prenom, email FROM users WHERE id = :id");
$stmt_user->execute(['id' => $_SESSION['user_id']]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Récupérer tous les quiz disponibles
$stmt_quiz = $pdo->prepare("SELECT * FROM quizzes ORDER BY date_creation DESC");
$stmt_quiz->execute();
$quizzes = $stmt_quiz->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les résultats de l'utilisateur
$stmt_results = $pdo->prepare("
    SELECT q.titre, SUM(a.score) AS note, COUNT(qs.id) AS total_questions
    FROM user_quiz_answers a
    JOIN quizzes q ON a.quiz_id = q.id
    JOIN questions qs ON qs.quiz_id = q.id
    WHERE a.user_id = :user_id
    GROUP BY a.quiz_id
");
$stmt_results->execute(['user_id' => $_SESSION['user_id']]);
$results = $stmt_results->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau associatif pour accéder facilement aux scores
$scores = [];
foreach ($results as $r) {
    $scores[$r['titre']] = $r['note'] . ' / ' . $r['total_questions'];
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizzeo — Dashboard Utilisateur</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
:root {
  --bg-gradient: linear-gradient(180deg, #f0f2f7, #e8e5f8);
  --card-bg: rgba(255,255,255,0.85);
  --text: #0f1724;
  --muted: #7b7f87;
  --accent: #7b6df6;
  --accent-light: #a88cff;
  --accent-2: #F38788;
  --accent-3: #FAB540;
  --radius: 20px;
  --shadow-lg: 0 15px 35px rgba(16,24,40,0.08);
  --shadow-sm: 0 6px 18px rgba(16,24,40,0.05);
}
* { box-sizing: border-box; margin:0; padding:0; font-family:'Inter', sans-serif; text-decoration:none;}
body { background: var(--bg-gradient); color: var(--text); min-height:100vh;}
header{width:100%; position: sticky; top:0; z-index:30; background: rgba(255,255,255,0.6); backdrop-filter: blur(8px); box-shadow: var(--shadow-sm);}
header .nav{display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:20px; padding:10px 24px;}
header .logo{width:46px;height:46px;border-radius:10px; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg,var(--accent),#cc7af6); color:white; font-weight:700; font-family:"Fredoka One"; font-size:20px;}
header .brand-info{display:flex; flex-direction: column; justify-content:center;}
header .brand-info .title{font-weight:700; font-size:18px;}
header .brand-info .subtitle{font-size:12px; color:var(--muted); margin-top:2px;}
.logout-btn {padding:12px 20px; background: linear-gradient(90deg, var(--accent), var(--accent-light)); color:white; border-radius:14px; font-weight:600; cursor:pointer; box-shadow: var(--shadow-sm);}
.logout-btn:hover {background: linear-gradient(90deg, var(--accent-light), var(--accent)); transform: translateY(-2px) scale(1.02);}
main {padding:30px 40px 40px 40px; max-width:1100px; margin:0 auto; display:flex; flex-direction:column; gap:30px;}
section {background: var(--card-bg); padding:30px; border-radius: var(--radius); box-shadow: var(--shadow-lg); backdrop-filter: blur(12px);}
section:hover {transform: translateY(-4px); box-shadow:0 20px 40px rgba(16,24,40,0.12);}
section h3 {font-size:24px; margin-bottom:16px; font-weight:600;}
.btn {display:inline-block; padding:12px 20px; background: linear-gradient(90deg, var(--accent-3), var(--accent-2)); color:white; border-radius:14px; font-weight:600; cursor:pointer; box-shadow: var(--shadow-sm); text-decoration:none;}
.btn:hover {transform: translateY(-2px) scale(1.03); background: linear-gradient(90deg, var(--accent-2), var(--accent-3));}
table {width:100%; border-collapse: collapse; margin-top:10px; border-radius: var(--radius); overflow:hidden;}
table th, table td {padding:14px; text-align:left;}
table th {background: var(--accent); color:white; font-weight:600;}
table tr {background:white; border-bottom:1px solid #eee;}
footer {text-align:center; padding:20px 0; color: var(--muted); font-size:14px;}
@media(max-width:768px){main{padding:30px 20px 20px 20px;} table th, table td{font-size:14px;}}
</style>
</head>
<body>

<header>
  <div class="nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="images/Quizzeo-logo.png" alt="logo" style="height: 35px;">
    </div>
    <div></div>
    <a href="logout.php" class="logout-btn">Déconnexion</a>
  </div>
</header>

<main>
  <section>
    <h3>👤 Mon Profil</h3>
    <p><strong>Nom :</strong> <?= htmlspecialchars($user_info['nom']); ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($user_info['prenom']); ?></p>
    <p><strong>Email :</strong> <?= htmlspecialchars($user_info['email']); ?></p>
  </section>

  <section>
    <h3>📘 Quiz disponibles</h3>
    <table>
      <tr>
        <th>Titre</th>
        <th>Statut</th>
        <th>Action</th>
      </tr>
      <?php foreach ($quizzes as $quiz): 
          $title = htmlspecialchars($quiz['titre']);
          $status = htmlspecialchars($quiz['statut']);
          $btn_text = ($status == "terminé") ? "Revoir" : "Répondre";
          $quiz_id = $quiz['id'];
      ?>
      <tr>
        <td><?= $title; ?></td>
        <td><?= $status; ?></td>
        <td>
          <a class="btn" href="quiz/answer_quiz.php?quiz_id=<?= $quiz_id; ?>"><?= $btn_text; ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section>
    <h3>📊 Historique des réponses</h3>
    <table>
      <tr>
        <th>Quiz</th>
        <th>Résultat</th>
      </tr>
      <?php foreach ($scores as $quiz => $note): ?>
      <tr>
        <td><?= htmlspecialchars($quiz); ?></td>
        <td><?= htmlspecialchars($note); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </section>
</main>

<footer>
  &copy; 2025 Quizzeo — Tous droits réservés
</footer>

</body>
</html>
<?php $pdo = null; ?>
