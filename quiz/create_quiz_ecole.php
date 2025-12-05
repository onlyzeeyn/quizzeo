<?php
session_start();

// Vérifier que l'utilisateur est connecté et a le rôle "ecole"
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: ../login.php");
    exit;
}

// Connexion à la base de données
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $statut = $_POST['status'] === 'published' ? 'lancé' : "en cours d'écriture";
    $user_id = $_SESSION['user_id'];

    // Insérer le quiz
    $stmt_quiz = $pdo->prepare("INSERT INTO quizzes (user_id, titre, description, statut) VALUES (:user_id, :titre, :description, :statut)");
    $stmt_quiz->execute([
        'user_id' => $user_id,
        'titre' => $titre,
        'description' => $description,
        'statut' => $statut
    ]);

    $quiz_id = $pdo->lastInsertId();

    // Insérer les questions et réponses
    foreach ($_POST as $key => $value) {
        if (preg_match('/^question_(\d+)$/', $key, $matches)) {
            $q_num = $matches[1];
            $question_text = $value;
            $correct_answer = $_POST["question_{$q_num}_correct"];

            // Ajouter la question
            $stmt_question = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (:quiz_id, :question_text)");
            $stmt_question->execute([
                'quiz_id' => $quiz_id,
                'question_text' => $question_text
            ]);
            $question_id = $pdo->lastInsertId();

            // Ajouter les réponses
            for ($i = 1; $i <= 3; $i++) {
                $rep_text = $_POST["question_{$q_num}_rep{$i}"];
                $is_correct = ($rep_text === $correct_answer) ? 1 : 0;
                $stmt_answer = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, :is_correct)");
                $stmt_answer->execute([
                    'question_id' => $question_id,
                    'answer_text' => $rep_text,
                    'is_correct' => $is_correct
                ]);
            }
        }
    }

    // Redirection vers le dashboard école
    header("Location: ../dashboard-ecole.php");
    exit;
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizeo — Créer un Quiz</title>
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
</style>


</head>
<body>
  <header>
    <div class="nav">
      <div style="display:flex; align-items:center; gap:12px;">
        <img src="../images/Quizzeo-logo.png" alt="logo" style="height: 35px;">
      </div>
      <div></div>
      <a href="/logout.php" class="logout">Déconnexion</a>
    </div>
  </header>

  <main class="container">
    <section>
      <h3>➕ Créer un Nouveau Quiz</h3>
      <form id="quizForm" method="POST">
        <label for="title">Titre du Quiz :</label>
        <input type="text" id="title" name="title" placeholder="Ex : Mathématiques - Chapitre 3" required>

        <label for="description">Description :</label>
        <textarea id="description" name="description" placeholder="Décrivez le quiz..." rows="3"></textarea>

        <div id="questionsContainer"></div>

        <button type="button" id="addQuestionBtn" class="btn" style="margin-bottom:15px;">➕ Ajouter une question</button>

        <br>

        <label for="status">Statut :</label>
        <select id="status" name="status">
          <option value="draft">Brouillon</option>
          <option value="published">Publié</option>
        </select>

        <button type="submit" class="btn">Créer le Quiz</button>
        <a href="../dashboard-ecole.php" class="annuler">⬅ Annuler</a>
      </form>
    </section>
  </main>

  <footer class="container">
    &copy; 2025 Quizio — Tous droits réservés
  </footer>

  <script>
    const container = document.getElementById('questionsContainer');
    const addBtn = document.getElementById('addQuestionBtn');
    let questionCount = 0;

    addBtn.addEventListener('click', () => {
      questionCount++;
      const div = document.createElement('div');
      div.classList.add('question-block');
      div.innerHTML = `
        <label>Question ${questionCount} :</label>
        <input type="text" name="question_${questionCount}" placeholder="Tapez votre question" required>
        <label>Réponse 1 :</label>
        <input type="text" name="question_${questionCount}_rep1" placeholder="Réponse 1" required>
        <label>Réponse 2 :</label>
        <input type="text" name="question_${questionCount}_rep2" placeholder="Réponse 2" required>
        <label>Réponse 3 :</label>
        <input type="text" name="question_${questionCount}_rep3" placeholder="Réponse 3" required>
        <label>Bonne réponse :</label>
        <input type="text" name="question_${questionCount}_correct" placeholder="Ex : Réponse 2" required>
      `;
      container.appendChild(div);
    });

    // Déconnexion
    const logoutButton = document.querySelector('.logout');
    logoutButton.addEventListener('click', function(event) {
        alert('Vous êtes déconnecté');
    });
  </script>
</body>
</html>
