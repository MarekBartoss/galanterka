<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';


$message = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $hash);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            $_SESSION['admin_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: administratorTab.php");
            exit;
        } else {
            $message = "Špatné heslo.";
        }
    } else {
        $message = "Uživatel nenalezen.";
    }

    $stmt->close();
}

//zakomentovat
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "Uživatel už existuje.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hash, $email);
        if ($stmt->execute()) {
            $message = "Registrace úspěšná. Můžete se přihlásit.";
        } else {
            $message = "Chyba při registraci.";
        }
    }

    $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: administratorTab.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrátor</title>
<style>
form { margin-bottom:20px; }
label { display:block; margin:5px 0; }
input[type="text"], input[type="password"], input[type="email"] { width:200px; }
</style>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<h1>GALANTERKA ADMIN</h1>

<?php if (!empty($message)): ?>
<p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if (!isset($_SESSION['admin_id'])): ?>

<h2>Přihlášení</h2>
<form method="post">
    <label>Uživatelské jméno:
        <input type="text" name="username" required>
    </label>
    <label>Heslo:
        <input type="password" name="password" required>
    </label>
    <button type="submit" name="login">Přihlásit</button>
    <a href="../index.php" style="color: red;">Ukončit</a>
</form>

<h2>Registrace</h2>
<form method="post">
    <label>Uživatelské jméno:
        <input type="text" name="username" required>
    </label>
    <label>Email:
        <input type="email" name="email" required>
    </label>
    <label>Heslo:
        <input type="password" name="password" required>
    </label>
    <button type="submit" name="register">Registrovat</button>
</form>

<?php else: ?>
<div class="dashboard">
  <p class="welcome">
    Jste přihlášen jako <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.
  </p>

  <div class="actions">
    <a href="manage_pictures.php">Správa obrázků</a>
    <a href="manage_sortiment.php">Správa sortimentu</a>
    <a href="manage_settings.php">Nastavení webu (Lišta)</a>
  </div>

  <a class="logout" href="administratorTab.php?logout=1">Odhlásit se</a>
</div>

<?php endif; ?>

</body>
</html>