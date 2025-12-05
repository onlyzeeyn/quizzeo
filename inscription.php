<?php
// inscription.php - single file (HTML + PHP) avec CAPTCHA côté client + vérification serveur
session_start();

// --- CONFIG BDD (adapte si nécessaire) ---
$host    = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "quizzeo";
// ------------------------------
$mysqli = new mysqli($host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
}

// --- Fonctions utilitaires ---
function generate_captcha_code($len = 6) {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $c = "";
    for ($i = 0; $i < $len; $i++) {
        $c .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $c;
}

// Si l'utilisateur a demandé explicitement une régénération via query param (bouton "Régénérer"), on regen
if (isset($_GET['regen_captcha']) && $_GET['regen_captcha'] == '1') {
    $_SESSION['captcha_code'] = generate_captcha_code(6);
    // redirect pour enlever le query param et éviter double submit
    $loc = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $loc");
    exit;
}

// Si pas de code en session, en créer un
if (empty($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generate_captcha_code(6);
}

// Variables pour ré-affichage
$val_nom = "";
$val_prenom = "";
$val_email = "";
$val_role = "";
$message = "";
$message_type = ""; // "error" | "success"

// Traitement du POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération (trim pour la plupart)
    $val_nom = trim($_POST['nom'] ?? "");
    $val_prenom = trim($_POST['prenom'] ?? "");
    $val_email = trim($_POST['email'] ?? "");
    $val_role = trim($_POST['role'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";
    $captcha_input = trim($_POST['captcha'] ?? "");

    // Validations
    if ($val_nom === "" || $val_prenom === "" || $val_email === "" || $val_role === "" || $password === "" || $confirm_password === "" || $captcha_input === "") {
        $message = "Veuillez remplir tous les champs (y compris le captcha).";
        $message_type = "error";
    } elseif (!filter_var($val_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse e-mail invalide.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
        $message_type = "error";
    } elseif (!isset($_SESSION['captcha_code']) || $captcha_input !== $_SESSION['captcha_code']) {
        $message = "Captcha incorrect. Veuillez réessayer.";
        $message_type = "error";
        // regénérer le captcha pour la tentative suivante
        $_SESSION['captcha_code'] = generate_captcha_code(6);
    } else {
        // Normaliser rôle
        $role_map = [
            "ecole" => "ecole",
            "entreprise" => "entreprise",
            "utilisateur" => "user",
            "user" => "user"
        ];
        $role_db = $role_map[strtolower($val_role)] ?? null;
        if ($role_db === null) {
            $message = "Rôle invalide sélectionné.";
            $message_type = "error";
        } else {
            // Vérifier unicité email (prepared)
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $val_email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $message = "Cette adresse e-mail est déjà utilisée.";
                $message_type = "error";
                $stmt->close();
                // regénérer captcha pour sécurité
                $_SESSION['captcha_code'] = generate_captcha_code(6);
            } else {
                $stmt->close();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $mysqli->prepare("INSERT INTO users (nom, prenom, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                if (!$insert) {
                    $message = "Erreur interne (prepare): " . $mysqli->error;
                    $message_type = "error";
                } else {
                    $insert->bind_param("sssss", $val_nom, $val_prenom, $val_email, $password_hash, $role_db);
                    if ($insert->execute()) {
                        $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                        $message_type = "success";
                        // clear sensitive fields
                        $val_nom = $val_prenom = $val_email = $val_role = "";
                        // Regénérer captcha pour la prochaine visite
                        $_SESSION['captcha_code'] = generate_captcha_code(6);
                    } else {
                        $message = "Erreur lors de l'inscription : " . $insert->error;
                        $message_type = "error";
                        // Regénérer captcha
                        $_SESSION['captcha_code'] = generate_captcha_code(6);
                    }
                    $insert->close();
                }
            }
        }
    }
}

$mysqli->close();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quizzeo — Inscription</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg: #f6f7fb;
      --card: rgba(255,255,255,0.95);
      --muted: #7b7f87;
      --accent: #7b6df6;
      --radius: 16px;
      --shadow-lg: 0 18px 40px rgba(16,24,40,0.08);
      --shadow-sm: 0 6px 18px rgba(16,24,40,0.06);
    }
    body{margin:0;background:linear-gradient(180deg,var(--bg),#eef0fb);font-family:"Inter",sans-serif;display:flex;justify-content:center;align-items:center;height:100vh}
    *{box-sizing:border-box}
    .card{width:100%;max-width:480px;background:var(--card);border-radius:var(--radius);padding:28px;box-shadow:var(--shadow-lg)}
    .logo{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;background:linear-gradient(135deg,var(--accent),#cc7af6);color:#fff;font-weight:700;font-family:"Fredoka One",sans-serif}
    h1{text-align:center;font-size:28px;margin-bottom:6px}
    .sub{text-align:center;color:var(--muted);margin-bottom:18px}
    label{display:block;font-weight:600;margin-bottom:6px;font-size:14px}
    .input{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.08);margin-bottom:14px;font-size:15px}
    .btn{width:100%;padding:12px 16px;border-radius:12px;border:none;cursor:pointer;font-weight:600;box-shadow:var(--shadow-sm)}
    .btn-primary{background:linear-gradient(90deg,var(--accent),#a86bff);color:#fff}
    .btn-primary:hover{transform:translateY(-2px)}
    .link{display:block;text-align:center;margin-top:12px;font-size:14px;color:var(--accent);font-weight:600;text-decoration:none}
    .message{padding:10px;border-radius:10px;margin-bottom:14px;text-align:center;font-weight:600}
    .message.error{background:#fff0f0;color:#b00020;border:1px solid rgba(176,0,32,0.08)}
    .message.success{background:#f0fff6;color:#138000;border:1px solid rgba(19,128,0,0.06)}
    #captcha-code{display:block;text-align:center;font-weight:bold;letter-spacing:3px;font-size:24px;user-select:none;margin:6px 0 12px}
    .captcha-row{display:flex;gap:8px;align-items:center}
    .regen-btn{padding:10px;border-radius:10px;border:1px dashed rgba(0,0,0,0.08);background:transparent;cursor:pointer}
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">QZ</div>
    <h1>Inscription</h1>
    <div class="sub">Créez votre compte Quizzeo</div>

    <?php if ($message): ?>
      <div class="message <?= $message_type === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES | ENT_HTML5) ?>
        <?php if ($message_type === 'success'): ?>
          <div style="margin-top:8px"><a href="login.php" class="link">Aller à la page de connexion →</a></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <label>Nom</label>
      <input type="text" name="nom" class="input" required value="<?= htmlspecialchars($val_nom, ENT_QUOTES | ENT_HTML5) ?>" />

      <label>Prénom</label>
      <input type="text" name="prenom" class="input" required value="<?= htmlspecialchars($val_prenom, ENT_QUOTES | ENT_HTML5) ?>" />

      <label>Email</label>
      <input type="email" name="email" class="input" required value="<?= htmlspecialchars($val_email, ENT_QUOTES | ENT_HTML5) ?>" />

      <label>Rôle</label>
      <select name="role" class="input" required>
        <option value="" <?= $val_role === "" ? "selected" : "" ?> disabled>Choisissez votre rôle</option>
        <option value="ecole" <?= $val_role === "ecole" ? "selected" : "" ?>>École</option>
        <option value="entreprise" <?= $val_role === "entreprise" ? "selected" : "" ?>>Entreprise</option>
        <option value="utilisateur" <?= ($val_role === "utilisateur" || $val_role === "user") ? "selected" : "" ?>>Utilisateur</option>
      </select>

      <label>Mot de passe</label>
      <input type="password" name="password" class="input" required />

      <label>Confirmer le mot de passe</label>
      <input type="password" name="confirm_password" class="input" required />

      <label>Captcha</label>
      <div id="captcha-code"><?= htmlspecialchars($_SESSION['captcha_code'] ?? '', ENT_QUOTES | ENT_HTML5) ?></div>

      <div class="captcha-row">
        <input type="text" name="captcha" id="captcha-input" class="input" placeholder="Entrez le code ci-dessus" required style="flex:1"/>
        <a class="regen-btn" href="?regen_captcha=1" title="Régénérer le captcha">↻</a>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:12px">S'inscrire</button>
    </form>

    <a href="login.php" class="link">Déjà un compte ? Se connecter →</a>
  </div>

  <script>
    // petit script pour déplacer le focus sur le champ captcha si nécessaire
    (function(){
      const captchaInput = document.getElementById('captcha-input');
      if (captchaInput) {
        // si l'URL contient #focusCaptcha on met le focus (utile après un reload)
        if (window.location.hash === '#focusCaptcha') captchaInput.focus();
      }
    })();
  </script>
</body>
</html>
