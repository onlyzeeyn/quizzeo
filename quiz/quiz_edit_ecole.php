<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['quiz_id'])) {
    die("Quiz ID manquant.");
}

$quiz_id = $_GET['quiz_id'];

// Connexion DB
$pdo = new PDO("mysql:host=localhost;dbname=quizzeo;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Charger le quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $quiz_id, 'uid' => $_SESSION['user_id']]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz introuvable ou non autorisé.");
}

// Charger questions + réponses
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :id");
$stmtQ->execute(['id' => $quiz_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$answers = [];
foreach ($questions as $q) {
    $stmtA = $pdo->prepare("SELECT * FROM answers WHERE question_id = :qid");
    $stmtA->execute(['qid' => $q['id']]);
    $answers[$q['id']] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Modifier Quiz</title>

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

/* Form elements */
input, textarea, select{
  width:100%;
  padding:12px 14px;
  margin-bottom:18px;
  border-radius:12px;
  border:1px solid rgba(15,23,36,0.1);
  font-size:14px;
}
textarea{resize:vertical;}

/* Buttons */
.btn, .annuler{
  display:inline-block;
  padding:10px 18px;
  border-radius:12px;
  font-weight:600;
  cursor:pointer;
  border:none;
  box-shadow: var(--shadow-sm);
  transition: transform .2s, box-shadow .2s;
}
.btn{
  background:linear-gradient(90deg,var(--accent-3),#f5aa2e);
  color:white;
}
.btn:hover{transform:translateY(-2px);}
.annuler{
  background:transparent;
  border:1px solid rgba(15,23,36,0.1);
  color:var(--text);
  margin-left:10px;
}
.annuler:hover{transform:translateY(-2px);}

/* Question Blocks */
.question-block{
  margin-bottom:20px;
  padding:15px;
  background: rgba(255,255,255,0.9);
  border-radius:12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.question-block label{font-weight:600; margin-bottom:6px; display:block;}
.question-block input{margin-bottom:12px;}

/* Footer */
footer{
  text-align:center;
  padding:24px 0;
  color:var(--muted);
  font-size:14px;
}

/* Responsive */
@media (max-width:600px){
  main{padding:120px 15px 40px;}
  header .nav{grid-template-columns: auto 1fr auto; gap:10px; padding:10px 16px;}
  section{padding:20px;}
}
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

*{box-sizing:border-box;margin:0;padding:0;font-family:"Inter";}
body{background: linear-gradient(180deg, var(--bg), #eef0fb);}
.container{max-width:900px;margin:auto;width:95%;}

header{position:sticky;top:0;z-index:20;background:rgba(255,255,255,0.6);backdrop-filter:blur(8px);box-shadow:var(--shadow-sm);}
header .nav{display:grid;grid-template-columns:auto 1fr auto;padding:10px 24px;align-items:center;}
header .logo{width:46px;height:46px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#cc7af6);display:flex;align-items:center;justify-content:center;color:white;font-family:"Fredoka One";font-size:20px;}
header .logout{padding:10px 20px;border-radius:12px;background:linear-gradient(90deg,var(--accent-2),#e26666);color:white;}

section{background:var(--card);padding:30px;margin-top:25px;border-radius:16px;box-shadow:var(--shadow-lg);}
h3{font-size:24px;margin-bottom:20px;font-weight:700;}

input, textarea, select{width:100%;padding:12px;border-radius:12px;margin-bottom:18px;border:1px solid #ddd;}

.btn{padding:10px 18px;border-radius:12px;background:linear-gradient(90deg,var(--accent-3),#f5aa2e);color:white;font-weight:600;margin-top:10px;cursor:pointer;display:inline-block;}
.annuler{padding:10px 18px;border-radius:12px;border:1px solid #bbb;color:#222;margin-left:10px;font-weight:600;}

.question-block{padding:15px;background:white;border-radius:12px;margin-bottom:20px;box-shadow:0 4px 10px rgba(0,0,0,0.05);}
.delete-question{color:#d33;font-weight:700;cursor:pointer;margin-top:5px;display:inline-block;}
</style>
</head>

<body>

<header>
  <div class="nav">
    <div style="display:flex;gap:12px;">
      <div class="logo">QZ</div>
      <div>
        <div class="title">Quizzeo</div>
        <div class="subtitle" style="font-size:12px;color:#777;">Modifier votre quiz</div>
      </div>
    </div>
    <div></div>
    <a href="../logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<main class="container">
  <section>
    <h3>✏️ Modifier le Quiz</h3>

    <form action="update_quiz.php" method="POST">
      <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

      <label>Titre :</label>
      <input type="text" name="title" value="<?= htmlspecialchars($quiz['titre']) ?>" required>

      <label>Description :</label>
      <textarea name="description"><?= htmlspecialchars($quiz['description']) ?></textarea>

      <label>Statut :</label>
      <select name="status">
        <option value="draft" <?= $quiz['statut'] == "en cours d'écriture" ? "selected" : "" ?>>Brouillon</option>
        <option value="published" <?= $quiz['statut'] == "lancé" ? "selected" : "" ?>>Publié</option>
      </select>

      <h3>Questions</h3>

      <div id="questionsContainer">
        <?php foreach ($questions as $index => $q): ?>
          <div class="question-block">
            <label>Question :</label>
            <input type="text" name="question_<?= $q['id'] ?>" value="<?= htmlspecialchars($q['question_text']) ?>">

            <?php foreach ($answers[$q['id']] as $a): ?>
              <label>Réponse :</label>
              <input type="text" name="answer_<?= $a['id'] ?>" value="<?= htmlspecialchars($a['answer_text']) ?>">

              <label>
                <input type="radio" name="correct_<?= $q['id'] ?>" value="<?= $a['id'] ?>" <?= $a['is_correct'] ? "checked" : "" ?>>
                Correcte
              </label>
              <br><br>
            <?php endforeach; ?>

            <span class="delete-question" onclick="deleteQuestion(<?= $q['id'] ?>)">🗑 Supprimer la question</span>
          </div>
        <?php endforeach; ?>
      </div>

      <button type="button" id="addQuestionBtn" class="btn">➕ Ajouter une question</button>

      <br><br>

      <button type="submit" class="btn">💾 Enregistrer</button>
      <a href="../dashboard/ecole.php" class="annuler">Annuler</a>
    </form>
  </section>
</main>

<script>
let newQuestionCount = 0;

document.getElementById('addQuestionBtn').addEventListener('click', () => {
    newQuestionCount++;
    let id = "new_" + newQuestionCount;

    const block = document.createElement("div");
    block.className = "question-block";

    block.innerHTML = `
      <label>Nouvelle question :</label>
      <input type="text" name="question_${id}" required>

      <label>Réponse 1 :</label>
      <input type="text" name="answer_${id}_1" required>

      <label>Réponse 2 :</label>
      <input type="text" name="answer_${id}_2" required>

      <label>Réponse 3 :</label>
      <input type="text" name="answer_${id}_3" required>

      <label>Bonne réponse :</label>
      <input type="number" name="correct_${id}" min="1" max="3" required>

      <span class="delete-question" onclick="this.parentElement.remove()">🗑 Supprimer</span>
    `;

    document.getElementById("questionsContainer").appendChild(block);
});

// supprimer question existante
function deleteQuestion(id) {
    if (confirm("Supprimer cette question ?")) {
        window.location.href = "delete_question.php?qid=" + id + "&quiz_id=<?= $quiz_id ?>";
    }
}
</script>

</body>
</html>
