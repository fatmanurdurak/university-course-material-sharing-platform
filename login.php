<?php
require_once "config.php";
$loginRole = $_GET['role'] ?? 'student';  // default is student


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $name, $password_hash, $role);

    if ($stmt->fetch() && password_verify($pass, $password_hash)) {

    // ROLE CHECK
    if ($loginRole !== $role) {
        $errors[] = "You selected the wrong login type for this account.";
    } else {
        $_SESSION['user_id'] = $id;
        $_SESSION['name']    = $name;
        $_SESSION['role']    = $role;

        header("Location: dashboard.php");
        exit();
    }

} else {
    $errors[] = "Invalid email or password.";
}

}

$pageTitle = "Login";
include "header.php";
?>

<h2>
    <?= ($loginRole === 'professor') ? "Akademisyen Girişi" : "Öğrenci Girişi" ?>
</h2>

<?php if (isset($_GET['registered'])): ?>
    <div class="success">Registration successful. You can login now.</div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<form method="post">
    <label>Email:
        <input type="email" name="email" required>
    </label>

    <label>Password:
        <input type="password" name="password" required>
    </label>

    <button type="submit" class="btn">Login</button>
</form>
<p>
    Don’t have an account?
    <?php if ($loginRole === 'professor'): ?>
        <a href="register.php?role=professor">Register as Professor</a>
    <?php else: ?>
        <a href="register.php?role=student">Register as Student</a>
    <?php endif; ?>
</p>


<?php include "footer.php"; ?>
