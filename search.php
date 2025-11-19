<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions.php';
include './includes/header.php';

// ==========================
// Get search keyword
// ==========================
$keyword = isset($_GET['q']) ? trim($_GET['q']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

// ==========================
// Allowed category slugs
// ==========================
$allowed_slugs = [
    'cakes' => ['Cakes', 'cakes'],
    'pies' => ['Pies', 'pies'],
    'classic-filipino-breads' => ['Classic Filipino Breads', 'classic filipino breads'],
    'slices' => ['Slices', 'slices']
];
?>

<section class="shop fade-in">
    <h2 class="page-title">
        🔍 Search Results for: 
        <span class="accent"><?php echo htmlspecialchars($keyword); ?></span>
    </h2>

<?php
// If empty search
if ($keyword === "") {
    echo "<p class='no-items'>Please enter a search keyword.</p>";
    include './includes/footer.php';
    exit;
}

// ==========================
// Main Search Query
// ==========================
$sql = "
    SELECT i.item_id, i.item_name, i.description, i.img_path, i.sell_price, 
           COALESCE(s.quantity, 0) AS quantity
    FROM item i
    LEFT JOIN stock s ON i.item_id = s.item_id
    WHERE (i.item_name LIKE ? 
       OR i.description LIKE ?)
";

$params = ["%$keyword%", "%$keyword%"];
$types = "ss";

// Optional category filter
if ($category !== "" && array_key_exists($category, $allowed_slugs)) {
    $sql .= " AND i.category = ? ";
    $params[] = $allowed_slugs[$category][0]; // exact DB match
    $types .= "s";
}

$sql .= " ORDER BY i.item_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php if ($result && mysqli_num_rows($result) > 0): ?>
    <div class="product-grid">

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="product-card">
            <!-- ================= IMAGE SLIDER ================= -->
            <div class="product-image">
            <?php
                $item_id = $row['item_id'];
                $img_q = mysqli_query($conn, "SELECT image_path FROM item_images WHERE item_id = $item_id");
            ?>

                <div class="slider-shop-<?php echo $item_id; ?>">
                <?php if (mysqli_num_rows($img_q) > 0): ?>
                    <?php while ($img_row = mysqli_fetch_assoc($img_q)): ?>
                        <div>
                            <img src="uploads/products/<?php echo e($img_row['image_path']); ?>"
                                 alt="Image"
                                 style="width:100%; border-radius:8px;">
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div>
                        <img src="uploads/products/<?php echo e($row['img_path']); ?>"
                             alt="Image"
                             style="width:100%; border-radius:8px;">
                    </div>
                <?php endif; ?>
                </div>

                <script>
                    $(document).ready(function(){
                        $('.slider-shop-<?php echo $item_id; ?>').slick({
                            dots: true,
                            arrows: true,
                            infinite: false,
                            slidesToShow: 1,
                            slidesToScroll: 1
                        });
                    });
                </script>

                <?php if ($row['quantity'] == 0): ?>
                    <span class="badge out">Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- ================= ITEM NAME & PRICE ================= -->
            <h3><?php echo e($row['item_name']); ?></h3>
            <p class="price">₱<?php echo number_format($row['sell_price'], 2); ?></p>

            <!-- ================= RATING ================= -->
            <?php
                $rating_q = mysqli_query($conn, "
                    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
                    FROM reviews
                    WHERE item_id = $item_id
                ");
                $rating_data = mysqli_fetch_assoc($rating_q);

                if ($rating_data['total_reviews'] > 0) {
                    $avg = $rating_data['avg_rating'];
                    $total_reviews = $rating_data['total_reviews'];
                    $full = floor($avg);
                    $half = ($avg - $full >= 0.5) ? 1 : 0;
                    $empty = 5 - $full - $half;

                    $stars = str_repeat("⭐", $full) . ($half ? "✨" : "") . str_repeat("☆", $empty);

                    echo "<a class='rating-summary' href='item_rev.php?id={$item_id}'>
                            <span class='stars'>{$stars}</span>
                            <span>({$total_reviews} reviews)</span>
                          </a>";
                } else {
                    echo "<a class='rating-summary' href='item_rev.php?id={$item_id}'>⭐ No reviews yet</a>";
                }
            ?>

            <p class="desc"><?php echo e($row['description']); ?></p>

            <!-- ================= ADD TO CART ================= -->
            <?php if ($row['quantity'] > 0): ?>
                <div class="card-actions">
                    <form method="POST" action="cart/store.php" style="display:inline;">
                        <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                        <input type="number" name="item_qty" value="1" min="1" max="<?php echo $row['quantity']; ?>" class="qty-input">
                        <input type="hidden" name="type" value="add">
                        <button type="submit" class="button">🛒 Add to Cart</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="out-of-stock-text">Sorry, this item is sold out!</p>
            <?php endif; ?>
        </div>

    <?php endwhile; ?>
    </div>

<?php else: ?>
    <p class="no-items">No results found for “<?php echo e($keyword); ?>”.</p>
<?php endif; ?>

</section>

<?php include './includes/footer.php'; ?>
