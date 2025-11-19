<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions.php';
include './includes/header.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : "";
$slug = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : "";

$allowed_slugs = [
    'cakes' => ['cakes','Cakes'],
    'pies' => ['pies','Pies'],
    'classic-filipino-breads' => ['classic-filipino-breads','Classic Filipino Breads'],
    'slices' => ['slices','Slices']
];

$category_values = $allowed_slugs[$slug] ?? null;

$sql = "
    SELECT i.item_id, i.item_name, i.description, i.img_path, i.sell_price, 
           COALESCE(s.quantity, 0) AS quantity
    FROM item i
    LEFT JOIN stock s ON i.item_id = s.item_id
    WHERE 1=1
";
$params = [];
$types = "";

if ($category_values) {
    $sql .= " AND (i.category = ? OR i.category = ?) ";
    $params[] = $category_values[0];
    $params[] = $category_values[1];
    $types .= "ss";
}

if ($keyword !== "") {
    $sql .= " AND (i.item_name LIKE ? OR i.description LIKE ?) ";
    $searchParam = "%$keyword%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " ORDER BY i.item_name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($keyword !== "" && $category_values) {
    $heading = "Search Results in " . htmlspecialchars(ucwords(str_replace('-', ' ', $slug))) . " for: " . htmlspecialchars($keyword);
} elseif ($keyword !== "") {
    $heading = "Search Results for: " . htmlspecialchars($keyword);
} elseif ($category_values) {
    $heading = "Category: " . htmlspecialchars(ucwords(str_replace('-', ' ', $slug)));
} else {
    $heading = "°❀⋆.ೃ࿔*Our Pastries ༘♡ ⋆｡˚ ❀";
}
?>

<section class="shop fade-in">

    <!-- Search Bar -->
    <form class="search-form" method="GET" action="search.php">
        <input type="text" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit">🔍 Search pastries...</button>
    </form>

    <h2 class="page-title"><?php echo $heading; ?></h2>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="product-grid">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php $item_id = $row['item_id']; ?>
                <div class="product-card">

                    <div class="product-image">
                        <?php
                        $img_q = mysqli_query($conn, "SELECT image_path FROM item_images WHERE item_id = $item_id");
                        $images = [];
                        if (mysqli_num_rows($img_q) > 0) {
                            while ($img_row = mysqli_fetch_assoc($img_q)) {
                                $images[] = $img_row['image_path'];
                            }
                        }
                        array_unshift($images, $row['img_path']); 
                        ?>
                        <div class="slider-shop-<?php echo $item_id; ?>">
                            <?php foreach ($images as $img_path): ?>
                                <div>
                                    <img src="uploads/products/<?php echo e($img_path); ?>" 
                                         alt="<?php echo e($row['item_name']); ?>" 
                                         style="width:100%; border-radius:8px;">
                                </div>
                            <?php endforeach; ?>
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

                    <h3><?php echo e($row['item_name']); ?></h3>
                    <p class="price">₱<?php echo number_format($row['sell_price'], 2); ?></p>

                    <?php
                    $rating_q = mysqli_query($conn, "
                        SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
                        FROM reviews
                        WHERE item_id = $item_id
                    ");
                    $rating_data = mysqli_fetch_assoc($rating_q);
                    if ($rating_data['total_reviews'] > 0) {
                        $avg_rating = number_format($rating_data['avg_rating'],1);
                        $full_stars = floor($rating_data['avg_rating']);
                        $half_star = ($rating_data['avg_rating'] - $full_stars) >= 0.5 ? 1 : 0;
                        $empty_stars = 5 - $full_stars - $half_star;
                        $stars = str_repeat("⭐",$full_stars) . ($half_star ? "✨" : "") . str_repeat("☆",$empty_stars);
                        echo "<a class='rating-summary' href='item_rev.php?id={$item_id}'>
                                <span class='stars'>{$stars}</span>
                                <span>({$rating_data['total_reviews']} reviews)</span>
                              </a>";
                    } else {
                        echo "<a class='rating-summary' href='item_rev.php?id={$item_id}'>⭐ No reviews yet</a>";
                    }
                    ?>
  
                    <p class="desc"><?php echo e($row['description']); ?></p>

                    <?php if ($row['quantity'] > 0): ?>
                        <div class="card-actions">
                            <form method="POST" action="cart/store.php" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                <input type="number" name="item_qty" value="1" min="1" max="<?php echo $row['quantity']; ?>" class="qty-input">
                                <input type="hidden" name="type" value="add">
                                <button type="submit" class="button">🛒</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="out-of-stock-text">Sorry, this item is sold out!</p>
                    <?php endif; ?>

                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="no-items">No products found.</p>
    <?php endif; ?>
</section>

<!-- Show SweetAlert if msg exists -->
<?php if (isset($_GET['msg'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: "success",
            title: "<?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>",
            showConfirmButton: false,
            timer: 1500
        });
    </script>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'login_required'): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: "warning",
    title: "Please Login First",
    text: "You must be logged in to add items to your cart.",
    showCancelButton: true,
    confirmButtonText: "Login",
    cancelButtonText: "Register"
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = "login.php";
    } else {
        window.location.href = "register.php";
    }
});
</script>
<?php endif; ?>


<?php include './includes/footer.php'; ?>
