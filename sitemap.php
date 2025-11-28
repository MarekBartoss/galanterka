<?php
require_once __DIR__ . '/includes/db_connect.php';

header("Content-Type: application/xml; charset=UTF-8");

// Change this to your real domain
$baseURL = 'https://galanterka.eu';

// 1. Static Pages
$pages = [
    '' => 1.0,           // Homepage (root)
    'sortiment' => 0.9,
    'inspiration' => 0.8,
    'aboutUs' => 0.7,
    'contact' => 0.6
];

// 2. Fetch Categories (Dynamic)
$cat_res = $conn->query("SELECT id FROM categories");
$categories = [];
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row['id'];
    }
}

// 3. Fetch Subcategories (Dynamic)
$sub_res = $conn->query("SELECT id FROM subcategories");
$subcategories = [];
if ($sub_res) {
    while ($row = $sub_res->fetch_assoc()) {
        $subcategories[] = $row['id'];
    }
}

// 4. Fetch Products (Dynamic)
$item_res = $conn->query("SELECT id FROM items");
$items = [];
if ($item_res) {
    while ($row = $item_res->fetch_assoc()) {
        $items[] = $row['id'];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <?php foreach ($pages as $slug => $priority): ?>
    <url>
        <loc><?= htmlspecialchars($baseURL . ($slug ? '/' . $slug : '')) ?></loc>
        <priority><?= $priority ?></priority>
        <changefreq>monthly</changefreq>
    </url>
    <?php endforeach; ?>

    <?php foreach ($categories as $catId): ?>
    <url>
        <loc><?= htmlspecialchars("$baseURL/category?id=$catId") ?></loc>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>
    <?php endforeach; ?>

    <?php foreach ($subcategories as $subId): ?>
    <url>
        <loc><?= htmlspecialchars("$baseURL/subcategory?id=$subId") ?></loc>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>
    <?php endforeach; ?>

    <?php foreach ($items as $itemId): ?>
    <url>
        <loc><?= htmlspecialchars("$baseURL/productDetail?id=$itemId") ?></loc>
        <priority>0.9</priority>
        <changefreq>weekly</changefreq>
    </url>
    <?php endforeach; ?>

</urlset>