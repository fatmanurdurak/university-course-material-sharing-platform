<?php
$defaultRole = $_GET['role'] ?? 'student';

require_once "config.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role'] ?? 'student';

    if ($name === "" || $email === "" || $pass === "") {
        $errors[] = "All fields are required.";
    } else {
        $password_hash = password_hash($pass, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $email, $password_hash, $role);

        if ($stmt->execute()) {
            // after successful sign-up, go to login page
            header("Location: login.php?registered=1");
            exit();
        } else {
            $errors[] = "This email is already registered, or there was a database error.";
        }
    }
}

$pageTitle = "Sign Up";
include "header.php";
?>

<h2>Create an Account</h2>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<form method="post">
    <label>Name:
        <input type="text" name="name" required>
    </label>

    <label>Email:
        <input type="email" name="email" required>
    </label>

    <label>Password:
        <input type="password" name="password" required>
    </label>

   <label>Role:
    <select name="role">
        <option value="student" <?= $defaultRole === 'student' ? 'selected' : '' ?>>Student</option>
        <option value="professor" <?= $defaultRole === 'professor' ? 'selected' : '' ?>>Professor</option>
    </select>
   </label>


    <button type="submit" class="btn">Register</button>
</form>

<?php include "footer.php"; ?>
