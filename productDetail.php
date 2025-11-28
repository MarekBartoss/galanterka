<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

// Získat ID POLOŽKY z URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sortiment.php");
    exit;
}
$item_id = (int)$_GET['id'];

// Načíst detaily o této POLOŽCE a její nadřazené podkategorii
$sql = "SELECT i.*, s.id as subcategory_id, s.name as subcategory_name 
        FROM items i
        JOIN subcategories s ON i.subcategory_id = s.id
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: sortiment.php");
    exit;
}
$item = $result->fetch_assoc();
$stmt->close();

// --- Nastavení pro <head> ---
$pageTitle = "GALANTERKA - " . htmlspecialchars($item['name']);
$pageDesc = htmlspecialchars($item['description']);
// Načteme nové CSS pro detail produktu
$pageStylesheets = ['css/productDetail.css', 'css/globalAnimations.css'];

// Vložení hlavičky
include 'includes/header.php';

// Zjistíme cestu k obrázku
$imgSrc = $item['picture_id']
    ? 'image.php?id=' . $item['picture_id']
    : 'assets/img/placeholderIMGWHITE.jpg';

?>

<main>
    <div class="produkt-nav">
        <p>
            <a href="sortiment.php">Sortiment</a> / 
            <a href="subcategory.php?id=<?php echo $item['subcategory_id']; ?>">
                <?php echo htmlspecialchars($item['subcategory_name']); ?>
            </a>
        </p>
    </div>

    <div class="produkt-detail">
        <div class="produkt-obrazek">
            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
        </div>
        <div class="produkt-info">
            <h1><?php echo htmlspecialchars($item['name']); ?></h1>
            
            <p class="produkt-availability-tag">Dostupné pouze na prodejně</p>
            
            <div class="cena">
                <?php 
                if (!empty($item['price'])) {
                    echo htmlspecialchars(number_format($item['price'], 0, ',', ' ')) . ' Kč';
                } else {
                    echo 'Cena na dotaz';
                }
                ?>
            </div>
            <div class="popis">
                <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            </div>
            <?php if (empty($item['price'])): ?>
                <a href="contact.php" class="btn" style="margin-top: 20px;">Kontaktujte nás pro cenu</a>
            <?php endif; ?>
        </div>
    </div>

    </div>
    </div> <section class="page-navigation">
        <a href="subcategory.php?id=<?php echo $item['subcategory_id']; ?>" class="btn">
            &larr; Zpět na <?php echo htmlspecialchars($item['subcategory_name']); ?>
        </a>
    </section>

</main>

<?php
include 'includes/footer.php';
?>