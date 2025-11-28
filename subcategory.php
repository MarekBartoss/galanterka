<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sortiment");
    exit;
}
$subcategory_id = (int)$_GET['id'];

// --- Get Subcategory AND Parent Category Discount ---
// JOIN categories to get sale_discount
$stmt_sub = $conn->prepare("
    SELECT s.*, c.sale_discount as category_discount 
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    WHERE s.id = ?
");
$stmt_sub->bind_param("i", $subcategory_id);
$stmt_sub->execute();
$result_sub = $stmt_sub->get_result();
if ($result_sub->num_rows === 0) {
    header("Location: sortiment");
    exit;
}
$subcategory = $result_sub->fetch_assoc();
$stmt_sub->close();

$category_discount = (int)$subcategory['category_discount']; // e.g., 20

// --- Filters ---
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$sql = "SELECT * FROM items WHERE subcategory_id = ?";
$params = [$subcategory_id];
$types = "i";
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Simple filter logic (filtering by calculated price in SQL is complex, keeping it basic)
if (!empty($min_price)) {
    $where_conditions[] = "(price >= ? OR sale_price >= ?)";
    $params[] = (float)$min_price;
    $params[] = (float)$min_price;
    $types .= "dd";
}
if (!empty($max_price)) {
    $where_conditions[] = "price <= ? OR sale_price <= ?"; // Simplified
    $params[] = (float)$max_price;
    $params[] = (float)$max_price;
    $types .= "dd";
}

if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY name ASC";

$stmt_item = $conn->prepare($sql);
if (count($params) > 0) $stmt_item->bind_param($types, ...$params);
$stmt_item->execute();
$result_item = $stmt_item->get_result();

$pageTitle = "GALANTERKA - " . htmlspecialchars($subcategory['name']);
$pageDesc = htmlspecialchars($subcategory['description']);
$pageStylesheets = ['css/sortimentCategory.css'];

include 'includes/header.php';
?>

<style>
/* CSS included in previous response, keeping it brief here */
.filter-bar { background: #111; border: 1px solid #333; border-radius: 8px; padding: 20px; margin-bottom: 30px; text-align: left; }
.filter-bar form { display: grid; grid-template-columns: 2fr 1fr 1fr 100px 100px; gap: 15px; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; }
.filter-group label { display: block; margin-bottom: 6px; font-size: 0.85em; color: #999; text-transform: uppercase; }
.filter-group input { width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: #eee; border-radius: 4px; box-sizing: border-box; }
.filter-group button { padding: 10px 15px; background: #eee; color: #111; border: none; cursor: pointer; border-radius: 4px; font-weight: 500; }
.filter-group a { color: #999; font-size: 0.9em; text-decoration: none; text-align: center; padding-bottom: 10px; }
@media (max-width: 768px) { .filter-bar form { grid-template-columns: 1fr 1fr; } .filter-group.search { grid-column: 1 / -1; } }

.sale-badge { position: absolute; top: 10px; right: 10px; background-color: #d9534f; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8em; font-weight: bold; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.3); }
.price-crossed { text-decoration: line-through; color: #999; font-size: 0.85em; margin-right: 5px; }
.price-sale { color: #d9534f; font-weight: bold; }
</style>

<main>
    <section class="kategorie-hero">
        <h1><?php echo htmlspecialchars($subcategory['name']); ?></h1>
        <?php if($category_discount > 0): ?>
            <p style="color:#d9534f; font-weight:bold;">Tato kategorie je ve slevě <?php echo $category_discount; ?>%!</p>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($subcategory['description']); ?></p>
    </section>

    <div class="filter-bar">
    <form method="GET" action="subcategory">
        <input type="hidden" name="id" value="<?php echo $subcategory_id; ?>">
        <div class="filter-group search">
            <label for="search">Vyhledat:</label>
            <input type="text" id="search" name="search" style="font-family: 'Montserrat', sans-serif;" value="<?php echo htmlspecialchars($search); ?>" placeholder="Název...">
        </div>
        <div class="filter-group">
            <label for="min_price">Cena od:</label>
            <input type="number" id="min_price" name="min_price" style="font-family: 'Montserrat', sans-serif;" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="0">
        </div>
        <div class="filter-group">
            <label for="max_price">Cena do:</label>
            <input type="number" id="max_price" name="max_price" style="font-family: 'Montserrat', sans-serif;" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="9999">
        </div>
        <div class="filter-group"><button type="submit" style="font-family: 'Montserrat', sans-serif;">Filtrovat</button></div>
        <div class="filter-group"><a href="subcategory?id=<?php echo $subcategory_id; ?>">Zrušit</a></div>
    </form>
    </div>

    <section class="produkty-list">
        <?php
        if ($result_item && $result_item->num_rows > 0):
            while ($item = $result_item->fetch_assoc()):
                $imgSrc = $item['picture_id'] ? 'image?id=' . $item['picture_id'] : 'assets/img/placeholderIMGWHITE.jpg';
                
                // --- PRICING LOGIC ---
                $final_price = $item['price'];
                $is_on_sale = false;
                $badge_text = "";

                if (!empty($item['sale_price']) && $item['sale_price'] > 0) {
                    // 1. Explicit Item Sale wins
                    $final_price = $item['sale_price'];
                    $is_on_sale = true;
                    // Calculate percent off
                    if ($item['price'] > 0) {
                        $pct = round((($item['price'] - $item['sale_price']) / $item['price']) * 100);
                        $badge_text = "-" . $pct . " %";
                    } else {
                        $badge_text = "AKCE";
                    }
                } elseif ($category_discount > 0) {
                    // 2. Category Percentage Sale (only if no item sale)
                    $final_price = $item['price'] * (1 - ($category_discount / 100));
                    $is_on_sale = true;
                    $badge_text = "-" . $category_discount . " %";
                }
        ?>
                <div class="produkt" style="position: relative;">
                    <a href="productDetail?id=<?php echo $item['id']; ?>">
                        <?php if($is_on_sale): ?>
                            <div class="sale-badge"><?php echo $badge_text; ?></div>
                        <?php endif; ?>
                        
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <h2><?php echo htmlspecialchars($item['name']); ?></h2>
                        <p class="produkt-cena">
                            <?php 
                            if ($is_on_sale) {
                                echo '<span class="price-crossed">' . number_format($item['price'], 0, ',', ' ') . ' Kč</span>';
                                echo '<span class="price-sale">' . number_format($final_price, 0, ',', ' ') . ' Kč</span>';
                            } elseif (!empty($item['price'])) {
                                echo number_format($item['price'], 0, ',', ' ') . ' Kč';
                            } else {
                                echo 'Cena na dotaz';
                            }
                            ?>
                        </p>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                    </a>
                </div>
        <?php
            endwhile;
        else:
            echo "<p>Žádné položky.</p>";
        endif;
        $stmt_item->close();
        ?>
    </section>

    <section class="page-navigation">
        <a href="sortiment" class="btn">&larr; Zpět na Sortiment</a>
    </section>

</main>
<?php include 'includes/footer.php'; ?>