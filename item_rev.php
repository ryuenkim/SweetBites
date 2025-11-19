<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions.php';
include './includes/header.php';

if (!isset($_GET['id'])) {
    echo "<p>Invalid item.</p>";
    exit;
}

$item_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? 0;


 //  FETCH ITEM DATA
$stmt = $conn->prepare("
    SELECT item_id, item_name, description, img_path, sell_price
    FROM item
    WHERE item_id = ?
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    echo "<p>Item not found.</p>";
    exit;
}

 
   //FETCH ALL IMAGES FOR SLIDER
$images = [];
$imgQuery = $conn->prepare("SELECT image_path FROM item_images WHERE item_id = ?");
$imgQuery->bind_param("i", $item_id);
$imgQuery->execute();
$res = $imgQuery->get_result();

while ($row = $res->fetch_assoc()) {
    $images[] = $row['image_path'];
}

/* fallback to main image */
if (empty($images)) {
    $images[] = $item['img_path'];
}

   //AVERAGE RATING
$avg = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM reviews
    WHERE item_id = ?
");
$avg->bind_param("i", $item_id);
$avg->execute();
$ratingData = $avg->get_result()->fetch_assoc();

  // ALL REVIEWS
$r = $conn->prepare("
    SELECT r.rating, r.comment, r.review_date, c.full_name, r.user_id
    FROM reviews r
    JOIN customer_info c ON r.user_id = c.user_id
    WHERE r.item_id = ?
    ORDER BY r.review_date DESC
");
$r->bind_param("i", $item_id);
$r->execute();
$reviews = $r->get_result();

   //CHECK IF USER PURCHASED
$purchased = false;
$userReview = null;

if ($user_id) {

    $buy = $conn->prepare("
        SELECT COUNT(*) AS bought
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.item_id = ? AND o.user_id = ? AND o.order_status = 'Delivered'
    ");
    $buy->bind_param("ii", $item_id, $user_id);
    $buy->execute();
    $purchased = $buy->get_result()->fetch_assoc()['bought'] > 0;

    /* USER’S PREVIOUS REVIEW */
    $reviewQuery = $conn->prepare("
        SELECT rating, comment
        FROM reviews
        WHERE item_id = ? AND user_id = ?
    ");
    $reviewQuery->bind_param("ii", $item_id, $user_id);
    $reviewQuery->execute();
    $userReview = $reviewQuery->get_result()->fetch_assoc();
}

   //SUBMIT REVIEW (WITH PROFANITY FILTER)

if (isset($_POST['submit_review']) && $purchased) {
    $rating = intval($_POST['rating']);
    $comment = mask_profanity(trim($_POST['comment']));

    if ($userReview) {
        $upd = $conn->prepare("
            UPDATE reviews
            SET rating = ?, comment = ?, review_date = NOW()
            WHERE item_id = ? AND user_id = ?
        ");
        $upd->bind_param("isii", $rating, $comment, $item_id, $user_id);
        $upd->execute();
    } else {
        $ins = $conn->prepare("
            INSERT INTO reviews (user_id, item_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        $ins->bind_param("iiis", $user_id, $item_id, $rating, $comment);
        $ins->execute();
    }

    header("Location: item_rev.php?id=$item_id&review_updated=1");
    exit;
}
?>

<style>
/* slider size */
.slick-slide img {
    width: 100%;
    height: auto;
    border-radius: 10px;
}
.item-image-slider {
    width: 370px;
    margin-bottom: 15px;
}
</style>

<section class="item-page fade-in">
    <div class="item-container">

        <!-- ================= IMAGE SLIDER ================= -->
        <div class="item-image-slider slider-<?php echo $item_id; ?>">
            <?php foreach ($images as $img): ?>
                <div>
                    <img src="uploads/products/<?php echo e($img); ?>" alt="">
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            $(document).ready(function() {
                $(".slider-<?php echo $item_id; ?>").slick({
                    dots: true,
                    infinite: true,
                    arrows: true,
                    speed: 300,
                    slidesToShow: 1,
                    adaptiveHeight: true
                });
            });
        </script>
        <!-- ================================================= -->

        <div class="item-details">

            <h2><?php echo e($item['item_name']); ?></h2>
            <p class="price">₱<?php echo number_format($item['sell_price'], 2); ?></p>
            <p><?php echo e($item['description']); ?></p>

            <form method="POST" action="cart/store.php">
                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                <input type="number" name="item_qty" value="1" min="1" class="qty-input">
                <input type="hidden" name="type" value="add">
                <button class="button">🛒 Add to Cart</button>
            </form>

            <hr>

            <!-- ================= REVIEWS ================= -->
            <h3>⭐ Customer Reviews</h3>
            <p>
                <strong><?php echo number_format($ratingData['avg_rating'], 1); ?></strong> / 5.0
                (<?php echo $ratingData['total_reviews']; ?> reviews)
            </p>

            <?php if (isset($_GET['review_updated'])): ?>
                <script>alert('Your review has been submitted/updated!');</script>
            <?php endif; ?>

            <?php if (!$user_id): ?>
                <p>You must <a href="login.php">login</a> to review.</p>

            <?php elseif (!$purchased): ?>
                <p style="color:gray;">You must purchase this item before reviewing.</p>

            <?php elseif ($userReview): ?>

                <div class="user-review-box" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong>Your Review:</strong>
                        <p>Rating: <?php echo str_repeat("⭐", $userReview['rating']); ?></p>
                        <p><?php echo e(mask_profanity($userReview['comment'])); ?></p>
                    </div>

                    <button type="button" onclick="document.getElementById('edit-review-form').style.display='block'; this.style.display='none';" class="button">Edit Review</button>
                </div>

                <form method="POST" id="edit-review-form" class="review-form" style="display:none; margin-top:10px;">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="5" <?php echo ($userReview['rating']==5)?'selected':''; ?>>⭐⭐⭐⭐⭐ 5</option>
                        <option value="4" <?php echo ($userReview['rating']==4)?'selected':''; ?>>⭐⭐⭐⭐ 4</option>
                        <option value="3" <?php echo ($userReview['rating']==3)?'selected':''; ?>>⭐⭐⭐ 3</option>
                        <option value="2" <?php echo ($userReview['rating']==2)?'selected':''; ?>>⭐⭐ 2</option>
                        <option value="1" <?php echo ($userReview['rating']==1)?'selected':''; ?>>⭐ 1</option>
                    </select>

                    <label>Comment</label>
                    <textarea name="comment" required><?php echo htmlspecialchars(mask_profanity($userReview['comment']), ENT_QUOTES); ?></textarea>

                    <button name="submit_review" class="button">Update Review</button>
                </form>

            <?php else: ?>

                <form method="POST" class="review-form">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="5">⭐⭐⭐⭐⭐ 5</option>
                        <option value="4">⭐⭐⭐⭐ 4</option>
                        <option value="3">⭐⭐⭐ 3</option>
                        <option value="2">⭐⭐ 2</option>
                        <option value="1">⭐ 1</option>
                    </select>

                    <label>Comment</label>
                    <textarea name="comment" required></textarea>

                    <button name="submit_review" class="button">Submit Review</button>
                </form>
            <?php endif; ?>

            <hr>

            <?php while ($rev = $reviews->fetch_assoc()): ?>
                <?php if ($user_id && $rev['user_id'] == $user_id) continue; ?>
                <div class="review-box">
                    <strong><?php echo str_repeat("⭐", $rev['rating']); ?></strong>
                    <p><?php echo e(mask_profanity($rev['comment'])); ?></p>
                    <small>
                        By: <?php echo e($rev['full_name']); ?> — <?php echo $rev['review_date']; ?>
                    </small>
                </div>
            <?php endwhile; ?>

        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>
