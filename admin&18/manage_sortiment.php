<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Zabezpečení stránky
if (!isset($_SESSION['admin_id'])) {
    header("Location: administratorTab.php");
    exit;
}

$message = "";

// --- ZPRACOVÁNÍ MAZÁNÍ (DELETE REQUESTS) ---
// Vše ostatní (POST, edit) je přesunuto do manage_edit.php
try {
    if (isset($_GET['delete_cat'])) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_cat']);
        if ($stmt->execute()) $message = "🗑️ Kategorie smazána (včetně všech podkategorií a položek).";
    }
    if (isset($_GET['delete_subcat'])) {
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_subcat']);
        if ($stmt->execute()) $message = "🗑️ Podkategorie smazána (včetně všech položek v ní).";
    }
    if (isset($_GET['delete_item'])) {
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", (int)$_GET['delete_item']);
        if ($stmt->execute()) $message = "🗑️ Položka smazána.";
    }
    // Zpráva o úspěchu z editoru
    if (isset($_GET['success'])) {
        $message = "✅ Údaje byly úspěšně uloženy.";
    }
} catch (Exception $e) {
    $message = "❌ Chyba databáze: " . $e->getMessage();
}

// --- NAČTENÍ DAT PRO STROM ---
$category_list = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) $category_list[] = $row;
}

$subcategory_list = [];
$subcat_result = $conn->query("SELECT id, name, category_id FROM subcategories ORDER BY name ASC");
if ($subcat_result) {
    while ($row = $subcat_result->fetch_assoc()) $subcategory_list[] = $row;
}

// Seskupení dat
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
$item_result = $conn->query("SELECT id, name, subcategory_id, picture_id, price FROM items ORDER BY name ASC");
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
<title>Správa sortimentu - Přehled</title>
<link rel="stylesheet" href="../css/admin.css">
<style>
    body { font-size: 0.95em; }
    .list-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        align-self: flex-start;
    }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; }
    td { padding: 8px 6px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
    td:last-child { text-align: right; }
    td img { max-width: 40px; max-height: 40px; border-radius: 4px; }
    .actions a { 
        font-size: 0.9em; 
        margin-left: 8px; 
        text-decoration: none;
        padding: 4px 8px;
        border-radius: 4px;
        background-color: #f0f0f0;
        color: #333;
    }
    .actions a:hover { background-color: #e0e0e0; }
    .actions a.delete { background-color: #fbeaea; color: #c9302c; }
    .actions a.delete:hover { background-color: #f8d7da; }

    h2 { text-align: left; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-header .add-buttons a {
        display: inline-block;
        padding: 10px 15px;
        background: #111;
        color: #fff;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 500;
        margin-left: 10px;
    }
    .page-header .add-buttons a:hover { background: #333; }

    /* Styly pro strom */
    .tree-table { table-layout: fixed; }
    .tree-table .col-img { width: 60px; }
    .tree-table .col-name { width: auto; }
    .tree-table .col-price { width: 100px; }
    .tree-table .col-actions { width: 180px; text-align: right; }

    .tree-table .level-1 td { background-color: #f4f4f4; font-size: 1.1em; font-weight: 600; }
    .tree-table .level-2 td { background-color: #fdfdfd; padding-left: 25px; }
    .tree-table .level-3 td { padding-left: 50px; font-size: 0.95em; }
    .tree-table .level-3 .col-name { color: #333; }
    .tree-table .level-3 .col-price { font-weight: 500; }

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
        <colgroup>
            <col class="col-img">
            <col class="col-name">
            <col class="col-price">
            <col class="col-actions">
        </colgroup>
        <tbody>
            <?php if (empty($categories_with_data)): ?>
                <tr><td colspan="4">Zatím nebyly vytvořeny žádné kategorie.</td></tr>
            <?php else: ?>
                <?php foreach ($categories_with_data as $cat): ?>
                    <tr class="level-1">
                        <td></td>
                        <td colspan="2"><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td class="actions">
                            <a href="manage_edit.php?type=category&id=<?php echo $cat['id']; ?>">Upravit</a>
                            <a href="?delete_cat=<?php echo $cat['id']; ?>" class="delete" onclick="return confirm('Opravdu smazat kategorii \'<?php echo htmlspecialchars(addslashes($cat['name'])); ?>\'? Smažou se i VŠECHNY podkategorie a položky v ní!');">Smazat</a>
                        </td>
                    </tr>
                    
                    <?php if (empty($cat['subcategories'])): ?>
                        <tr><td colspan="4" style="padding-left: 25px;"><small>Tato kategorie nemá žádné podkategorie.</small></td></tr>
                    <?php else: ?>
                        <?php foreach ($cat['subcategories'] as $subcat): ?>
                            <tr class="level-2">
                                <td></td>
                                <td colspan="2"><?php echo htmlspecialchars($subcat['name']); ?></td>
                                <td class="actions">
                                    <a href="manage_edit.php?type=subcategory&id=<?php echo $subcat['id']; ?>">Upravit</a>
                                    <a href="?delete_subcat=<?php echo $subcat['id']; ?>" class="delete" onclick="return confirm('Opravd
                               _subcat=<?php echo $subcat['id']; ?>" class="delete" onclick="return confirm('Opravdu smazat podkategorii \'<?php echo htmlspecialchars(addslashes($subcat['name'])); ?>\'? Smažou se i VŠECHNY položky v ní!');">Smazat</a>
                                </td>
                            </tr>

                            <?php if (empty($subcat['items'])): ?>
                                <tr><td colspan="4" style="padding-left: 50px;"><small>Žádné položky v této podkategorii.</small></td></tr>
                            <?php else: ?>
                                <?php foreach ($subcat['items'] as $item): ?>
                                    <tr class="level-3">
                                        <td>
                                            <?php $imgSrc = $item['picture_id'] ? '../image.php?id=' . $item['picture_id'] : '../assets/img/placeholderIMGjpg.jpg'; ?>
                                            <img src="<?php echo $imgSrc; ?>" alt="">
                                        </td>
                                        <td class="col-name"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="col-price">
                                            <?php echo $item['price'] ? htmlspecialchars(number_format($item['price'], 0, ',', ' ')) . ' Kč' : '-'; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="manage_edit.php?type=item&id=<?php echo $item['id']; ?>">Upravit</a>
                                            <a href="?delete_item=<?php echo $item['id']; ?>" class="delete" onclick="return confirm('Opravdu smazat položku \'<?php echo htmlspecialchars(addslashes($item['name'])); ?>\'?');">Smazat</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>