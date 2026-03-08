<?php
require_once "config.php";
require_login();

// sadece hoca veya admin girebilsin
if ($_SESSION['role'] !== 'professor' && $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// hocanın derslerini çek
if ($_SESSION['role'] === 'professor') {
    $stmt = $conn->prepare("SELECT id, code, title FROM courses WHERE professor_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
} else { // admin ise tüm dersler
    $stmt = $conn->prepare("SELECT id, code, title FROM courses");
}
$stmt->execute();
$courses = $stmt->get_result();

$errors = [];
$success = "";

// FORM POST EDİLDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id   = (int)$_POST['course_id'];
    $type        = $_POST['type']; // material OR assignment
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($title === "" || $course_id <= 0) {
        $errors[] = "Course and title are required.";
    } else {
        if ($type === 'material') {
            // DOSYA YÜKLEME
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir);
                }

                $fileName   = time() . "_" . basename($_FILES['file']['name']);
                $targetPath = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO materials (course_id, title, description, file_path)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("isss", $course_id, $title, $description, $targetPath);
                    $stmt->execute();
                    $success = "Material uploaded successfully.";
                } else {
                    $errors[] = "File upload failed.";
                }
            } else {
                $errors[] = "Please select a file to upload.";
            }

        } else { // ASSIGNMENT
            $question       = trim($_POST['question']);
            $correct_answer = trim($_POST['correct_answer']);
            $max_sub        = (int)$_POST['max_submissions'];
            $deadline       = $_POST['deadline'];

            if ($question === "") {
                $errors[] = "Question cannot be empty.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO assignments (course_id, title, question, correct_answer, max_submissions, deadline)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("isssis",
                    $course_id,
                    $title,
                    $question,
                    $correct_answer,
                    $max_sub,
                    $deadline
                );
                $stmt->execute();
                $success = "Assignment created successfully (with submission limit).";
            }
        }
    }
}

$pageTitle = "Upload / Create Assignment";
include "header.php";
?>

<h2>Upload Material / Create Assignment</h2>

<?php foreach ($errors as $e): ?>
    <div class="error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Course:
        <select name="course_id" required>
            <?php while ($c = $courses->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['code'] . " – " . $c['title']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Type:
        <select name="type" id="type-select">
            <option value="material">Material (PDF, PPT, etc.)</option>
            <option value="assignment">Assignment / Quiz</option>
        </select>
    </label>

    <label>Title:
        <input type="text" name="title" required>
    </label>

    <label>Description:
        <textarea name="description"></textarea>
    </label>

    <!-- MATERIAL FIELDS -->
    <div id="material-fields">
        <label>File:
            <input type="file" name="file">
        </label>
    </div>

    <!-- ASSIGNMENT FIELDS -->
    <div id="assignment-fields" style="display:none;">
        <label>Question:
            <textarea name="question"></textarea>
        </label>

        <label>Correct Answer (for auto-check, optional):
            <input type="text" name="correct_answer">
        </label>

        <label>Max Submissions (resource limit):
            <input type="number" name="max_submissions" value="50" min="1">
        </label>

        <label>Deadline:
            <input type="datetime-local" name="deadline">
        </label>
    </div>

    <button type="submit" class="btn">Save</button>
</form>

<script>
// basit JS: dropdown değişince alanları göster/gizle
const typeSelect = document.getElementById('type-select');
const matFields  = document.getElementById('material-fields');
const assFields  = document.getElementById('assignment-fields');

typeSelect.addEventListener('change', () => {
    if (typeSelect.value === 'material') {
        matFields.style.display = 'block';
        assFields.style.display = 'none';
    } else {
        matFields.style.display = 'none';
        assFields.style.display = 'block';
    }
});
</script>

<?php include "footer.php"; ?>
