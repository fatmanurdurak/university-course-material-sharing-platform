<?php
// header.php
if (!isset($pageTitle)) {
    $pageTitle = "Course Platform";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="main-header">
    <h1>University Course Material Sharing Platform</h1>
     <nav>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- 🔓 Sadece login olmayanlar Home görür -->
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Sign Up</a>

    <?php else: ?>
        <!-- 🔒 Login olanlar Home GÖRMEZ -->
        <a href="dashboard.php">Dashboard</a>
        <a href="notifications.php">Notifications</a>

        <?php if ($_SESSION['role'] === 'student'): ?>
            <a href="student_courses.php">Courses</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'professor' || $_SESSION['role'] === 'admin'): ?>
            <a href="create_course.php">Create Course</a>
            <a href="upload_material.php">Upload / Assignment</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin_panel.php">Admin Panel</a>
        <?php endif; ?>

        <a href="logout.php">Logout</a>
    <?php endif; ?>

</nav>



</header>

<main class="content">
