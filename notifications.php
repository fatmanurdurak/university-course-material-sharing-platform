<?php
require_once "config.php";
require_login();

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$pageTitle = "Notifications";
include "header.php";
?>

<h2>Notifications</h2>

<?php if ($role === 'student'): ?>

    <p>Öğrenci olarak derslerinle ilgili son olayları görüyorsun.</p>

    <?php
    // 1) Son 7 günde açılan yeni assignments (ödevler) – kayıtlı olunan dersler
    $stmt = $conn->prepare(
        "SELECT a.id, a.title, a.deadline, a.created_at,
                c.code, c.title AS course_title
         FROM assignments a
         JOIN courses c ON c.id = a.course_id
         JOIN course_enrollments ce ON ce.course_id = c.id
         WHERE ce.student_id = ?
         AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY a.created_at DESC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $newAssignments = $stmt->get_result();

    // 2) Forum aktiviteleri – kayıtlı olunan derslerin forumlarından son 10 mesaj
    $stmt = $conn->prepare(
        "SELECT fp.content, fp.created_at,
                c.code, c.title AS course_title,
                u.name, u.role
         FROM forum_posts fp
         JOIN courses c ON c.id = fp.course_id
         JOIN course_enrollments ce ON ce.course_id = c.id
         JOIN users u ON u.id = fp.user_id
         WHERE ce.student_id = ?
         ORDER BY fp.created_at DESC
         LIMIT 10"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $forumPosts = $stmt->get_result();
    ?>

    <div class="panel">
        <h3>New Assignments (last 7 days)</h3>
        <?php if ($newAssignments->num_rows === 0): ?>
            <p>Son 7 günde yeni ödev açılmamış.</p>
        <?php else: ?>
            <ul>
                <?php while ($a = $newAssignments->fetch_assoc()): ?>
                    <li>
                        <strong><?= htmlspecialchars($a['course_title']) ?></strong> –
                        <?= htmlspecialchars($a['title']) ?> <br>
                        Created: <?= htmlspecialchars($a['created_at']) ?> |
                        Deadline: <?= htmlspecialchars($a['deadline']) ?> |
                        <a href="submit_assignment.php?id=<?= $a['id'] ?>">Ödeve Git</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Recent Forum Activity (your enrolled courses)</h3>
        <?php if ($forumPosts->num_rows === 0): ?>
            <p>Kayıtlı olduğun derslerin forumlarında henüz mesaj yok.</p>
        <?php else: ?>
            <ul>
                <?php while ($f = $forumPosts->fetch_assoc()): ?>
                    <li>
                        <strong><?= htmlspecialchars($f['course_title']) ?></strong> –
                        <?= htmlspecialchars($f['name']) ?>
                        <?php if ($f['role'] === 'professor'): ?>
                            (Instructor)
                        <?php elseif ($f['role'] === 'admin'): ?>
                            (Admin)
                        <?php endif; ?>
                        wrote:<br>
                        “<?= nl2br(htmlspecialchars($f['content'])) ?>” <br>
                        <small><?= htmlspecialchars($f['created_at']) ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>

<?php else: ?>

    <p>Şimdilik Notifications sayfası sadece öğrenciler için detaylı hazırlanmış durumda. İstersen hocalar için de
       “yeni submission’lar” gibi bildirimler ekleyebiliriz.</p>

<?php endif; ?>

<?php include "footer.php"; ?>

