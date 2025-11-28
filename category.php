<?php
// Zapnutí chyb pro diagnostiku
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

// Získat ID KATEGORIE z URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sortiment");
    exit;
}
$category_id = (int)$_GET['id'];

// Načíst detaily o této KATEGORII (včetně slevy)
$stmt_cat = $conn->prepare("SELECT name, description, sale_discount FROM categories WHERE id = ?");
$stmt_cat->bind_param("i", $category_id);
$stmt_cat->execute();
$result_cat = $stmt_cat->get_result();

if ($result_cat->num_rows === 0) {
    header("Location: sortiment");
    exit;
}
$category = $result_cat->fetch_assoc();
$stmt_cat->close();

$discount = isset($category['sale_discount']) ? (int)$category['sale_discount'] : 0;

// Nastavení proměnných pro <head> (pro header.php)
$pageTitle = "GALANTERKA - " . htmlspecialchars($category['name']);
$pageDesc = htmlspecialchars($category['description']);
// Použijeme CSS pro sortiment (mřížka + badge)
$pageStylesheets = ['css/sortiment.css'];

// Vložení hlavičky
include 'includes/header.php';
?>

<main>
    <section class="sortiment-hero">
        <h1><?php echo htmlspecialchars($category['name']); ?></h1>
        
        <?php if($discount > 0): ?>
            <p style="color:#d9534f; font-weight:bold; font-size:1.1em; margin-bottom:5px;">
                SLEVA <?php echo $discount; ?> % na celou kategorii
            </p>
        <?php endif; ?>

        <p><?php echo htmlspecialchars($category['description']); ?></p>
    </section>

    <section class="sortiment-list">
        <?php
        // Dotaz na všechny PODKATEGORIE v této KATEGORII
        $stmt_sub = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name ASC");
        $stmt_sub->bind_param("i", $category_id);
        $stmt_sub->execute();
        $result_sub = $stmt_sub->get_result();

        if ($result_sub && $result_sub->num_rows > 0):
            while ($subcategory = $result_sub->fetch_assoc()):
                $imgSrc = $subcategory['picture_id']
                    ? 'image?id=' . $subcategory['picture_id']
                    : 'assets/img/placeholderIMGWHITE.jpg';
        ?>
                <div class="item">
                    <a href="subcategory?id=<?php echo htmlspecialchars($subcategory['id']); ?>">
                        
                        <?php if($discount > 0): ?>
                            <div class="sale-badge">-<?php echo $discount; ?> %</div>
                        <?php endif; ?>

                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($subcategory['name']); ?>">
                        <h2><?php echo htmlspecialchars($subcategory['name']); ?></h2>
                        <p><?php echo htmlspecialchars($subcategory['description']); ?></p>
                    </a>
                </div>
        <?php
            endwhile;
        else:
            echo "<p>V této kategorii zatím nejsou žádné podkategorie.</p>";
        endif;
        
        $stmt_sub->close();
        ?>
    </section>
</main>

<?php
// Vložení patičky
include 'includes/footer.php';
?>