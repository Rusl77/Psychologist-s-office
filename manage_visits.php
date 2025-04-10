<?php
session_start();
require "dbconnect.php";

// Проверка авторизации и типа пользователя
if (!isset($_SESSION['login']) || $_SESSION['user_type'] != 'specialist') {
    $_SESSION['msg'] = "Доступ запрещен. Пожалуйста, войдите как специалист.";
    header('Location: /index.php');
    exit();
}

$specialist_id = $_SESSION['id'];

// Обработка удаления записи
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $visit_id = (int)$_GET['delete'];

    try {
        // Проверка наличия связанных записей (для контроля целостности)
        $check_sql = "SELECT COUNT(*) FROM visit_records WHERE visit_id = :visit_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':visit_id', $visit_id);
        $check_stmt->execute();
        $related_records = $check_stmt->fetchColumn();

        if ($related_records > 0) {
            $_SESSION['msg'] = "Невозможно удалить запись, так как с ней связаны записи в журнале посещений. Вы можете отметить её как отмененную.";
        } else {
            // Проверяем, принадлежит ли запись этому специалисту
            $delete_sql = "DELETE FROM visit WHERE id = :visit_id AND id_med = :specialist_id";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bindValue(':visit_id', $visit_id);
            $delete_stmt->bindValue(':specialist_id', $specialist_id);
            $delete_stmt->execute();

            if ($delete_stmt->rowCount() > 0) {
                $_SESSION['msg'] = "Запись успешно удалена!";
            } else {
                $_SESSION['msg'] = "Ошибка при удалении записи. Возможно, она не принадлежит вам или уже была удалена.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['msg'] = "Ошибка при удалении записи: " . $e->getMessage();
    }

    // Перенаправляем обратно на страницу управления
    header('Location: /manage_visits.php');
    exit();
}

// Обработка отметки о завершении приема
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $visit_id = (int)$_GET['complete'];

    try {
        // Проверяем, принадлежит ли запись этому специалисту
        $check_sql = "SELECT id FROM visit WHERE id = :visit_id AND id_med = :specialist_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':visit_id', $visit_id);
        $check_stmt->bindValue(':specialist_id', $specialist_id);
        $check_stmt->execute();

        if ($check_stmt->fetchColumn()) {
            // Добавляем запись в журнал посещений
            $insert_sql = "INSERT INTO visit_records (visit_id, status, notes) VALUES (:visit_id, 'completed', 'Приём завершен')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bindValue(':visit_id', $visit_id);
            $insert_stmt->execute();

            $_SESSION['msg'] = "Приём отмечен как завершенный!";
        } else {
            $_SESSION['msg'] = "Ошибка при отметке о завершении приема. Возможно, он не принадлежит вам.";
        }
    } catch (PDOException $e) {
        $_SESSION['msg'] = "Ошибка при отметке о завершении приема: " . $e->getMessage();
    }

    // Перенаправляем обратно на страницу управления
    header('Location: /manage_visits.php');
    exit();
}

// Получение данных о визитах специалиста
try {
    $sql = "SELECT v.id, v.day, v.time, c.name as client_name, c.id as client_id, cat.name as category_name,
            CASE WHEN vr.id IS NOT NULL THEN vr.status ELSE 'scheduled' END as status
            FROM visit v 
            JOIN client c ON v.id_cli = c.id 
            JOIN categories cat ON v.category = cat.id
            LEFT JOIN visit_records vr ON v.id = vr.visit_id AND vr.id = (
                SELECT MAX(id) FROM visit_records WHERE visit_id = v.id
            )
            WHERE v.id_med = :specialist_id 
            ORDER BY v.day, v.time";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':specialist_id', $specialist_id);
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Ошибка при получении данных о приемах: " . $e->getMessage();
}

include "menu.php";
?>

    <div class="container mt-5 pt-5">
        <h1 class="text-center mb-4">Управление записями</h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Ваши записи</h5>
            </div>
            <div class="card-body">
                <?php if (empty($visits)): ?>
                    <div class="alert alert-info">
                        У вас пока нет запланированных приемов.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Клиент</th>
                                <th>Категория</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($visits as $visit): ?>
                                <tr <?php if ($visit['status'] == 'completed') echo 'class="table-success"'; elseif ($visit['status'] == 'cancelled') echo 'class="table-danger"'; elseif ($visit['status'] == 'rescheduled') echo 'class="table-warning"'; ?>>
                                    <td><?= $visit['id'] ?></td>
                                    <td><?= htmlspecialchars($visit['day']) ?></td>
                                    <td><?= htmlspecialchars($visit['time']) ?></td>
                                    <td><?= htmlspecialchars($visit['client_name']) ?></td>
                                    <td><?= htmlspecialchars($visit['category_name']) ?></td>
                                    <td>
                                        <?php
                                        if ($visit['status'] == 'completed') echo 'Завершен';
                                        elseif ($visit['status'] == 'cancelled') echo 'Отменен';
                                        elseif ($visit['status'] == 'rescheduled') echo 'Перенесен';
                                        else echo 'Запланирован';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="visit.php?visitId=<?= $visit['id'] ?>" class="btn btn-sm btn-info">Подробнее</a>

                                            <?php if ($visit['status'] == 'scheduled'): ?>
                                                <a href="manage_visits.php?complete=<?= $visit['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Отметить этот приём как завершенный?');">Завершить</a>
                                                <a href="manage_visits.php?delete=<?= $visit['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center">
            <a href="specialist_dashboard.php" class="btn btn-secondary">Вернуться в личный кабинет</a>
        </div>
    </div>

<?php
// Отображение сообщений, если они есть
if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') {
    require "message.php";
    $_SESSION['msg'] = '';
}

require "footer.php";
?>