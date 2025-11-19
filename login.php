<?php
// login.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
include __DIR__ . '/includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Please enter email and password.';
    } elseif (!is_valid_email($email)) {
        $message = 'Invalid email format.';
    } else {
        // Fetch user with status check
        $stmt = mysqli_prepare($conn, "SELECT user_id, password, role, status FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $user_id, $hash, $role, $status);

        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);

            if ($status !== 'active') {
                // User is deactivated
                $message = 'Your account has been deactivated. Please contact the administrator.';
            } elseif (!empty($hash) && password_verify($password, $hash)) {
                // login success
                session_start();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;

                // Redirect admin to admin dashboard
                if ($role === 'admin') {
                    redirect('/sweetbites/admin/index.php');
                } else {
                    redirect('/sweetbites/shop.php');
                }
            } else {
                $message = 'Invalid email or password.';
            }
        } else {
            mysqli_stmt_close($stmt);
            $message = 'Invalid email or password.';
        }
    }
}
?>

<div class="form-container">
  <h2>Login</h2>
  <?php if ($message): ?>
    <p class="alert"><?php echo e($message); ?></p>
  <?php endif; ?>

  <form method="post" action="">
    <label>Email</label>
    <input type="email" name="email" required value="<?php echo isset($email) ? e($email) : ''; ?>">

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" name="login">Login</button>
  </form>

  <p>No account? <a href="/sweetbites/register.php">Register</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
