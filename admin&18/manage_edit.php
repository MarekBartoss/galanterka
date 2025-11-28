<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: administratorTab.php");
    exit;
}

$type = $_GET['type'] ?? 'category';
$id = $_GET['id'] ?? null;
$is_editing = ($id !== null);
$page_title = "";
$form_data = null;

// --- SAVE DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $id = $_POST['id'] ?? null;

    try {
        switch ($type) {
            case 'category':
                $picture_id = !empty($_POST['picture_id']) ? (int)$_POST['picture_id'] : NULL;
                $sale_discount = !empty($_POST['sale_discount']) ? (int)$_POST['sale_discount'] : 0; // New Field

                if ($id) {
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, picture_id = ?, sale_discount = ? WHERE id = ?");
                    $stmt->bind_param("ssiii", $_POST['name'], $_POST['description'], $picture_id, $sale_discount, $id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, picture_id, sale_discount) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $_POST['name'], $_POST['description'], $picture_id, $sale_discount);
                }
                break;

            case 'subcategory':
                $picture_id = !empty($_POST['picture_id']) ? (int)$_POST['picture_id'] : NULL;
                if ($id) {
                    $stmt = $conn->prepare("UPDATE subcategories SET category_id = ?, name = ?, description = ?, picture_id = ? WHERE id = ?");
                    $stmt->bind_param("issii", $_POST['category_id'], $_POST['name'], $_POST['description'], $picture_id, $id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO subcategories (category_id, name, description, picture_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("issi", $_POST['category_id'], $_POST['name'], $_POST['description'], $picture_id);
                }
                break;

            case 'item':
                $picture_id = !empty($_POST['picture_id']) ? (int)$_POST['picture_id'] : NULL;
                $price = !empty($_POST['price']) ? (float)$_POST['price'] : NULL;
                $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : NULL;
                $availability = $_POST['availability'] ?? 'in_store_only'; 

                if ($id) {
                    $stmt = $conn->prepare("UPDATE items SET subcategory_id = ?, name = ?, description = ?, price = ?, sale_price = ?, picture_id = ?, availability = ? WHERE id = ?");
                    $stmt->bind_param("issddisi", $_POST['subcategory_id'], $_POST['name'], $_POST['description'], $price, $sale_price, $picture_id, $availability, $id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO items (subcategory_id, name, description, price, sale_price, picture_id, availability) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issddis", $_POST['subcategory_id'], $_POST['name'], $_POST['description'], $price, $sale_price, $picture_id, $availability);
                }
                break;
        }
        
        $stmt->execute();
        $stmt->close();
        header("Location: manage_sortiment.php?success=1");
        exit;

    } catch (Exception $e) {
        $message = "❌ Chyba databáze: " . $e->getMessage();
    }
}

// --- LOAD DATA ---
if ($is_editing) {
    switch ($type) {
        case 'category':
            $page_title = "Upravit Kategorii";
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            break;
        case 'subcategory':
            $page_title = "Upravit Podkategorii";
            $stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
            break;
        case 'item':
            $page_title = "Upravit Položku";
            $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
            break;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $form_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    switch ($type) {
        case 'category': $page_title = "Přidat Novou Kategorii"; break;
        case 'subcategory': $page_title = "Přidat Novou Podkategorii"; break;
        case 'item': $page_title = "Přidat Novou Položku"; break;
    }
}

// Dropdowns
$picture_list = [];
$pic_result = $conn->query("SELECT id, title, filename FROM pictures ORDER BY title ASC");
if ($pic_result) while ($row = $pic_result->fetch_assoc()) $picture_list[] = $row;

$category_list = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) while ($row = $cat_result->fetch_assoc()) $category_list[] = $row;

$subcategory_list = [];
$subcat_result = $conn->query("SELECT s.id, s.name, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.name, s.name ASC");
if ($subcat_result) while ($row = $subcat_result->fetch_assoc()) $subcategory_list[] = $row;

?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>
<link rel="stylesheet" href="../css/admin.css">
<style>
    .form-container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .form-container form { margin: 0; padding: 0; box-shadow: none; max-width: 100%; }
</style>
</head>
<body>

<div class="form-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <p><a href="manage_sortiment.php">⬅ Zpět na přehled sortimentu</a></p>

    <?php if (!empty($message)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post" action="manage_edit.php">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <?php if ($is_editing): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <?php endif; ?>

        <?php if ($type == 'category'): ?>
            <label>Název kategorie:
                <input type="text" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
            </label>
            <label style="color:#d9534f;">Sleva na celou kategorii (%):
                <input type="number" name="sale_discount" min="0" max="100" placeholder="0" value="<?php echo htmlspecialchars($form_data['sale_discount'] ?? 0); ?>">
                <small>Toto zlevní všechny položky v kategorii, pokud nemají nastavenou vlastní akční cenu.</small>
            </label>
            <label>Popis:
                <textarea name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            </label>
        <?php endif; ?>

        <?php if ($type == 'subcategory'): ?>
            <label>Nadřazená kategorie:
                <select name="category_id" required>
                    <option value="">-- Vyberte kategorii --</option>
                    <?php foreach ($category_list as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Název podkategorie:
                <input type="text" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
            </label>
            <label>Popis:
                <textarea name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            </label>
        <?php endif; ?>

        <?php if ($type == 'item'): ?>
            <label>Nadřazená podkategorie:
                <select name="subcategory_id" required>
                    <option value="">-- Vyberte podkategorii --</option>
                    <?php
                    $current_cat_name = '';
                    foreach ($subcategory_list as $subcat):
                        if ($subcat['category_name'] != $current_cat_name) {
                            if ($current_cat_name != '') echo '</optgroup>';
                            $current_cat_name = $subcat['category_name'];
                            echo '<optgroup label="' . htmlspecialchars($current_cat_name) . '">';
                        }
                    ?>
                        <option value="<?php echo $subcat['id']; ?>" <?php echo (isset($form_data['subcategory_id']) && $form_data['subcategory_id'] == $subcat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subcat['name']); ?>
                        </option>
                    <?php endforeach; echo '</optgroup>'; ?>
                </select>
            </label>
            <label>Název položky:
                <input type="text" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
            </label>
            <div style="display:flex; gap:15px;">
                <label style="flex:1;">Cena (Kč):
                    <input type="number" step="0.01" name="price" placeholder="150.00" value="<?php echo htmlspecialchars($form_data['price'] ?? ''); ?>">
                </label>
                <label style="flex:1; color:#d9534f;">Vlastní akční cena (Kč):
                    <input type="number" step="0.01" name="sale_price" placeholder="120.00" value="<?php echo htmlspecialchars($form_data['sale_price'] ?? ''); ?>">
                    <small>Přepíše slevu kategorie.</small>
                </label>
            </div>
            
            <label>Dostupnost:
                <select name="availability">
                    <option value="in_stock" <?php echo (isset($form_data['availability']) && $form_data['availability'] === 'in_stock') ? 'selected' : ''; ?>>✅ Skladem (e-shop)</option>
                    <option value="in_store_only" <?php echo (!isset($form_data['availability']) || $form_data['availability'] === 'in_store_only') ? 'selected' : ''; ?>>🏪 Dostupné pouze na prodejně</option>
                    <option value="out_of_stock" <?php echo (isset($form_data['availability']) && $form_data['availability'] === 'out_of_stock') ? 'selected' : ''; ?>>❌ Vyprodáno</option>
                </select>
            </label>

            <label>Popis:
                <textarea name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            </label>
        <?php endif; ?>

        <label>Obrázek:
            <select name="picture_id">
                <option value="">-- Bez obrázku --</option>
                <?php foreach ($picture_list as $pic): ?>
                    <option value="<?php echo $pic['id']; ?>" <?php echo (isset($form_data['picture_id']) && $form_data['picture_id'] == $pic['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pic['title'] . ' (' . $pic['filename'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit"><?php echo $is_editing ? 'Aktualizovat' : 'Přidat'; ?></button>
    </form>
</div>

</body>
</html>