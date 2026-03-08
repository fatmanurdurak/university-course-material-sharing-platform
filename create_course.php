<?php
require_once "config.php";
require_login();

// sadece professor veya admin ders ekleyebilir
if ($_SESSION['role'] !== 'professor' && $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$errors  = [];
$success = "";

// FORM POST EDİLDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code        = trim($_POST['code']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($code === "" || $title === "") {
        $errors[] = "Course code and title are required.";
    } else {
        $professor_id = $_SESSION['user_id'];

        $stmt = $conn->prepare(
            "INSERT INTO courses (code, title, description, professor_id)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $code, $title, $description, $professor_id);

        if ($stmt->execute()) {
            $success = "Course created successfully.";
        } else {
            $errors[] = "Database error while creating course.";
        }
    }
}

$pageTitle = "Create Course";
include "header.php";
?>

<h2>Yeni Ders Oluştur</h2>

<p>Bu formu doldurarak yeni bir ders ekleyebilirsin. Eklenen ders, Dashboard ekranında verdiğin dersler arasında görünecek.</p>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post">
    <label>Ders Kodu (ör. CENG361):
        <input type="text" name="code" required>
    </label>

    <label>Ders Adı:
        <input type="text" name="title" required>
    </label>

    <label>Açıklama:
        <textarea name="description" rows="4"></textarea>
    </label>

    <button type="submit" class="btn">Dersi Oluştur</button>
</form>

<?php include "footer.php"; ?>
