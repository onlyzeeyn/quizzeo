<?php
session_start();

// Vérifier que l'utilisateur est bien une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'entreprise') {
    header("Location: login.php");
    exit;
}

// Connexion à la base de données
$host = "localhost";
$db = "quizzeo";
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
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? "en cours d'écriture"; // Valeur par défaut correspondant à l'ENUM
    $user_id = $_SESSION['user_id'];

    if ($titre) {
        // Insérer le quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (user_id, titre, description, statut) VALUES (:user_id, :titre, :description, :statut)");
        $stmt->execute([
            'user_id' => $user_id,
            'titre' => $titre,
            'description' => $description,
            'statut' => $status
        ]);
        $quiz_id = $pdo->lastInsertId();

        // Insérer les questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                $type = isset($q['answers']) ? 'qcm' : 'libre';
                $stmtQ = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, points, type) VALUES (:quiz_id, :question_text, 1, :type)");
                $stmtQ->execute([
                    'quiz_id' => $quiz_id,
                    'question_text' => $q['text'],
                    'type' => $type
                ]);
                $question_id = $pdo->lastInsertId();

                // Insérer les réponses si QCM
                if (isset($q['answers']) && is_array($q['answers'])) {
                    foreach ($q['answers'] as $a) {
                        $stmtA = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, 0)");
                        $stmtA->execute([
                            'question_id' => $question_id,
                            'answer_text' => $a
                        ]);
                    }
                }
            }
        }

        header("Location: ../dashboard-entreprise.php");
        exit;
    } else {
        $error = "Le titre du quiz est obligatoire.";
    }
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizzeo — Créer un Quiz (Entreprise)</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
/* --- Styles identiques à ton dashboard --- */
:root{ --bg: #f6f7fb; --card: rgba(255,255,255,0.9); --muted: #7b7f87; --text: #0f1724; --accent: #7b6df6; --accent-2: #F38788; --accent-3: #FAB540; --glass: rgba(255,255,255,0.65); --radius: 16px; --shadow-lg: 0 18px 40px rgba(16,24,40,0.08); --shadow-sm: 0 6px 18px rgba(16,24,40,0.06); --max-width: 1100px; }
*{box-sizing:border-box;margin:0;padding:0;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{background: linear-gradient(180deg, var(--bg), #eef0fb); color:var(--text); line-height:1.45; min-height:100vh;}
a{color:inherit; text-decoration:none;}
.container{width:95%; max-width: var(--max-width); margin:0 auto;}
header{width:100%;position:sticky;top:0;background: rgba(255,255,255,0.6);backdrop-filter: blur(8px);box-shadow: var(--shadow-sm);}
header .nav{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:20px;padding:10px 24px;}
header .logo{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--accent),#cc7af6);color:white;font-weight:700;font-family:"Fredoka One",sans-serif;font-size:20px;}
header .brand-info{display:flex;flex-direction: column;justify-content:center;}
header .brand-info .title{font-weight:700; font-size:18px;}
header .brand-info .subtitle{font-size:12px;color:var(--muted); margin-top:2px;}
header .logout{padding:10px 20px;background: linear-gradient(90deg,var(--accent-2),#e26666);color:white;border-radius:12px;font-weight:600;text-align:center;transition: all .25s ease;}
header .logout:hover{transform:translateY(-2px);}
main{padding:120px 0 40px;}
section{background: var(--card); padding:30px; border-radius: var(--radius); margin-bottom:30px; box-shadow: var(--shadow-lg);}
section h3{font-weight:700; font-size:24px; margin-bottom:20px; color: var(--text);}
input, textarea, select{width:100%; padding:10px 14px; margin-bottom:15px; border-radius:12px; border:1px solid rgba(15,23,36,0.1);}
.btn{display:inline-block;padding:10px 18px;border-radius:12px;font-weight:600;cursor:pointer;border:none;text-align:center;background: linear-gradient(90deg,var(--accent-3),#f5aa2e);color:white;}
.btn:hover{transform:translateY(-2px);}
.question-box{background:white;padding:18px;margin-bottom:12px;border-radius:12px;border-left:5px solid var(--accent-3);}
.remove-btn{background:#f05757;color:white;padding:6px 10px;border-radius:8px;font-size:14px;cursor:pointer;border:none;position: absolute; top:12px; right:12px;}
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
<a href="../logout.php" class="logout">Déconnexion</a>
</div>
</header>

<main class="container">
<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
<!-- Quiz Info -->
<section>
<h3>🏢 Infos du Quiz</h3>
<label>Titre du Quiz :</label>
<input type="text" name="titre" placeholder="Ex : Baromètre interne - Janvier" required>
<label>Description :</label>
<textarea name="description" rows="3" placeholder="Description du quiz..."></textarea>
<label>Statut :</label>
<select name="status">
  <option value="en cours d'écriture">Brouillon</option>
  <option value="lancé">Publié</option>
  <option value="terminé">Terminé</option>
</select>
</section>

<!-- Ajouter Questions -->
<section>
<h3>➕ Ajouter une Question</h3>
<label>Type de question :</label>
<select id="typeQuestion">
  <option value="qcm">QCM (choix multiples)</option>
  <option value="libre">Réponse libre</option>
</select>
<label>Intitulé de la question :</label>
<input type="text" id="questionText" placeholder="Ex : Comment évaluez-vous l'organisation ?">

<div id="qcmZone">
<label>Option 1 :</label><input type="text" id="r1">
<label>Option 2 :</label><input type="text" id="r2">
<label>Option 3 :</label><input type="text" id="r3">
</div>

<button type="button" class="btn" onclick="addQuestion()">Ajouter la question</button>
</section>

<!-- Liste Questions -->
<section>
<h3>📌 Questions du Quiz</h3>
<div id="questionsContainer"></div>
</section>

<button type="submit" class="btn">Enregistrer le Quiz</button>
<a href="../dashboard-entreprise.php" class="btn">⬅ Annuler</a>
</form>
</main>

<footer class="container">&copy; 2025 Quizzeo — Tous droits réservés</footer>

<script>
let questionCount = 0;
const typeSelector = document.getElementById("typeQuestion");
const qcmZone = document.getElementById("qcmZone");

typeSelector.addEventListener("change", () => {
    qcmZone.style.display = typeSelector.value === "qcm" ? "block" : "none";
});

function addQuestion(){
    const text = document.getElementById("questionText").value.trim();
    if(!text) return alert("Veuillez écrire la question.");

    const container = document.getElementById("questionsContainer");
    const div = document.createElement("div");
    div.className = "question-box";
    div.style.position = "relative";

    let html = `<strong>${text}</strong>
                <input type="hidden" name="questions[${questionCount}][text]" value="${text}">`;

    if(typeSelector.value === "qcm"){
        for(let i=1;i<=3;i++){
            const r = document.getElementById("r"+i).value.trim();
            if(r) html += `<input type="text" name="questions[${questionCount}][answers][${i-1}]" value="${r}">`;
        }
    }

    html += `<button type="button" class="remove-btn" onclick="removeQuestion(this)">Supprimer</button>`;
    div.innerHTML = html;
    container.appendChild(div);
    questionCount++;
    clearInputs();
}

function removeQuestion(btn){
    btn.parentElement.remove();
}

function clearInputs(){
    document.getElementById("questionText").value = "";
    for(let i=1;i<=3;i++) document.getElementById("r"+i).value = "";
}
</script>

</body>
</html>
