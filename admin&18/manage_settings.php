<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Zabezpečení
if (!isset($_SESSION['admin_id'])) {
    header("Location: administratorTab.php");
    exit;
}

$message = "";

// --- Uložení nastavení ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $text = $_POST['announcement_text'];
    $active = isset($_POST['announcement_active']) ? '1' : '0';

    // Update text
    $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('announcement_text', ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->bind_param("ss", $text, $text);
    $stmt->execute();
    
    // Update active status
    $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('announcement_active', ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->bind_param("ss", $active, $active);
    $stmt->execute();

    $message = "✅ Nastavení uloženo.";
}

// --- Načtení nastavení ---
$settings = [];
$res = $conn->query("SELECT name, value FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nastavení webu</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="form-container">
    <h1>Nastavení webu</h1>
    <p><a href="administratorTab.php">⬅ Zpět do administrace</a></p>

    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="post">
        <h2>📢 Horní oznamovací lišta</h2>
        
        <label>
            <input type="checkbox" name="announcement_active" value="1" <?php echo ($settings['announcement_active'] ?? '0') == '1' ? 'checked' : ''; ?>>
            Zobrazit lištu na webu
        </label>

        <label>Text oznámení:
            <textarea name="announcement_text" rows="3" required><?php echo htmlspecialchars($settings['announcement_text'] ?? ''); ?></textarea>
        </label>

        <button type="submit">Uložit nastavení</button>
    </form>
</div>

</body>
</html>