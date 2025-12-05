<?php
session_start();

// Vérifier si l'utilisateur est connecté et a le rôle "ecole"
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: login.php");
    exit;
}

// Connexion à la base de données
$host = "localhost";
$user = "root";
$pass = "";
$db   = "quizzeo";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer les quiz créés par cette école
$sql_quiz = "SELECT * FROM quizzes WHERE user_id='$user_id' ORDER BY date_creation DESC";
$result_quiz = $conn->query($sql_quiz);

// Récupérer les résultats des étudiants
$sql_results = "
SELECT u.nom AS etudiant, u.prenom AS prenom, q.titre AS quiz, SUM(a.score) AS note
FROM user_quiz_answers a
JOIN users u ON a.user_id = u.id
JOIN quizzes q ON a.quiz_id = q.id
WHERE q.user_id='$user_id'
GROUP BY a.user_id, a.quiz_id
ORDER BY q.date_creation DESC
";
$result_results = $conn->query($sql_results);
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quizzeo — Dashboard École</title>

<!-- Fonts -->
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

  /* Buttons */
  .btn, .btn-outline{
    display:inline-block;
    padding:10px 18px;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
    border:none;
    box-shadow: var(--shadow-sm);
    transition: transform .2s, box-shadow .2s;
    text-decoration:none;
    text-align:center;
  }
  .btn{background:linear-gradient(90deg,var(--accent-3),#f5aa2e); color:white;}
  .btn:hover{transform:translateY(-2px);}
  .btn-outline{background:transparent; border:1px solid rgba(15,23,36,0.1); color:var(--text);}
  .btn-outline:hover{transform:translateY(-2px);}

  /* Table */
  table{width:100%; border-collapse: separate; border-spacing:0 8px; margin-top:10px;}
  table th, table td{padding:12px 14px; text-align:left;}
  table th{
    background: var(--accent);
    font-weight:600;
    color:white;
  }
  table tr:first-child th:first-child{border-top-left-radius:12px;}
  table tr:first-child th:last-child{border-top-right-radius:12px;}
  table tr{background:white; border-radius:12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition: background .25s;}
  table tr td:first-child{border-top-left-radius:0; border-bottom-left-radius:0;}
  table tr td:last-child{border-top-right-radius:0; border-bottom-right-radius:0;}
  table tr:hover{background: var(--accent-light);}

  /* Filtrage */
  .Filtrage{display:flex; gap:20px; margin-top:10px; margin-bottom:10px;}
  .Filtrage label, .Filtrage select{font-size:16px; font-weight:500;}
  .Filtrage select{padding:6px 10px; border-radius:10px; border:none; background: var(--glass); box-shadow: var(--shadow-sm);}

  /* Show/Hide links */
  #showMore,#showLess,#showMoreResults,#showLessResults{color: var(--accent-3); cursor:pointer; text-align:center; margin-top:12px; font-weight:600;}
  #showMore:hover,#showLess:hover,#showMoreResults:hover,#showLessResults:hover{text-decoration: underline;}

  a, .btn{ text-decoration:none; }
  footer{ text-align:center; padding:20px 0; color: var(--muted); font-size:14px; }

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
    <a href="quiz/create_quiz_ecole.php" class="btn">➕ Créer un nouveau quiz</a>
  <table id="quizTable">
  <tr><th>Titre</th><th>Statut</th><th>Réponses</th><th>Action</th></tr>
  <?php
  if ($result_quiz->num_rows > 0) {
      while ($quiz = $result_quiz->fetch_assoc()) {
          $quiz_id = $quiz['id'];
          $res_count = $conn->query("SELECT COUNT(*) AS total FROM user_quiz_answers WHERE quiz_id='$quiz_id'")->fetch_assoc()['total'];
          $statut = $quiz['statut'];
          $action_text = ($statut == "terminé") ? "Voir résultats" : "Voir détails";
          ?>
          <tr>
              <td><?php echo htmlspecialchars($quiz['titre']); ?></td>
              <td>
                  <form method="POST" action="update_quiz_status.php" style="display:inline;">
                      <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                      <select name="statut" onchange="this.form.submit()">
                          <option value="en cours d'écriture" <?php if($statut=='en cours d\'écriture') echo 'selected'; ?>>Brouillon</option>
                          <option value="lancé" <?php if($statut=='lancé') echo 'selected'; ?>>Publié</option>
                          <option value="terminé" <?php if($statut=='terminé') echo 'selected'; ?>>Terminé</option>
                      </select>
                  </form>
              </td>
              <td><?php echo $res_count; ?></td>
              <td><a class='btn-outline' href='../QUIZZEO/quiz/quiz_detail_ecole.php?quiz_id=<?php echo $quiz_id; ?>'><?php echo $action_text; ?></a></td>
          </tr>
          <?php
      }
  } else {
      echo "<tr><td colspan='4'>Aucun quiz créé pour le moment.</td></tr>";
  }
  ?>
  </table>

    <p id="showMore">Plus de détails...</p>
    <p id="showLess">Moins de détails...</p>
  </section>

  <!-- Résultats Étudiants -->
  <section>
    <h3>📊 Résultats des Étudiants</h3>
    <div class="Filtrage">
      <div><label for="filterStudent">Filtrer par étudiant:</label>
        <select id="filterStudent">
          <option value="all">Tous</option>
          <?php
          $students = [];
          $result_results->data_seek(0);
          while ($row = $result_results->fetch_assoc()) {
              $students[$row['etudiant']] = true;
          }
          foreach ($students as $name => $v) {
              echo "<option>".htmlspecialchars($name)."</option>";
          }
          ?>
        </select>
      </div>
      <div><label for="filterQuiz">Filtrer par quiz:</label>
        <select id="filterQuiz">
          <option value="all">Tous</option>
          <?php
          $result_results->data_seek(0);
          $quizzes_seen = [];
          while ($row = $result_results->fetch_assoc()) {
              $quiz_name = $row['quiz'];
              if (!in_array($quiz_name, $quizzes_seen)) {
                  echo "<option>".htmlspecialchars($quiz_name)."</option>";
                  $quizzes_seen[] = $quiz_name;
              }
          }
          ?>
        </select>
      </div>
    </div>
    <table id="resultsTable">
      <tr><th>Étudiant</th><th>Quiz</th><th>Note / Pourcentage</th></tr>
      <?php
      $result_results->data_seek(0);
      if ($result_results->num_rows > 0) {
          while ($row = $result_results->fetch_assoc()) {
              echo "<tr class='result-row'>
                  <td>".htmlspecialchars($row['etudiant']." ".$row['prenom'])."</td>
                  <td>".htmlspecialchars($row['quiz'])."</td>
                  <td>".htmlspecialchars($row['note'])."</td>
              </tr>";
          }
      } else {
          echo "<tr><td colspan='3'>Aucun résultat pour le moment.</td></tr>";
      }
      ?>
    </table>
    <p id="showMoreResults">Plus de détails...</p>
    <p id="showLessResults">Moins de détails...</p>
  </section>

  </main>

  <footer class="container">
    &copy; 2025 Quizzeo — Tous droits réservés
  </footer>

  <script>
    const quizRows = document.querySelectorAll("#quizTable tr:not(:first-child)");
    const resultRows = document.querySelectorAll(".result-row");
    let showingAll = false;
    function updateTables(){
      let qCount=0;
      quizRows.forEach(row=>{if(!showingAll && qCount>=3){row.style.display='none';} else {row.style.display='table-row'; qCount++;}});
      document.getElementById('showMore').style.display = (!showingAll && quizRows.length>3)?'block':'none';
      document.getElementById('showLess').style.display = showingAll?'block':'none';
      let rCount=0;
      resultRows.forEach(row=>{if(!showingAll && rCount>=3){row.style.display='none';} else {row.style.display='table-row'; rCount++;}});
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

<?php
$conn->close();
?>
