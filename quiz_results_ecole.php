<?php
session_start();

// Vérifier que l'utilisateur est bien une école
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ecole') {
    header("Location: login.php");
    exit;
}

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

// Récupérer tous les résultats des quiz de l'école
$sql = "
SELECT u.nom AS nom, u.prenom AS prenom, q.titre AS quiz, SUM(a.score) AS note
FROM user_quiz_answers a
JOIN users u ON a.user_id = u.id
JOIN quizzes q ON a.quiz_id = q.id
WHERE q.user_id = :user_id
GROUP BY a.user_id, a.quiz_id
ORDER BY q.date_creation DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Résultats des Étudiants — École</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
<style>
body{font-family:"Inter",sans-serif; background:#f6f7fb; color:#0f1724; padding:40px;}
.container{max-width:900px;margin:0 auto;}
h2{font-size:28px;margin-bottom:20px;}
table{width:100%; border-collapse: collapse; margin-top:20px;}
th, td{padding:12px; text-align:left; border-bottom:1px solid #ddd;}
th{background:#7b6df6; color:white;}
tr:hover{background:#f0f0f0;}
a.btn{display:inline-block;margin-top:20px;padding:10px 18px;background:#FAB540;color:white;border-radius:12px;text-decoration:none;}
</style>
</head>
<body>

<div class="container">
<h2>Résultats des Étudiants</h2>

<?php if(count($results) > 0): ?>
<table>
    <tr>
        <th>Étudiant</th>
        <th>Quiz</th>
        <th>Note</th>
    </tr>
    <?php foreach($results as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['nom'] . ' ' . $row['prenom']); ?></td>
        <td><?php echo htmlspecialchars($row['quiz']); ?></td>
        <td><?php echo htmlspecialchars($row['note']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>Aucun résultat disponible pour le moment.</p>
<?php endif; ?>

<a class="btn" href="../dashboard/ecole.php">⬅ Retour au Dashboard</a>
</div>

</body>
</html>
