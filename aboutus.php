<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions.php';
include './includes/header.php';

// Fetch 6 product images
$sql = "SELECT img_path FROM item ORDER BY item_id DESC LIMIT 6";
$result = mysqli_query($conn, $sql);
$images = [];
while ($row = mysqli_fetch_assoc($result)) {
    $images[] = "uploads/products/" . $row['img_path'];
}
?>

<style>


.about-wrapper {
    position: relative;
    padding: 6rem 2rem 8rem;
    min-height: 95vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}


.blur-grid {
    position: absolute;
    inset: 0;
    z-index: -3;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: 33%;
    gap: 12px;
    padding: 12px;
    opacity: 0.25;
    filter: blur(20px) brightness(0.85);
}

.blur-grid img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 14px;
}

.blur-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(255, 240, 230, 0.88),
        rgba(255, 255, 255, 1)
    );
    z-index: -2;
}


.decor {
    position: absolute;
    width: 90px;
    opacity: 0.5;
    animation: float 6s ease-in-out infinite;
}

.decor1 { top: 10%; left: 8%; animation-delay: 0s; }
decor2 { top: 15%; right: 10%; animation-delay: 1.2s; }
decor3 { bottom: 12%; left: 15%; animation-delay: 2.4s; }

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
    100% { transform: translateY(0px); }
}


.about-card {
    background: rgba(255, 250, 245, 0.9);
    backdrop-filter: blur(12px);
    padding: 3rem 2.5rem;
    max-width: 820px;
    border-radius: 25px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
    text-align: center;
    animation: fadeUp 0.9s ease;
}

.about-card h2 {
    font-size: 2.8rem;
    color: #d35400;
    margin-bottom: 1.2rem;
    font-weight: 800;
    letter-spacing: 1px;
}

.about-card p {
    font-size: 1.2rem;
    color: #4a3a37;
    line-height: 1.8rem;
    margin-bottom: 1rem;
}


.contact-box {
    background: white;
    border: 1px solid #ffe3cc;
    padding: 1.6rem 2rem;
    margin-top: 2rem;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(255,150,90,0.15);
    display: inline-block;
    text-align: center;
}

.contact-box h3 {
    color: #d35400;
    font-size: 1.6rem;
    margin-bottom: 0.5rem;
}

.contact-box p {
    font-size: 1.1rem;
    color: #5a4a45;
}

.contact-box strong {
    font-size: 1.2rem;
    color: #c0392b;
}


.auth-buttons {
    margin-top: 2rem;
}

.auth-buttons a {
    padding: 12px 28px;
    background: linear-gradient(135deg, #ff9a4a, #ff7f22);
    color: white;
    border-radius: 30px;
    margin: 0 10px;
    font-size: 1.1rem;
    font-weight: bold;
    text-decoration: none;
    transition: 0.3s;
    box-shadow: 0 6px 18px rgba(255,140,60,0.3);
}

.auth-buttons a:hover {
    background: #e26a00;
    transform: translateY(-3px);
}

/* Fade animation */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>

<section class="about-wrapper">

    <!-- BACKGROUND COLLAGE -->
    <div class="blur-grid">
        <?php foreach ($images as $img): ?>
            <img src="<?= $img ?>" alt="Product">
        <?php endforeach; ?>
    </div>

    <div class="blur-overlay"></div>

    <!-- Floating pastry decor -->
    <img src="/sweetbites/assets/bgimg/decor1.png" class="decor decor1">
    <img src="/sweetbites/assets/bgimg/decor2.png" class="decor decor2">
    <img src="/sweetbites/assets/bgimg/decor3.png" class="decor decor3">

    <!-- ABOUT CONTENT -->
    <div class="about-card">
        <h2>✨ About SweetBites</h2>

        <p>
            At <strong>SweetBites</strong>, we believe that every pastry tells a story.  
            From traditional Filipino breads to decadent cakes and pastries,  
            each creation is crafted to bring comfort, joy, and a touch of sweetness  
            to every moment of your day.
        </p>

        <p>
            Whether it’s a birthday, a celebration, a gift,  
            or simply your everyday craving,  
            SweetBites is here to make life sweeter — one bite at a time. 🍰💕
        </p>

        <!-- CONTACT DETAILS -->
        <div class="contact-box">
            <h3>📩 Contact Us</h3>
            <p>Have questions or want to place a custom order?</p>
            <p><strong>Email: sb@gmail.com</strong></p>
        </div>

        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="auth-buttons">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
        <?php endif; ?>
    </div>

</section>

<?php include './includes/footer.php'; ?>
