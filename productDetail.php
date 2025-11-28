<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sortiment.php");
    exit;
}
$item_id = (int)$_GET['id'];

// Get Item + Subcategory + Category Discount
$sql = "SELECT i.*, s.id as subcategory_id, s.name as subcategory_name, c.sale_discount as category_discount
        FROM items i
        JOIN subcategories s ON i.subcategory_id = s.id
        JOIN categories c ON s.category_id = c.id
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

$pageTitle = "GALANTERKA - " . htmlspecialchars($item['name']);
$pageDesc = htmlspecialchars($item['description']);
$pageStylesheets = ['css/productDetail.css', 'css/globalAnimations.css'];

include 'includes/header.php';

$imgSrc = $item['picture_id'] ? 'image?id=' . $item['picture_id'] : 'assets/img/placeholderIMGWHITE.jpg';

// --- PRICING LOGIC ---
$final_price = $item['price'];
$is_on_sale = false;
$cat_discount = (int)$item['category_discount'];

if (!empty($item['sale_price']) && $item['sale_price'] > 0) {
    $final_price = $item['sale_price'];
    $is_on_sale = true;
} elseif ($cat_discount > 0) {
    $final_price = $item['price'] * (1 - ($cat_discount / 100));
    $is_on_sale = true;
}
?>

<style>
.price-old { text-decoration: line-through; color: #888; font-size: 0.7em; margin-right: 10px; }
.price-new { color: #d9534f; }
</style>

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
            
            <?php
            $avail_text = "";
            $avail_class = "";
            switch ($item['availability'] ?? 'in_store_only') {
                case 'in_stock': $avail_text = "Skladem (e-shop)"; $avail_class = "status-stock"; break;
                case 'out_of_stock': $avail_text = "Vyprodáno"; $avail_class = "status-out"; break;
                default: $avail_text = "Dostupné pouze na prodejně"; $avail_class = "status-store"; break;
            }
            ?>
            <p class="produkt-availability-tag <?php echo $avail_class; ?>">
                <?php echo htmlspecialchars($avail_text); ?>
            </p>
            
            <div class="cena">
                <?php 
                if ($is_on_sale) {
                    echo '<span class="price-old">' . number_format($item['price'], 0, ',', ' ') . ' Kč</span>';
                    echo '<span class="price-new">' . number_format($final_price, 0, ',', ' ') . ' Kč</span>';
                } elseif (!empty($item['price'])) {
                    echo number_format($item['price'], 0, ',', ' ') . ' Kč';
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

    <section class="page-navigation">
        <a href="subcategory.php?id=<?php echo $item['subcategory_id']; ?>" class="btn">
            &larr; Zpět na <?php echo htmlspecialchars($item['subcategory_name']); ?>
        </a>
    </section>

</main>
<?php include 'includes/footer.php'; ?>