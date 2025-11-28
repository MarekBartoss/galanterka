<?php
// Připojíme databázi pouze JEDNOU
require_once __DIR__ . '/db_connect.php';

// --- Načtení nastavení lišty ---
$announcement_text = "";
$announcement_active = 0;
$setRes = $conn->query("SELECT name, value FROM settings WHERE name IN ('announcement_text', 'announcement_active')");
if ($setRes) {
    while($row = $setRes->fetch_assoc()) {
        if ($row['name'] == 'announcement_text') $announcement_text = $row['value'];
        if ($row['name'] == 'announcement_active') $announcement_active = (int)$row['value'];
    }
}

// --- Logika pro metadata stránky ---
$defaultTitle = 'Galanterka';
$defaultDesc = 'Kurzy šití, mentoring a inspirace pro začátečníky i pokročilé.';
$defaultImage = 'https://galanterka.eu/inspiration'; 
$defaultURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$pageTitle = $pageTitle ?? $defaultTitle;
$pageDesc = $pageDesc ?? $defaultDesc;
$pageImage = $pageImage ?? $defaultImage;
$pageURL = $pageURL ?? $defaultURL;

// --- Logika pro logo ---
$logoId = null;
$res = $conn->query("SELECT id FROM pictures WHERE page='header' AND position='logo' ORDER BY uploaded_at DESC LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $logoId = $row['id'];
}
?>
<!DOCTYPE html>
<html lang="cs">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDesc); ?>">

  <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>" />
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc); ?>" />
  <meta property="og:image" content="<?php echo htmlspecialchars($pageImage); ?>" />
  <meta property="og:url" content="<?php echo htmlspecialchars($pageURL); ?>" />
  <meta property="og:type" content="website" />

  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>" />
  <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDesc); ?>" />
  <meta name="twitter:image" content="<?php echo htmlspecialchars($pageImage); ?>" />

  <link rel="icon" href="assets/img/galaFAVICON.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/header.css">
  
  <?php
  if (isset($pageStylesheets) && is_array($pageStylesheets)) {
      foreach ($pageStylesheets as $style) {
          echo '<link rel="stylesheet" href="' . htmlspecialchars($style) . '">';
      }
  }
  ?>
  <link rel="stylesheet" href="css/globalAnimations.css">

  <script async src="https://www.googletagmanager.com/gtag/js?id=G-V5Y6Z0TYMQ"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-V5Y6Z0TYMQ');
  </script>
</head>

<body>

  <?php if ($announcement_active && !empty($announcement_text)): ?>
  <div class="announcement-bar">
    <?php echo htmlspecialchars($announcement_text); ?>
  </div>
  <?php endif; ?>

  <header>
    <div class="header-inner">
      <div class="logo">
        <a href="index">
          <?php if ($logoId): ?>
            <img src="image?id=<?php echo $logoId; ?>" alt="Galanterka logo">
          <?php else: ?>
            <p>Logo nenalezeno</p>
          <?php endif; ?>
        </a>
    </div>

      <button class="burger" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav class="nav-desktop">
        <a href="index">Úvod</a>
        <a href="sortiment">Sortiment</a>
        <a href="inspiration">Inspirace</a>
        <a href="aboutUs">O nás</a>
        <a href="contact">Kontakt</a>
      </nav>
    </div>

    <div class="mobile-menu">
      <button class="close">&times;</button>
      <ul>
        <li><a href="index">Úvod</a></li>
        <li><a href="sortiment">Sortiment</a></li>
        <li><a href="inspiration">Inspirace</a></li>
        <li><a href="aboutUs">O nás</a></li>
        <li><a href="contact">Kontakt</a></li>
      </ul>
    </div>
  </header>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const burger = document.querySelector('.burger');
      const mobileMenu = document.querySelector('.mobile-menu');
      const closeBtn = document.querySelector('.close');

      burger.addEventListener('click', () => {
        burger.classList.toggle('open');
        mobileMenu.classList.toggle('active');
      });

      closeBtn.addEventListener('click', () => {
        burger.classList.remove('open');
        mobileMenu.classList.remove('active');
      });
    });
  </script>