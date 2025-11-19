<?php
// register.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
include __DIR__ . '/includes/header.php';

$message = '';
$fullname = '';
$email = '';
$phone = '';
$age = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullname = e($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = e($_POST['phone'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Basic validations
    if ($fullname === '' || $email === '' || $phone === '' || $age === 0 || $password === '' || $confirm === '') {
        $message = 'Please fill all required fields.';
    } elseif (!is_valid_email($email)) {
        $message = 'Invalid email address.';
    } elseif (!preg_match('/^\+63\d{10}$|^09\d{9}$/', $phone)) {
        $message = 'Invalid Philippine phone number. Use +63XXXXXXXXXX or 09XXXXXXXXX format.';
    } elseif ($age < 13 || $age > 120) {
        $message = 'Please enter a valid age (13-120).';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } elseif (!is_strong_password($password)) {
        $message = 'Password must be at least 8 characters and include letters and numbers.';
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $message = 'Email already registered.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (email, password, role) VALUES (?, ?, 'customer')");
            mysqli_stmt_bind_param($stmt2, 'ss', $email, $hash);
            if (mysqli_stmt_execute($stmt2)) {
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt2);

                // Handle profile picture upload
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['profile_picture']['type'];
                    $file_size = $_FILES['profile_picture']['size'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    if (!in_array($file_type, $allowed_types)) {
                        $message = 'Invalid image format. Only JPG, PNG, and GIF allowed.';
                    } elseif ($file_size > $max_size) {
                        $message = 'Image size exceeds 5MB limit.';
                    } else {
                        $upload_dir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $profile_picture = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture);
                    }
                }

                if (!$message) {
                    // insert profile with phone, age and picture
                    $stmt3 = mysqli_prepare($conn, "INSERT INTO customer_info (user_id, full_name, phone, age, profile_picture) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt3, 'issss', $user_id, $fullname, $phone, $age, $profile_picture);
                    if (mysqli_stmt_execute($stmt3)) {
                        $message = 'Registration successful. You can now log in.';
                        // Clear form fields after successful registration
                        $fullname = '';
                        $email = '';
                        $phone = '';
                        $age = '';
                    } else {
                        error_log('Register DB error: ' . mysqli_error($conn));
                        $message = 'Registration failed, please try again later.';
                    }
                    mysqli_stmt_close($stmt3);
                }
            } else {
                error_log('Register DB error: ' . mysqli_error($conn));
                $message = 'Registration failed, please try again later.';
            }
        }
    }
}
?>

<div class="form-container">
  <h2>Create account</h2>
  <?php if ($message): ?>
    <p class="alert"><?php echo e($message); ?></p>
  <?php endif; ?>

  <form method="post" action="" enctype="multipart/form-data">
    <label>Full name</label>
    <input type="text" name="fullname" required value="<?php echo e($fullname); ?>">

    <label>Email</label>
    <input type="email" name="email" required value="<?php echo e($email); ?>">

    <label>Phone number</label>
    <input type="tel" name="phone" required value="<?php echo e($phone); ?>" placeholder="09XXXXXXXXX or +63XXXXXXXXXX">

    <label>Age</label>
    <input type="number" name="age" min="13" max="120" required value="<?php echo ($age > 0) ? (int)$age : ''; ?>">

    <label>Profile picture</label>
    <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/gif">
    <small>Optional. Max 5MB. Accepted formats: JPG, PNG, GIF</small>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm password</label>
    <input type="password" name="confirm" required>

    <button type="submit" name="register">Register</button>
  </form>

<p>Already have an account? <a href="/sweetbites/login.php">Login</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>