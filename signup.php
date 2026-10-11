<?php
require_once('database.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $fullName === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {
        $error = 'Please complete all fields correctly.';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $query = 'INSERT INTO users (fullName, email, password)
                      VALUES (:fullName, :email, :password)';

            $statement = $db->prepare($query);
            $statement->bindValue(':fullName', $fullName);
            $statement->bindValue(':email', $email);
            $statement->bindValue(':password', $passwordHash);
            $statement->execute();
            $statement->closeCursor();

            $message = 'Account created successfully!';
        } catch (PDOException $errorObject) {
            $error = 'This email address may already be registered.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - LittleSteps</title>
</head>
<body>
    <main>
        <h1>LittleSteps</h1>
        <h2>Create Account</h2>

        <?php if ($message !== ''): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="signup.php" method="post">
            <label for="fullName">Full Name:</label>
            <input
                type="text"
                id="fullName"
                name="fullName"
                required
            >

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

            <button type="submit">Sign Up</button>
        </form>
    </main>
</body>
</html>