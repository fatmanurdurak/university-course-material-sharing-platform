<?php
require_once "config.php";
require_login();

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id       = $_SESSION['user_id'];

// ödev bilgilerini çek
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

// mevcut submission sayısını say (resource usage)
$stmt = $conn->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$stmt->bind_result($current_count);
$stmt->fetch();
$stmt->close();

$limit_reached = ($current_count >= $assignment['max_submissions']);

$errors = [];
$success = "";
$alreadySubmitted = false;

// bu öğrenci daha önce submit etmiş mi? (isterseniz tek hakkı olsun)
$stmt = $conn->prepare(
    "SELECT id, answer, is_correct, submitted_at
     FROM assignment_submissions
     WHERE assignment_id = ? AND student_id = ?"
);
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    $alreadySubmitted = true;
}

// FORM SUBMIT ise ve limit dolmamışsa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$limit_reached && !$alreadySubmitted) {
    $answer = trim($_POST['answer']);

    if ($answer === "") {
        $errors[] = "Answer cannot be empty.";
    } else {
        // auto-check: correct_answer doluysa küçük/büyük harf önemseme
        $is_correct = null;
        if (!empty($assignment['correct_answer'])) {
            $is_correct = (strcasecmp(trim($assignment['correct_answer']), $answer) === 0) ? 1 : 0;
        }

        $stmt = $conn->prepare(
            "INSERT INTO assignment_submissions (assignment_id, student_id, answer, is_correct)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iisi", $assignment_id, $user_id, $answer, $is_correct);

        if ($stmt->execute()) {
            $success = "Your answer has been saved.";
            $alreadySubmitted = true;
        } else {
            $errors[] = "Database error while saving answer.";
        }
    }
}

$pageTitle = "Submit Assignment";
include "header.php";
?>

<h2><?= htmlspecialchars($assignment['title']) ?></h2>
<p><strong>Course:</strong> <?= htmlspecialchars($assignment['course_code'] . " – " . $assignment['course_title']) ?></p>
<p><strong>Question:</strong><br><?= nl2br(htmlspecialchars($assignment['question'])) ?></p>
<p><strong>Deadline:</strong> <?= htmlspecialchars($assignment['deadline']) ?></p>
<p><strong>Submissions:</strong> <?= $current_count ?> / <?= $assignment['max_submissions'] ?></p>

<?php if ($limit_reached): ?>
    <div class="error">
        Submission limit reached for this assignment.  
        You cannot submit, because previous users used all available slots.
    </div>
<?php endif; ?>

<?php if ($alreadySubmitted && $existing): ?>
    <div class="info">
        <p><strong>You have already submitted:</strong></p>
        <p><?= nl2br(htmlspecialchars($existing['answer'])) ?></p>
        <?php if ($existing['is_correct'] !== null): ?>
            <p>Result: <?= $existing['is_correct'] ? "Correct ✅" : "Incorrect ❌" ?></p>
        <?php endif; ?>
        <p>Submitted at: <?= htmlspecialchars($existing['submitted_at']) ?></p>
    </div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$limit_reached && !$alreadySubmitted): ?>
    <form method="post">
        <label>Your Answer:
            <textarea name="answer" rows="5" required></textarea>
        </label>
        <button type="submit" class="btn">Submit</button>
    </form>
<?php endif; ?>

<?php include "footer.php"; ?>
