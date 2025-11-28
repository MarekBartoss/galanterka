<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GALANTERKA - Sortiment</title>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/sortiment.css">
<link rel="stylesheet" href="css/globalAnimations.css">
</head>
<body>

<?php 
// Připojíme header
include 'includes/header.php'; 
// Připojíme databázi (header.php už ji možná připojuje, ale pro jistotu)
require_once __DIR__ . '/includes/db_connect.php';
?>

<main>

<section class="sortiment-hero">
  <h1>Náš sortiment</h1>
  <p>Objevte širokou nabídku galanterie, kterou jsme pro vás pečlivě vybrali. U nás najdete všechno, co potřebujete pro vaše šicí i kreativní projekty.</p>
</section>

<section class="sortiment-list">

  <?php
  // 1. Dotaz na všechny kategorie
  $sql = "SELECT * FROM categories ORDER BY name ASC";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0):
    // 2. Smyčka pro zobrazení každé kategorie
    while ($category = $result->fetch_assoc()):
      
      // Zjistíme cestu k obrázku
      $imgSrc = $category['picture_id']
        ? 'image.php?id=' . $category['picture_id']
        : 'assets/img/placeholderIMGWHITE.jpg'; // Výchozí obrázek
  ?>
  
      <div class="item">
        <a href="category.php?id=<?php echo htmlspecialchars($category['id']); ?>">
          <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
          <h2><?php echo htmlspecialchars($category['name']); ?></h2>
          <p><?php echo htmlspecialchars($category['description']); ?></p>
        </a>
      </div>

  <?php
    endwhile;
  else:
    echo "<p>V nabídce zatím nejsou žádné kategorie.</p>";
  endif;
  ?>

</section>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>