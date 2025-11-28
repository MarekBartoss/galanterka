<?php
// Připojíme databázi pouze JEDNOU
require_once __DIR__ . '/db_connect.php';

// --- Logika pro metadata stránky ---

// Výchozí hodnoty
$defaultTitle = 'Galanterka';
$defaultDesc = 'Kurzy šití, mentoring a inspirace pro začátečníky i pokročilé.';
$defaultImage = 'https://galanterka.eu/inspiration'; // Upravte podle potřeby
$defaultURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Pokud stránka (jako category.php) nastavila $pageTitle, použijeme ji.
// Pokud ne, použijeme výchozí.
$pageTitle = $pageTitle ?? $defaultTitle;
$pageDesc = $pageDesc ?? $defaultDesc;
$pageImage = $pageImage ?? $defaultImage;
$pageURL = $pageURL ?? $defaultURL;

// --- Logika pro logo ---
// (Sloučil jsem vaše dva dotazy na logo do jednoho)
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
  // Tento blok nám umožní načíst specifické CSS pro každou stránku.
  // Např. do category.php přidáme $pageStylesheets = ['css/sortimentCategory.css'];
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

  <header>
    <div class="header-inner">
      <div class="logo">
        <a href="index.php">
          <?php if ($logoId): ?>
            <img src="image.php?id=<?php echo $logoId; ?>" alt="Galanterka logo">
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
        <a href="index.php">Úvod</a>
        <a href="sortiment.php">Sortiment</a>
        <a href="inspiration.php">Inspirace</a>
        <a href="aboutUs.php">O nás</a>
        <a href="contact.php">Kontakt</a>
      </nav>
    </div>
    </div>

    <div class="mobile-menu">
      <button class="close">&times;</button>
      <ul>
        <li><a href="index.php">Úvod</a></li>
        <li><a href="sortiment.php">Sortiment</a></li>
        <li><a href="inspiration.php">Inspirace</a></li>
        <li><a href="aboutUs.php">O nás</a></li>
        <li><a href="contact.php">Kontakt</a></li>
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

      const mobileDropdownToggles = document.querySelectorAll('.mobile-menu .dropdown-toggle');

      mobileDropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', e => {
          e.preventDefault();
          toggle.parentElement.classList.toggle('open');
        });
      });
    });
  </script>