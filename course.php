<?php
require_once "config.php";
require_login();

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($course_id <= 0) {
    die("Invalid course.");
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// DERS BİLGİLERİ
$stmt = $conn->prepare(
    "SELECT c.*, u.name AS professor_name
     FROM courses c
     LEFT JOIN users u ON u.id = c.professor_id
     WHERE c.id = ?"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    die("Course not found.");
}

$errors = [];

// YENİ FORUM MESAJI GELDİ Mİ?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_content'])) {
    $content = trim($_POST['forum_content']);

    if ($content === "") {
        $errors[] = "Mesaj boş olamaz.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO forum_posts (course_id, user_id, content)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $course_id, $user_id, $content);

        if (!$stmt->execute()) {
            $errors[] = "Mesaj kaydedilirken bir hata oluştu.";
        }
    }
}

// MATERYALLER
$stmt = $conn->prepare(
    "SELECT * FROM materials
     WHERE course_id = ?
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$materials = $stmt->get_result();

// ÖDEVLER
$stmt = $conn->prepare(
    "SELECT * FROM assignments
     WHERE course_id = ?
     ORDER BY deadline ASC"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$assignments = $stmt->get_result();

// FORUM MESAJLARI
$stmt = $conn->prepare(
    "SELECT fp.*, u.name, u.role
     FROM forum_posts fp
     JOIN users u ON u.id = fp.user_id
     WHERE fp.course_id = ?
     ORDER BY fp.created_at DESC"
);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$posts = $stmt->get_result();

$pageTitle = $course['title'];
include "header.php";
?>

<h2><?= htmlspecialchars($course['code'] . " – " . $course['title']) ?></h2>

<p><strong>Professor:</strong> <?= htmlspecialchars($course['professor_name'] ?? 'Unknown') ?></p>

<?php if (!empty($course['description'])): ?>
    <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>
<?php endif; ?>

<hr>

<section>
    <h3>Course Materials</h3>

    <?php if ($materials->num_rows === 0): ?>
        <p>Bu ders için henüz materyal yüklenmemiş.</p>
    <?php else: ?>
        <ul>
            <?php while ($m = $materials->fetch_assoc()): ?>
                <li style="margin-bottom:8px;">
                    <strong><?= htmlspecialchars($m['title']) ?></strong><br>
                    <?php if (!empty($m['description'])): ?>
                        <?= nl2br(htmlspecialchars($m['description'])) ?><br>
                    <?php endif; ?>
                    <a class="btn" href="<?= htmlspecialchars($m['file_path']) ?>" download>Download</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
</section>

<hr>

<section>
    <h3>Assignments</h3>

    <?php if ($assignments->num_rows === 0): ?>
        <p>Bu ders için henüz ödev açılmamış.</p>
    <?php else: ?>
        <ul>
            <?php while ($a = $assignments->fetch_assoc()): ?>
                <li style="margin-bottom:8px;">
                    <strong><?= htmlspecialchars($a['title']) ?></strong><br>
                    Deadline: <?= htmlspecialchars($a['deadline']) ?><br>
                    <a class="btn" href="submit_assignment.php?id=<?= $a['id'] ?>">Ödeve Git</a>
                    <?php if ($user_role === 'professor' || $user_role === 'admin'): ?>
                        <a class="btn" href="grade_assignment.php?id=<?= $a['id'] ?>">View Submissions</a>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
</section>

<hr>

<section>
    <h3>Discussion Forum</h3>
    <p>Bu bölümde dersle ilgili soru sorabilir, fikir paylaşabilir ve hocanla/arkadaşlarınla tartışabilirsin.</p>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <!-- Yeni mesaj formu -->
    <form method="post">
        <label>Mesajın:
            <textarea name="forum_content" rows="4" required></textarea>
        </label>
        <button type="submit" class="btn">Gönder</button>
    </form>

    <h4 style="margin-top:20px;">Son Mesajlar</h4>

    <?php if ($posts->num_rows === 0): ?>
        <p>Bu derste henüz mesaj yok. İlk mesajı sen yazabilirsin 😊</p>
    <?php else: ?>
        <?php while ($p = $posts->fetch_assoc()): ?>
            <div class="forum-post">
                <div class="forum-meta">
                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                    <?php if ($p['role'] === 'professor'): ?>
                        <span>(Instructor)</span>
                    <?php elseif ($p['role'] === 'admin'): ?>
                        <span>(Admin)</span>
                    <?php endif; ?>
                    · <?= htmlspecialchars($p['created_at']) ?>
                </div>
                <div class="forum-content">
                    <?= nl2br(htmlspecialchars($p['content'])) ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

<?php include "footer.php"; ?>

