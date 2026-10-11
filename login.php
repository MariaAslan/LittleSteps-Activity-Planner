<?php
session_start();
require_once('database.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $query = 'SELECT userID, fullName, email, password
              FROM users
              WHERE email = :email';

    $statement = $db->prepare($query);
    $statement->bindValue(':email', $email);
    $statement->execute();
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    $statement->closeCursor();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['fullName'] = $user['fullName'];

        header('Location: dashboard.php');
        exit();
    }

    $error = 'Incorrect email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In - LittleSteps</title>
</head>
<body>
    <main>
        <h1>LittleSteps</h1>
        <h2>Welcome Back</h2>

        <?php if ($error !== ''): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="email">Email Address:</label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="password">Password:</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">Log In</button>
        </form>

        <p>
            Don’t have an account?
            <a href="signup.php">Sign Up</a>
        </p>
    </main>
</body>
</html>