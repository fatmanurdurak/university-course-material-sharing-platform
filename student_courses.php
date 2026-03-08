<?php
require_once "config.php";
require_login();

// sadece öğrenci girebilsin
if ($_SESSION['role'] !== 'student') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = "";

// POST ile enroll / leave işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $action    = $_POST['action'] ?? '';

    if ($course_id <= 0) {
        $errors[] = "Invalid course.";
    } else {
        if ($action === 'enroll') {
            // derse kayıt ol
            $stmt = $conn->prepare(
                "INSERT INTO course_enrollments (student_id, course_id)
                 VALUES (?, ?)"
            );
            $stmt->bind_param("ii", $user_id, $course_id);
            if ($stmt->execute()) {
                $success = "You have enrolled in the course.";
            } else {
                // muhtemelen UNIQUE constraint (zaten kayıtlı)
                $errors[] = "You are already enrolled in this course or a database error occurred.";
            }

        } elseif ($action === 'leave') {
            // dersten ayrıl
            $stmt = $conn->prepare(
                "DELETE FROM course_enrollments
                 WHERE student_id = ? AND course_id = ?"
            );
            $stmt->bind_param("ii", $user_id, $course_id);
            if ($stmt->execute()) {
                $success = "You have left the course.";
            } else {
                $errors[] = "Database error while leaving the course.";
            }
        }
    }
}

// TÜM DERSLERİ çek + bu öğrenci kayıtlı mı bilgisini ekle
$stmt = $conn->prepare(
    "SELECT c.id,
            c.code,
            c.title,
            c.description,
            u.name AS professor_name,
            CASE WHEN ce.id IS NULL THEN 0 ELSE 1 END AS enrolled
     FROM courses c
     LEFT JOIN users u ON u.id = c.professor_id
     LEFT JOIN course_enrollments ce
         ON ce.course_id = c.id AND ce.student_id = ?
     ORDER BY c.code"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses = $stmt->get_result();

$pageTitle = "Courses";
include "header.php";
?>

<h2>Ders Listesi</h2>

<p>Buradan mevcut dersleri görebilir ve istediğin derslere kayıt olup çıkabilirsin.</p>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($courses->num_rows === 0): ?>
    <p>Şu anda sistemde hiç ders bulunmuyor.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
        <tr style="background:#e5e7eb;">
            <th>Ders</th>
            <th>Öğretim Elemanı</th>
            <th>Açıklama</th>
            <th>Durum</th>
            <th>İşlem</th>
        </tr>
        <?php while ($c = $courses->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['code']) ?></strong> – <?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['professor_name'] ?? 'Unknown') ?></td>
                <td><?= nl2br(htmlspecialchars($c['description'])) ?></td>
                <td style="text-align:center;">
                    <?= $c['enrolled'] ? "Kayıtlı" : "Kayıtlı Değil" ?>
                </td>
                <td style="text-align:center;">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <?php if ($c['enrolled']): ?>
                            <input type="hidden" name="action" value="leave">
                            <button type="submit" class="btn">Leave</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enroll">
                            <button type="submit" class="btn">Enroll</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<?php include "footer.php"; ?>
