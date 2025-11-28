<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

// --- Načtení ID a dat podkategorie ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sortiment.php");
    exit;
}
$subcategory_id = (int)$_GET['id'];

$stmt_sub = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
$stmt_sub->bind_param("i", $subcategory_id);
$stmt_sub->execute();
$result_sub = $stmt_sub->get_result();
if ($result_sub->num_rows === 0) {
    header("Location: sortiment.php");
    exit;
}
$subcategory = $result_sub->fetch_assoc();
$stmt_sub->close();

// --- Zpracování filtrů ---
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Základní SQL dotaz
$sql = "SELECT * FROM items WHERE subcategory_id = ?";
$params = [$subcategory_id];
$types = "i";

// Přidání podmínek pro filtry
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($min_price)) {
    $where_conditions[] = "price >= ?";
    $params[] = (float)$min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $where_conditions[] = "price <= ?";
    $params[] = (float)$max_price;
    $types .= "d";
}

if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY name ASC";

// Příprava a spuštění finálního dotazu na položky
$stmt_item = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt_item->bind_param($types, ...$params);
}
$stmt_item->execute();
$result_item = $stmt_item->get_result();

// --- Nastavení pro <head> ---
$pageTitle = "GALANTERKA - " . htmlspecialchars($subcategory['name']);
$pageDesc = htmlspecialchars($subcategory['description']);
$pageStylesheets = ['css/sortimentCategory.css']; // Používáme stávající CSS

// Vložení hlavičky
include 'includes/header.php';
?>

<style>
/* Reset a základní layout formuláře */
.filter-bar {
    background: #111; /* Odsazeno od pozadí */
    border: 1px solid #333; /* Jemný okraj */
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
}
.filter-bar form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 100px 100px; /* Rozložení sloupců */
    gap: 15px;
    align-items: flex-end; /* Zarovná vše ke spodní hraně */
}
.filter-group {
    display: flex;
    flex-direction: column;
}

/* Minimalistický vzhled popisků a polí */
.filter-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.85em; /* Menší popisek */
    color: #999; /* Světle šedá */
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.filter-group input[type="text"],
.filter-group input[type="number"] {
    width: 100%;
    padding: 10px;
    background: #222; /* Tmavé pole */
    border: 1px solid #444; /* Jemný okraj pole */
    color: #eee;
    border-radius: 4px;
    box-sizing: border-box;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95em;
}
/* Skryjeme šipky u číselných polí (pro čistší vzhled) */
.filter-group input[type=number]::-webkit-inner-spin-button, 
.filter-group input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
.filter-group input[type=number] {
  -moz-appearance: textfield;
}

/* Tlačítka */
.filter-group button {
    padding: 10px 15px;
    background: #eee;
    color: #111;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95em;
    transition: background 0.2s ease;
}
.filter-group button:hover {
    background: #ccc;
}
.filter-group a {
    color: #999;
    font-size: 0.9em;
    text-decoration: none;
    text-align: center;
    padding-bottom: 10px; /* Zarovnání s tlačítkem */
}
.filter-group a:hover {
    color: #fff;
    text-decoration: underline;
}

/* Responsive zobrazení pro mobily */
@media (max-width: 768px) {
    .filter-bar form {
        grid-template-columns: 1fr 1fr; /* 2 sloupce */
    }
    .filter-group.search {
        grid-column: 1 / -1; /* Hledání přes celou šířku */
    }
}
</style>

<main>
    <section class="kategorie-hero">
        <h1><?php echo htmlspecialchars($subcategory['name']); ?></h1>
        <p><?php echo htmlspecialchars($subcategory['description']); ?></p>
    </section>

    <div class="filter-bar">
    <form method="GET" action="subcategory.php">
        <input type="hidden" name="id" value="<?php echo $subcategory_id; ?>">
        
        <div class="filter-group search">
            <label for="search">Vyhledat v názvu:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Název položky...">
        </div>
        <div class="filter-group">
            <label for="min_price">Cena od (Kč):</label>
            <input type="number" id="min_price" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="0">
        </div>
        <div class="filter-group">
            <label for="max_price">Cena do (Kč):</label>
            <input type="number" id="max_price" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="9999">
        </div>
        <div class="filter-group">
            <button type="submit">Filtrovat</button>
        </div>
         <div class="filter-group">
            <a href="subcategory.php?id=<?php echo $subcategory_id; ?>">Zrušit</a>
        </div>
    </form>
</div>

    <section class="produkty-list">
        <?php
        if ($result_item && $result_item->num_rows > 0):
            while ($item = $result_item->fetch_assoc()):
                $imgSrc = $item['picture_id']
                    ? 'image.php?id=' . $item['picture_id']
                    : 'assets/img/placeholderIMGWHITE.jpg';
        ?>
                <div class="produkt">
                    <a href="productDetail.php?id=<?php echo $item['id']; ?>">
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <h2><?php echo htmlspecialchars($item['name']); ?></h2>
                        <p class="produkt-cena">
                            <?php 
                            if (!empty($item['price'])) {
                                echo htmlspecialchars(number_format($item['price'], 0, ',', ' ')) . ' Kč';
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
            echo "<p>Vašemu výběru neodpovídají žádné položky.</p>";
        endif;
        
        $stmt_item->close();
        ?>
    </section>

    </section>

    <section class="page-navigation">
        <a href="sortiment.php" class="btn">
            &larr; Zpět na Sortiment
        </a>
    </section>

</main>

<?php
include 'includes/footer.php';
?>