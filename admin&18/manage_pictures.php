<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: administratorTab.php");
    exit;
}

$message = "";

// DELETE
if (!empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM pictures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $message = "🗑️ Obrázek byl smazán.";
    } else {
        $message = "❌ Obrázek nebyl nalezen nebo se nepodařilo smazat.";
    }
    $stmt->close();
}

// UPLOAD & CONVERT to .webp
if (!empty($_FILES['picture']['name'])) {
    $title = trim($_POST['title']);
    $page = trim($_POST['page']);
    $position = trim($_POST['position']);
    $tmpPath = $_FILES['picture']['tmp_name'];
    $originalName = pathinfo($_FILES['picture']['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
    $webpFilename = $originalName . '.webp';
    $webpMime = 'image/webp';
    $webpPath = sys_get_temp_dir() . '/' . uniqid('img_') . '.webp';

    $success = false;

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($tmpPath);
                break;
            case 'png':
                $image = imagecreatefrompng($tmpPath);
                break;
            case 'gif':
                $image = imagecreatefromgif($tmpPath);
                break;
            default:
                $image = false;
        }

        if ($image !== false) {
            $success = imagewebp($image, $webpPath, 80);
            imagedestroy($image);
        }
    } else {
        $message = "❌ Nepodporovaný formát obrázku. Použij JPG, PNG nebo GIF.";
    }

    if ($success && file_exists($webpPath)) {
        $webpData = file_get_contents($webpPath);
        $desc = "Použito na stránce: $page, pozice: $position";

        $stmt = $conn->prepare("
            INSERT INTO pictures (filename, title, description, mime_type, data, page, position)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss", $webpFilename, $title, $desc, $webpMime, $webpData, $page, $position);
        $stmt->send_long_data(5, $webpData); // correct index for binary
        if ($stmt->execute()) {
            $message = "✅ Obrázek byl převeden do WebP a uložen do databáze.";
        } else {
            $message = "❌ Nepodařilo se uložit obrázek: " . $stmt->error;
        }
        $stmt->close();
        unlink($webpPath);
    } elseif (!$message) {
        $message = "❌ Nepodařilo se uložit obrázek.";
    }
}

// Load pictures
$picturesRaw = $conn->query("SELECT * FROM pictures ORDER BY page, position, uploaded_at DESC");
$pictures = [];
if ($picturesRaw) {
    while ($row = $picturesRaw->fetch_assoc()) {
        $pictures[$row['page']][$row['position']][] = $row;
    }
} else {
    $message = "❌ Chyba načítání obrázků: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Správa obrázků</title>
<link rel="stylesheet" href="../css/admin.css">
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
form { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; max-width: 400px; }
label { display: block; margin-top: 10px; }
input, select { width: 100%; margin-top: 3px; margin-bottom: 10px; padding: 5px; }
button { padding: 8px 12px; background: #111; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
button:hover { background: #333; }
img { max-width: 150px; display: block; margin-bottom: 5px; }
.picture { display: inline-block; text-align: center; margin: 10px; border: 1px solid #ddd; padding: 5px; width: 180px; }
.section { margin-bottom: 30px; }
.section h3 { background: #f0f0f0; padding: 5px; }
small.type { color: #666; font-size: 0.8em; display: block; margin-top: 4px; }
</style>
</head>
<body>

<h1>Správa obrázků</h1>
<p><a href="administratorTab.php">⬅ Zpět do administrace</a></p>

<?php if (!empty($message)): ?>
<p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Stránka:
  <select name="page" id="page" required>
    <option value="">-- vyber stránku --</option>
    <option value="header">Header</option>
    <option value="index">Úvod</option>
    <option value="inspirace">Inspirace</option> </select>
</label>

  <label>Pozice:
    <select name="position" id="position" required>
      <option value="">-- vyber pozici --</option>
    </select>
  </label>

  <label>Název:
    <input type="text" name="title" required>
  </label>

  <label>Soubor:
    <input type="file" name="picture" accept=".jpg,.jpeg,.png,.gif" required>
  </label>

  <button type="submit">Nahrát obrázek</button>
</form>

<h2>Existující obrázky</h2>

<?php foreach ($pictures as $page => $sections): ?>
<div class="section">
  <h2>📄 Stránka: <?php echo htmlspecialchars($page); ?></h2>
  <?php foreach ($sections as $position => $imgs): ?>
  <h3>📌 Pozice: <?php echo htmlspecialchars($position); ?></h3>
  <?php foreach ($imgs as $row): ?>
    <div class="picture">
      <img src="../image.php?id=<?php echo $row['id']; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
      <p><strong><?php echo htmlspecialchars($row['title']); ?></strong></p>
      <p><small><?php echo htmlspecialchars($row['description']); ?></small></p>
      <small class="type">🗂️ <?php echo htmlspecialchars($row['mime_type']); ?></small>
      <p><a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Opravdu smazat?');">🗑️ Smazat</a></p>
    </div>
  <?php endforeach; ?>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<script>
const pageSelect = document.getElementById('page');
const positionSelect = document.getElementById('position');
const positions = {
  'header': ['logo'],
  'index': ['hero'],
  'inspirace': ['gallery']
};
pageSelect.addEventListener('change', () => {
  const page = pageSelect.value;
  positionSelect.innerHTML = '<option value="">-- vyber pozici --</option>';
  if (positions[page]) {
    positions[page].forEach(pos => {
      const opt = document.createElement('option');
      opt.value = pos;
      opt.textContent = pos;
      positionSelect.appendChild(opt);
    });
  }
});
</script>

</body>
</html>
