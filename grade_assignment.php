<?php
require_once "config.php";
require_login();

// sadece professor veya admin
if ($_SESSION['role'] !== 'professor' && $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($assignment_id <= 0) {
    die("Invalid assignment.");
}

// ÖDEV + DERs bilgisi
$stmt = $conn->prepare(
    "SELECT a.*, c.title AS course_title, c.code AS course_code
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     WHERE a.id = ?"
);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("Assignment not found.");
}

// Bu ödevin submissions listesi
$errors = [];
$success = "";

// Grade/feedback update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $sub_id  = (int)$_POST['submission_id'];
    $grade   = ($_POST['grade'] === "") ? null : (int)$_POST['grade'];
    $feedback = trim($_POST['feedback']);

    $stmtU = $conn->prepare(
        "UPDATE assignment_submissions
         SET grade = ?, feedback = ?
         WHERE id = ?"
    );
    $stmtU->bind_param("isi", $grade, $feedback, $sub_id);

    if ($stmtU->execute()) {
        $success = "Grade/feedback updated.";
    } else {
        $errors[] = "Error while updating grade.";
    }
}

// Submissions yeniden çek
$stmt = $conn->prepare(
    "SELECT s.*, u.name AS student_name
     FROM assignment_submissions s
     JOIN users u ON u.id = s.student_id
     WHERE s.assignment_id = ?
     ORDER BY s.submitted_at DESC"
);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$subs = $stmt->get_result();

$pageTitle = "Grade Assignment";
include "header.php";
?>

<h2>Submissions – <?= htmlspecialchars($assignment['title']) ?></h2>
<p><strong>Course:</strong> <?= htmlspecialchars($assignment['course_code'] . " – " . $assignment['course_title']) ?></p>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($subs->num_rows === 0): ?>
    <p>Bu ödev için henüz submission yok.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
        <tr style="background:#e5e7eb;">
            <th>Öğrenci</th>
            <th>Cevap</th>
            <th>Doğru mu?</th>
            <th>Not (0–100)</th>
            <th>Feedback</th>
            <th>Kaydet</th>
        </tr>
        <?php while ($s = $subs->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($s['student_name']) ?><br>
                    <small><?= htmlspecialchars($s['submitted_at']) ?></small>
                </td>
                <td><?= nl2br(htmlspecialchars($s['answer'])) ?></td>
                <td style="text-align:center;">
                    <?php
                    if ($s['is_correct'] === null) {
                        echo "-";
                    } elseif ($s['is_correct']) {
                        echo "✅";
                    } else {
                        echo "❌";
                    }
                    ?>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                        <input type="number" name="grade" min="0" max="100"
                               value="<?= htmlspecialchars($s['grade']) ?>">
                </td>
                <td>
                        <textarea name="feedback" rows="2"><?= htmlspecialchars($s['feedback']) ?></textarea>
                </td>
                <td style="text-align:center;">
                        <button type="submit" class="btn">Save</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<?php include "footer.php"; ?>
