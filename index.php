<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions.php';
?>

<section style="
  position: relative;
  width: 100%;
  height: 100vh;
  background: url('assets/bgimg/homepage.png') no-repeat center center/cover;
  display: flex;
  flex-direction: column;
  justify-content: flex-end; /* move content to bottom */
  align-items: center;
  color: #fff;
  text-align: center;
  padding-bottom: 5rem; /* spacing from bottom */
  overflow: hidden;
">
  <div style="
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1;
  "></div>

  <div style="position: relative; z-index: 2;">
    <h1 style="
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.5);
    ">


    <a href="aboutus.php"
       style="
         background-color: #f5a623;
         color: white;
         padding: 0.9rem 2.2rem;
         border-radius: 8px;
         text-decoration: none;
         font-weight: bold;
         font-size: 1rem;
         transition: all 0.3s ease;
         box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
       "
       onmouseover="this.style.backgroundColor='#e59400'; this.style.transform='scale(1.05)';"
       onmouseout="this.style.backgroundColor='#f5a623'; this.style.transform='scale(1)';">
       Learn More
    </a>
  </div>
</section>

<?php include './includes/footer.php'; ?>
