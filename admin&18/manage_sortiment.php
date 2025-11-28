<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: administratorTab.php");
    exit;
}
$message = "";

// Delete Logic
try {
    if (isset($_GET['delete_cat'])) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_cat']);
        if ($stmt->execute()) $message = "🗑️ Kategorie smazána.";
    }
    if (isset($_GET['delete_subcat'])) {
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_subcat']);
        if ($stmt->execute()) $message = "🗑️ Podkategorie smazána.";
    }
    if (isset($_GET['delete_item'])) {
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_item']);
        if ($stmt->execute()) $message = "🗑️ Položka smazána.";
    }
    if (isset($_GET['success'])) $message = "✅ Údaje uloženy.";
} catch (Exception $e) {
    $message = "❌ Chyba: " . $e->getMessage();
}

// Fetch Tree Data
// UPDATE: Fetch sale_discount for categories
$category_list = [];
$cat_result = $conn->query("SELECT id, name, sale_discount FROM categories ORDER BY name ASC");
if ($cat_result) while ($row = $cat_result->fetch_assoc()) $category_list[] = $row;

$subcategory_list = [];
$subcat_result = $conn->query("SELECT id, name, category_id FROM subcategories ORDER BY name ASC");
if ($subcat_result) while ($row = $subcat_result->fetch_assoc()) $subcategory_list[] = $row;

$categories_with_data = [];
foreach ($category_list as $cat) {
    $categories_with_data[$cat['id']] = $cat;
    $categories_with_data[$cat['id']]['subcategories'] = [];
}
foreach ($subcategory_list as $subcat) {
    if(isset($categories_with_data[$subcat['category_id']])) {
        $subcat['items'] = [];
        $categories_with_data[$subcat['category_id']]['subcategories'][$subcat['id']] = $subcat;
    }
}
$item_result = $conn->query("SELECT id, name, subcategory_id, picture_id, price, sale_price, availability FROM items ORDER BY name ASC");
if ($item_result) {
    while ($item = $item_result->fetch_assoc()) {
        foreach ($categories_with_data as $cat_id => $cat_data) {
            if (isset($cat_data['subcategories'][$item['subcategory_id']])) {
                $categories_with_data[$cat_id]['subcategories'][$item['subcategory_id']]['items'][] = $item;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Správa sortimentu</title>
<link rel="stylesheet" href="../css/admin.css">
<style>
    body { font-size: 0.95em; }
    .list-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 8px 6px; border-bottom: 1px solid #f0f0f0; }
    td:last-child { text-align: right; }
    td img { max-width: 40px; max-height: 40px; border-radius: 4px; }
    .actions a { font-size: 0.9em; margin-left: 8px; padding: 4px 8px; border-radius: 4px; background-color: #f0f0f0; color: #333; text-decoration: none; }
    .actions a:hover { background-color: #e0e0e0; }
    .actions a.delete { background-color: #fbeaea; color: #c9302c; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-header .add-buttons a { padding: 10px 15px; background: #111; color: #fff; text-decoration: none; border-radius: 5px; margin-left: 10px; }
    .tree-table { table-layout: fixed; }
    .tree-table .col-img { width: 60px; }
    .tree-table .col-name { width: auto; }
    .tree-table .col-price { width: 120px; }
    .tree-table .col-actions { width: 180px; text-align: right; }
    .level-1 td { background-color: #f4f4f4; font-weight: 600; }
    .level-2 td { background-color: #fdfdfd; padding-left: 25px; }
    .level-3 td { padding-left: 50px; font-size: 0.95em; }
</style>
</head>
<body>

<div class="page-header">
    <h1>Struktura sortimentu</h1>
    <div class="add-buttons">
        <a href="manage_edit.php?type=category">Přidat Kategorii</a>
        <a href="manage_edit.php?type=subcategory">Přidat Podkategorii</a>
        <a href="manage_edit.php?type=item">Přidat Položku</a>
    </div>
</div>
<p><a href="administratorTab.php">⬅ Zpět do hlavní administrace</a></p>

<?php if (!empty($message)): ?>
<p style="font-weight: bold; background-color: #dff0d8; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="list-container">
    <table class="tree-table">
        <colgroup><col class="col-img"><col class="col-name"><col class="col-price"><col class="col-actions"></colgroup>
        <tbody>
            <?php foreach ($categories_with_data as $cat): ?>
                <tr class="level-1">
                    <td></td>
                    <td colspan="2">
                        <?php echo htmlspecialchars($cat['name']); ?>
                        <?php if(!empty($cat['sale_discount']) && $cat['sale_discount'] > 0): ?>
                            <span style="background:#d9534f; color:white; padding:2px 6px; border-radius:4px; font-size:0.8em; margin-left:10px;">
                                -<?php echo $cat['sale_discount']; ?>%
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="manage_edit.php?type=category&id=<?php echo $cat['id']; ?>">Upravit</a>
                        <a href="?delete_cat=<?php echo $cat['id']; ?>" class="delete" onclick="return confirm('Smazat kategorii?');">Smazat</a>
                    </td>
                </tr>
                
                <?php foreach ($cat['subcategories'] as $subcat): ?>
                    <tr class="level-2">
                        <td></td>
                        <td colspan="2"><?php echo htmlspecialchars($subcat['name']); ?></td>
                        <td class="actions">
                            <a href="manage_edit.php?type=subcategory&id=<?php echo $subcat['id']; ?>">Upravit</a>
                            <a href="?delete_subcat=<?php echo $subcat['id']; ?>" class="delete" onclick="return confirm('Smazat podkategorii?');">Smazat</a>
                        </td>
                    </tr>

                    <?php foreach ($subcat['items'] as $item): ?>
                        <tr class="level-3">
                            <td>
                                <?php $imgSrc = $item['picture_id'] ? '../image.php?id=' . $item['picture_id'] : '../assets/img/placeholderIMGjpg.jpg'; ?>
                                <img src="<?php echo $imgSrc; ?>" alt="">
                            </td>
                            <td class="col-name">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <?php if($item['availability'] == 'in_stock'): ?>
                                    <span style="color:green; font-size:0.8em; border:1px solid green; padding:2px 4px; border-radius:4px;">Skladem</span>
                                <?php elseif($item['availability'] == 'out_of_stock'): ?>
                                    <span style="color:red; font-size:0.8em; border:1px solid red; padding:2px 4px; border-radius:4px;">Vyprodáno</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-price">
                                <?php 
                                    if ($item['sale_price'] > 0) {
                                        echo "<s style='color:#999;'>" . number_format($item['price'],0) . "</s> <b style='color:#d9534f;'>" . number_format($item['sale_price'],0) . "</b>";
                                    } else {
                                        echo $item['price'] ? number_format($item['price'], 0, ',', ' ') . ' Kč' : '-'; 
                                    }
                                ?>
                            </td>
                            <td class="actions">
                                <a href="manage_edit.php?type=item&id=<?php echo $item['id']; ?>">Upravit</a>
                                <a href="?delete_item=<?php echo $item['id']; ?>" class="delete" onclick="return confirm('Smazat položku?');">Smazat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>