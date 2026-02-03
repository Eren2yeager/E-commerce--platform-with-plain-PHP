<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// If already logged in, redirect to products
if (isLoggedIn()) {
    header("Location: products.php");
    exit;
}
// Initialize variables for form fields and errors
$errors = [];
$username = '';
$email = '';
$phone = '';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Get and sanitize input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);

    // 2. Validate inputs
    // TODO: Check if fields are empty
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required";
    }
    // TODO: Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    // TODO: Check password length (min 6 characters?)
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }


    if (empty($errors)) {
        // Check if username/email exists
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $errors[] = "Username or email already exists";
        }

        // 4. Only if still no errors, insert
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashedPassword, $phone);
            mysqli_stmt_execute($stmt);

            // Auto-login: Set session with new user's ID
            $_SESSION['user_id'] = mysqli_insert_id($conn);
            $_SESSION['username'] = $username;

            // Redirect to products page (or homepage)
            header("Location: products.php");
            exit;
        }
    }
}
$page_title = 'Register - ShopHub';
?>


    <style>
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }

        .error-messages {
            color: red;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>

    <?php require_once('../includes/header.php'); ?>
    <div class="container">
        <h1>Create Account</h1>

        <!-- Show errors if any -->
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <!-- TODO: Add form fields -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= $username ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= $email ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= $phone ?>">
            </div>
            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
    <?php require_once('../includes/footer.php'); ?>
