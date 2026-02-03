<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// If already logged in, redirect to products
if (isLoggedIn()){
    header("Location: products.php");
    exit;
}

$errors = [];
$login_input = ''; // Will store username or email

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Get input
    $login_input = trim($_POST['login']); // username or email
    $password = $_POST['password'];
    
    // 2. Validate not empty
    // TODO: Check if fields are empty
    if (empty($login_input) || empty($password)) {
        $errors[] = "All fields are required";
    }
    
    // 3. If no errors, query database for user
    if (empty($errors)) {
        // TODO: SELECT user WHERE username = ? OR email = ?
        // Use prepared statement!
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        // 4. Check if user exists
        if ($user) {
            // 5. Verify password
            // TODO: Use password_verify($password, $user['password'])
            
            if (password_verify($password, $user['password'])) {
                // 6. Set session and redirect
                // TODO: Set $_SESSION['user_id'] and $_SESSION['username']
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // TODO: Redirect to products.php
                header("Location: products.php");
                exit;
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}
?>


    <style>
        /* Reuse similar styles from register.php */
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .error-messages {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #1976D2;
        }
    </style>

    <?php require_once('../includes/header.php'); ?>
    <div class="container">
        <h1>Login</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($login_input) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
    <?php require_once('../includes/footer.php'); ?>
