<?php
require_once "config.php";
require_login();

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$pageTitle = "Dashboard";
include "header.php";
?>

<h2>Merhaba, <?= htmlspecialchars($_SESSION['name']) ?> (<?= htmlspecialchars($role) ?>)</h2>

<?php if ($role === 'student'): ?>

    <p>Burada kayıtlı olduğun dersleri, ödev ilerlemeni ve yaklaşan ödev tarihlerini görüyorsun.</p>

    <?php
    // 1) Bu öğrencinin kayıtlı olduğu dersler
    $sql = "SELECT c.id, c.code, c.title, c.description
            FROM courses c
            JOIN course_enrollments ce ON ce.course_id = c.id
            WHERE ce.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    // 2) Upcoming deadlines (kayıtlı olunan derslerdeki assign. -> en yakın 5)
    $sqlDead = "SELECT a.id, a.title, a.deadline, c.code, c.title AS course_title
                FROM assignments a
                JOIN courses c ON c.id = a.course_id
                JOIN course_enrollments ce ON ce.course_id = c.id
                WHERE ce.student_id = ?
                AND a.deadline IS NOT NULL
                AND a.deadline >= NOW()
                ORDER BY a.deadline ASC
                LIMIT 5";
    $stmtD = $conn->prepare($sqlDead);
    $stmtD->bind_param("i", $user_id);
    $stmtD->execute();
    $upcoming = $stmtD->get_result();
    ?>

    <!-- Upcoming Deadlines Panel -->
    <div class="panel">
        <h3>Upcoming Deadlines</h3>
        <?php if ($upcoming->num_rows === 0): ?>
            <p>Yaklaşan bir ödev teslim tarihi bulunmuyor.</p>
        <?php else: ?>
            <ul>
                <?php while ($u = $upcoming->fetch_assoc()): ?>
                    <li>
                        <strong><?= htmlspecialchars($u['course_title']) ?></strong> –
                        <?= htmlspecialchars($u['title']) ?> <br>
                        Deadline: <?= htmlspecialchars($u['deadline']) ?> |
                        <a href="submit_assignment.php?id=<?= $u['id'] ?>">Ödeve Git</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Dersler + Progress -->
    <?php if (empty($courses)): ?>
        <p>Henüz hiçbir derse kayıtlı değilsin. Üst menüden <strong>Courses</strong> sayfasına gidip derslere kayıt olabilirsin.</p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <tr style="background:#e5e7eb;">
                <th>Ders</th>
                <th>Açıklama</th>
                <th>Toplam Ödev</th>
                <th>Tamamlanan Ödev</th>
                <th>İlerleme</th>
                <th>Detay</th>
            </tr>
            <?php foreach ($courses as $c): ?>
                <?php
                $courseId = $c['id'];

                // Toplam ödev
                $stmtA = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE course_id = ?");
                $stmtA->bind_param("i", $courseId);
                $stmtA->execute();
                $stmtA->bind_result($totalAssignments);
                $stmtA->fetch();
                $stmtA->close();

                // Tamamlanan ödev
                $stmtB = $conn->prepare(
                    "SELECT COUNT(DISTINCT s.assignment_id)
                     FROM assignment_submissions s
                     JOIN assignments a ON a.id = s.assignment_id
                     WHERE s.student_id = ? AND a.course_id = ?"
                );
                $stmtB->bind_param("ii", $user_id, $courseId);
                $stmtB->execute();
                $stmtB->bind_result($completedAssignments);
                $stmtB->fetch();
                $stmtB->close();

                // Yüzde
                if ($totalAssignments > 0) {
                    $progress = round(($completedAssignments / $totalAssignments) * 100);
                } else {
                    $progress = 0;
                }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['code']) ?></strong> – <?= htmlspecialchars($c['title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($c['description'])) ?></td>
                    <td style="text-align:center;"><?= $totalAssignments ?></td>
                    <td style="text-align:center;"><?= $completedAssignments ?></td>
                    <td style="text-align:center; width:180px;">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $progress ?>%;">
                                <?= $progress ?>%
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <a class="btn" href="course.php?id=<?= $courseId ?>">Derse Git</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>


<?php elseif ($role === 'professor'): ?>

    <p>Bu ekranda verdiğin dersleri görebilirsin.</p>

    <?php
    $sql = "SELECT id, code, title, description FROM courses WHERE professor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <?php if ($result->num_rows === 0): ?>
        <p>Henüz sana atanmış bir ders yok.</p>
    <?php else: ?>
        <ul>
            <?php while ($c = $result->fetch_assoc()): ?>
                <li>
                    <strong><?= htmlspecialchars($c['code']) ?></strong> – <?= htmlspecialchars($c['title']) ?>
                    ( <a href="course.php?id=<?= $c['id'] ?>">Derse Git</a> )
                </li>
            <?php endwhile; ?>
        </ul>
        <p>
            Materyal veya ödev eklemek için üst menüden
            <strong>“Upload / Assignment”</strong> linkini kullanabilirsin.
        </p>
    <?php endif; ?>


<?php else: ?>  <!-- admin -->

    <p>Admin olarak tüm dersleri görebilirsin.</p>

    <?php
    $sql = "SELECT c.id, c.code, c.title, u.name AS professor_name
            FROM courses c
            LEFT JOIN users u ON u.id = c.professor_id";
    $result = $conn->query($sql);
    ?>

    <?php if ($result->num_rows === 0): ?>
        <p>Hiç ders bulunamadı.</p>
    <?php else: ?>
        <ul>
            <?php while ($c = $result->fetch_assoc()): ?>
                <li>
                    <strong><?= htmlspecialchars($c['code']) ?></strong> – <?= htmlspecialchars($c['title']) ?>
                    (<?= htmlspecialchars($c['professor_name'] ?? 'Unknown') ?>)
                    [<a href="course.php?id=<?= $c['id'] ?>">Derse Git</a>]
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>

<?php endif; ?>

<?php include "footer.php"; ?>
