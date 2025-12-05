<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$user = "root";
$pass = "";
$db   = "quizzeo";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Initialiser le message
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    // Vérifier si l'utilisateur existe
    $sql = "SELECT * FROM users WHERE email='$email' AND status='active'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Vérifier le mot de passe
        if (password_verify($mot_de_passe, $user['password'])) {

            // Stocker la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];

            // 🎯 REDIRECTION SELON LE RÔLE
            if ($user['role'] == 'ecole') {
                header("Location: dashboard-ecole.php");
                exit;
            }
            elseif ($user['role'] == 'entreprise') {
                header("Location: dashboard-entreprise.php");
                exit;
            }
            elseif ($user['role'] == 'user' || $user['role'] == 'utilisateur') {
                header("Location: dashboard-utilisateur.php");
                exit;
            }
            else {
                $erreur = "Erreur : rôle utilisateur inconnu.";
            }

        } else {
            $erreur = "Mot de passe incorrect !";
        }
    } else {
        $erreur = "Aucun compte trouvé avec cet e-mail !";
    }
}

$conn->close();
?>

<!doctype html>
<html lang="fr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quizzeo — Connexion</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg: #f6f7fb;
      --card: rgba(255,255,255,0.9);
      --muted: #7b7f87;
      --text: #0f1724;
      --accent: #7b6df6;
      --button-hover: #9b6df6;
      --accent-2: #F38788;
      --accent-3: #FAB540;
      --radius: 16px;
      --shadow-lg: 0 18px 40px rgba(16,24,40,0.08);
      --shadow-sm: 0 6px 18px rgba(16,24,40,0.06);
    }

    body{
      margin:0;
      background: linear-gradient(180deg, var(--bg), #eef0fb);
      font-family: "Inter", sans-serif;
      display:flex;
      justify-content:center;
      align-items:center;
      height:100vh;
    }

    *{
      margin: 0;
      padding: 0;
      text-decoration: none;
      box-sizing: border-box;
    }

    .card{
      width:100%;
      max-width:420px;
      background:var(--card);
      border-radius:var(--radius);
      padding:32px;
      box-shadow:var(--shadow-lg);
      backdrop-filter: blur(8px);
      transition: 0.3s ease ;
    }

    .card:hover{
      transform: scale(1.03);
    }

    .logo-container{
      width: 100%;
    }

    .logo{
      width:46px;height:46px;border-radius:10px;
      display:flex;align-items:center;justify-content:center;
      background: linear-gradient(135deg,var(--accent),#cc7af6);
      box-shadow: var(--shadow-sm);
      color:white;font-weight:700;font-family:"Fredoka One",sans-serif;
      font-size:20px;
      margin: 0 auto;
    }

    h1{
      margin-top:10px;
      text-align:center;
      font-size:28px;
      font-weight:700;
    }

    .sub{
      text-align:center;
      color:var(--muted);
      margin-bottom:24px;
      font-size:14px;
    }

    .input{
      width:100%;
      padding:12px 14px;
      border-radius:12px;
      border:1px solid rgba(0,0,0,0.08);
      margin-bottom:14px;
      font-size:15px;
    }

    .btn{
      width:100%;
      padding:12px 16px;
      border-radius:12px;
      border:none;
      cursor:pointer;
      font-weight:600;
      box-shadow:var(--shadow-sm);
    }

    .btn-primary{
      background: linear-gradient(90deg,var(--accent),#a86bff);
      color: #fff;
      transition:  0.3s ease; 
    }

    .btn-primary:hover{
      background: linear-gradient(90deg,#9b6df6,#b380ff);
      transform: translateY(-2px);
    }

    .link{
      display:block;
      text-align:center;
      margin-top:12px;
      font-size:14px;
      color:var(--accent);
      font-weight:600;
      text-decoration:none;
    }

    .message{
      text-align:center;
      margin-bottom:15px;
      font-weight:600;
      color:red;
    }

  </style>

</head>

<body>

  <div class="card">

    <div class="logo-container">
      <div class="logo" aria-hidden="true">QZ</div>
    </div>
    
    <h1>Connexion</h1>
    <div class="sub">Accédez à votre espace Quizzeo</div>

    <?php if($erreur) echo "<div class='message'>$erreur</div>"; ?>

    <form method="post" action="">
      <label>Email</label>
      <input type="email" name="email" class="input" required />

      <label>Mot de passe</label>
      <input type="password" name="mot_de_passe" class="input" required />

      <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>

    <a href="inscription.php" class="link">Créer un compte →</a>

  </div>

</body>
</html>
