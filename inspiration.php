<?php
// Načteme potřebné soubory
require_once __DIR__ . '/includes/db_connect.php';

// Nastavení pro <head> (pro header.php)
$pageTitle = "GALANTERKA - Inspirace";
$pageDesc = "Nechte se inspirovat ukázkami z našich materiálů a projektů zákazníků.";
// Načteme původní CSS pro mřížku A NOVÉ CSS pro lightbox
$pageStylesheets = ['css/inspiration.css', 'css/lightbox.css'];

// 1. Načteme obrázky z DB pro stránku "inspirace"
$stmt = $conn->prepare("SELECT id, title FROM pictures WHERE page = 'inspirace' ORDER BY uploaded_at DESC");
$stmt->execute();
$result_images = $stmt->get_result();

$images_data = [];
while ($row = $result_images->fetch_assoc()) {
    $images_data[] = $row;
}
$stmt->close();

// Vložení hlavičky (po načtení dat)
include 'includes/header.php';
?>

<main>

<section class="inspirace-hero">
  <h1>Inspirace</h1>
  <p>Nechte se inspirovat ukázkami z našich materiálů a projektů zákazníků.</p>
</section>

<section class="gallery">
  <?php if (empty($images_data)): ?>
    <p>V galerii zatím nejsou žádné obrázky.</p>
  <?php else: ?>
    <?php 
    // 2. Vytvoříme pole pro JavaScript a vypíšeme obrázky
    $js_image_array = [];
    foreach ($images_data as $index => $img):
      $img_src = 'image.php?id=' . $img['id'];
      $js_image_array[] = $img_src;
    ?>
      <div class="gallery-item">
        <img 
          src="<?php echo $img_src; ?>" 
          alt="<?php echo htmlspecialchars($img['title'] ?? 'Inspirace'); ?>"
          onclick="openLightbox(<?php echo $index; ?>)"
        >
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

</main>

<div id="lightbox-overlay" class="lightbox-overlay" style="display: none;">
  <span class="lightbox-close" onclick="closeLightbox()">&times;</span>

  <a class="lightbox-prev" onclick="changeImage(-1)">&#10094;</a>

  <div class="lightbox-content">
    <img id="lightbox-img" src="" alt="Zvětšený obrázek inspirace">
  </div>

  <a class="lightbox-next" onclick="changeImage(1)">&#10095;</a>
</div>


<script>
  // 3. Převedeme PHP pole obrázků do JavaScriptového pole
  const galleryImages = <?php echo json_encode($js_image_array); ?>;
  
  // Proměnné pro sledování stavu
  let currentImageIndex = 0;
  const lightbox = document.getElementById('lightbox-overlay');
  const lightboxImg = document.getElementById('lightbox-img');

  /**
   * Otevře lightbox a nastaví obrázek podle indexu
   */
  function openLightbox(index) {
    if (galleryImages.length === 0) return; // Nic nedělat, pokud nejsou obrázky
    
    currentImageIndex = index;
    updateLightboxImage();
    
    lightbox.style.display = 'flex'; // Zobrazíme overlay
    // Malé zpoždění pro CSS animaci
    setTimeout(() => { lightbox.style.opacity = 1; }, 10); 
    
    // Přidáme posluchače pro klávesnici
    document.addEventListener('keydown', handleKeyPress);
  }

  /**
   * Zavře lightbox
   */
  function closeLightbox() {
    lightbox.style.opacity = 0; // Spustíme animaci zmizení
    // Počkáme na dokončení animace (300ms) a pak skryjeme
    setTimeout(() => { lightbox.style.display = 'none'; }, 300);
    
    // Odebereme posluchače pro klávesnici
    document.removeEventListener('keydown', handleKeyPress);
  }

  /**
   * Změní obrázek (dopředu o 1, nebo dozadu o -1)
   */
  function changeImage(direction) {
    currentImageIndex += direction;
    
    // Zacyklení galerie
    if (currentImageIndex < 0) {
      currentImageIndex = galleryImages.length - 1; // Jdi na konec
    } else if (currentImageIndex >= galleryImages.length) {
      currentImageIndex = 0; // Jdi na začátek
    }
    
    updateLightboxImage();
  }

  /**
   * Aktualizuje 'src' atribut obrázku v lightboxu
   */
  function updateLightboxImage() {
    if (galleryImages[currentImageIndex]) {
      lightboxImg.src = galleryImages[currentImageIndex];
    }
  }

  /**
   * Zpracovává stisky kláves (Escape, Šipky)
   */
  function handleKeyPress(e) {
    if (e.key === 'Escape') {
      closeLightbox();
    } else if (e.key === 'ArrowLeft') {
      changeImage(-1);
    } else if (e.key === 'ArrowRight') {
      changeImage(1);
    }
  }
  
  // Volitelné: Zavření lightboxu kliknutím mimo obrázek
  lightbox.addEventListener('click', function(e) {
    // Zavřeme pouze pokud se kliklo na tmavé pozadí (overlay), 
    // ne na obrázek nebo šipky.
    if (e.target === lightbox) {
      closeLightbox();
    }
  });

</script>

<?php 
// Vložení patičky
include 'includes/footer.php'; 
?>