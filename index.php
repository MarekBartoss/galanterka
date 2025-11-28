<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GALANTERKA - Úvod</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/index.css">
<link rel="stylesheet" href="css/sortiment.css">
<link rel="stylesheet" href="css/globalAnimations.css">
<style>
  .new-arrivals { max-width: 960px; margin: 40px auto; padding: 0 20px; }
  .new-arrivals h2 { font-size: 1.8em; font-weight: 600; margin-bottom: 30px; color: #fff; text-align: center; }
  .sale-badge { position: absolute; top: 10px; right: 10px; background-color: #d9534f; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8em; font-weight: bold; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.3); }
  .price-crossed { text-decoration: line-through; color: #999; font-size: 0.85em; margin-right: 5px; }
  .price-sale { color: #d9534f; font-weight: bold; }
</style>
</head>
<body>

<?php 
include 'includes/header.php'; 
require_once __DIR__ . '/includes/db_connect.php';
?>

<main>
<section class="hero">
  <h1>Galanterie pro vaše tvoření</h1>
  <p>Jste tu správně — naše galanterie nabízí vše, co potřebujete pro vaše šicí a kreativní projekty.</p>
  <a href="aboutUs.php" class="btn">O nás</a>
</section>

<section class="new-arrivals">
  <h2>Novinky v galanterii</h2>
  
  <div class="sortiment-list">
    <?php
    // Updated query to check category discount
    $sql = "SELECT i.*, c.sale_discount as category_discount
            FROM items i
            JOIN subcategories s ON i.subcategory_id = s.id
            JOIN categories c ON s.category_id = c.id
            ORDER BY i.id DESC LIMIT 3";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0):
      while ($item = $result->fetch_assoc()):
        
        $imgSrc = $item['picture_id'] ? 'image?id=' . $item['picture_id'] : 'assets/img/placeholderIMGWHITE.jpg';
        
        $avail_text = "";
        switch ($item['availability'] ?? 'in_store_only') {
            case 'in_stock': $avail_text = "Skladem"; break;
            case 'out_of_stock': $avail_text = "Vyprodáno"; break;
            default: $avail_text = "Dostupné na prodejně"; break;
        }

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
    
      <div class="item" style="position: relative;">
        <a href="productDetail.php?id=<?php echo $item['id']; ?>">
          <?php if($is_on_sale): ?>
              <div class="sale-badge">AKCE</div>
          <?php endif; ?>

          <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
          <h3><?php echo htmlspecialchars($item['name']); ?></h3>
          
          <p style="color: #eee; font-weight: bold; margin: 5px 0;">
             <?php 
                if ($is_on_sale) {
                    echo '<span class="price-crossed">' . number_format($item['price'], 0, ',', ' ') . ' Kč</span>';
                    echo '<span class="price-sale">' . number_format($final_price, 0, ',', ' ') . ' Kč</span>';
                } else {
                    echo $item['price'] ? number_format($item['price'], 0, ',', ' ') . ' Kč' : 'Cena na dotaz';
                }
             ?>
          </p>
          
          <small style="display:block; margin-bottom:10px; color:#999;"><?php echo $avail_text; ?></small>
        </a>
      </div>

    <?php
      endwhile;
    else:
      echo "<p style='text-align:center; color:#ccc;'>Zatím zde nejsou žádné novinky.</p>";
    endif;
    ?>
  </div>
  
  <div style="text-align:center; margin-top:30px;">
    <a href="sortiment.php" class="btn">Zobrazit celý sortiment</a>
  </div>
</section>

<section class="image"><img src="assets/img/placeholderIMGWHITE.jpg" alt="Galanterie"></section>
<section class="about">
  <h2>Proč nakupovat u nás?</h2>
  <p>Věříme, že tvoření začíná kvalitním materiálem. Naše galanterie vám poskytne široký výběr nití, látek, zipů, knoflíků a dalších potřeb.</p>
</section>

</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>