
  <?php
require_once __DIR__ . '/db_connect.php';
$res = $conn->query("SELECT id FROM pictures WHERE filename LIKE 'logo.%' ORDER BY uploaded_at DESC LIMIT 1");
$row = $res->fetch_assoc();
$logoId = $row ? $row['id'] : null;

?>

<!DOCTYPE html>
<html lang="cs">
<?php

// Default metadata fallback
$pageTitle = $pageTitle ?? 'Sdílna - Home';
$pageDesc = $pageDesc ?? 'Kurzy šití, mentoring a inspirace pro začátečníky i pokročilé.';
$pageImage = $pageImage ?? 'https://sdilna.eu/gallery'; // adjust path as needed
$pageURL = $pageURL ?? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Get logo from DB
$res = $conn->query("SELECT id FROM pictures WHERE page='header' AND position='logo' ORDER BY uploaded_at DESC LIMIT 1
");
$row = $res->fetch_assoc();
$logoId = $row ? $row['id'] : null;
?>

<!DOCTYPE html>
<html lang="cs">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>" />
  <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($pageURL) ?>" />
  <meta property="og:type" content="website" />

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>" />
  <meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($pageImage) ?>" />

  <!-- Favicon & Fonts -->
  <link rel="icon" href="assets/img/favicon.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="css/header.css">

  <!-- Google tag (gtag.js) -->
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
        <li class="has-dropdown">
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

</body>

</html>
