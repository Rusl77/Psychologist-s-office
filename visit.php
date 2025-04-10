<?php
session_start();
require "dbconnect.php";

// Проверка авторизации
if (!isset($_SESSION['login'])) {
    $_SESSION['msg'] = "Доступ запрещен. Пожалуйста, войдите в систему.";
    header('Location: /index.php');
    exit();
}

// Получение ID визита из GET-параметра
$visit_id = isset($_GET['visitId']) ? (int)$_GET['visitId'] : 0;

// Проверка наличия ID визита
if ($visit_id <= 0) {
    $_SESSION['msg'] = "Ошибка: не указан ID записи.";
    if ($_SESSION['user_type'] == 'client') {
        header('Location: /client_dashboard.php');
    } else {
        header('Location: /specialist_dashboard.php');
    }
    exit();
}

// Получение информации о визите в зависимости от типа пользователя
try {
    if ($_SESSION['user_type'] == 'client') {
        $sql = "SELECT v.id, v.day, v.time, v.notes, s.name as specialist_name, s.id as specialist_id, 
                s.phone_number as specialist_phone, s.photo as specialist_photo, cat.name as category_name,
                cat.description as category_description, s.description as specialist_description
                FROM visit v 
                JOIN specialist s ON v.id_med = s.id 
                JOIN categories cat ON v.category = cat.id
                WHERE v.id = :visit_id AND v.id_cli = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':visit_id', $visit_id);
        $stmt->bindValue(':user_id', $_SESSION['id']);
    } else {
        $sql = "SELECT v.id, v.day, v.time, v.notes, c.name as client_name, c.id as client_id, 
                c.phone_number as client_phone, c.Birth as client_birth, cat.name as category_name,
                cat.description as category_description
                FROM visit v 
                JOIN client c ON v.id_cli = c.id 
                JOIN categories cat ON v.category = cat.id
                WHERE v.id = :visit_id AND v.id_med = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':visit_id', $visit_id);
        $stmt->bindValue(':user_id', $_SESSION['id']);
    }

    $stmt->execute();
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        $_SESSION['msg'] = "Ошибка: запись не найдена или у вас нет прав для её просмотра.";
        if ($_SESSION['user_type'] == 'client') {
            header('Location: /client_dashboard.php');
        } else {
            header('Location: /specialist_dashboard.php');
        }
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['msg'] = "Ошибка при получении данных о записи: " . $e->getMessage();
    if ($_SESSION['user_type'] == 'client') {
        header('Location: /client_dashboard.php');
    } else {
        header('Location: /specialist_dashboard.php');
    }
    exit();
}

// Обработка сохранения заметок (только для специалистов)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes']) && $_SESSION['user_type'] == 'specialist') {
    $notes = $_POST['visit_notes'] ?? '';

    try {
        $stmt = $conn->prepare("UPDATE visit SET notes = :notes WHERE id = :visit_id");
        $stmt->bindValue(':notes', $notes);
        $stmt->bindValue(':visit_id', $visit_id);
        $stmt->execute();

        $_SESSION['msg'] = "Заметки успешно сохранены!";
        $visit['notes'] = $notes; // Обновляем данные в текущем объекте
    } catch (PDOException $e) {
        $error_message = "Ошибка при сохранении заметок: " . $e->getMessage();
    }
}

// Обработка отмены записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_visit'])) {
    // Проверяем, что запись принадлежит этому пользователю
    try {
        // Проверка наличия связанных записей (пример проверки целостности)
        // В реальном приложении здесь может быть проверка связанных таблиц
        $check_sql = "SELECT COUNT(*) FROM visit_records WHERE visit_id = :visit_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':visit_id', $visit_id);
        $check_stmt->execute();
        $related_records = $check_stmt->fetchColumn();

        if ($related_records > 0) {
            $delete_error = "Невозможно удалить запись, так как с ней связаны записи в журнале посещений.";
        } else {
            $delete_sql = "DELETE FROM visit WHERE id = :visit_id AND ";

            if ($_SESSION['user_type'] == 'client') {
                $delete_sql .= "id_cli = :user_id";
            } else {
                $delete_sql .= "id_med = :user_id";
            }

            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bindValue(':visit_id', $visit_id);
            $delete_stmt->bindValue(':user_id', $_SESSION['id']);
            $delete_stmt->execute();

            if ($delete_stmt->rowCount() > 0) {
                $_SESSION['msg'] = "Запись успешно отменена!";
                if ($_SESSION['user_type'] == 'client') {
                    header('Location: /client_dashboard.php');
                } else {
                    header('Location: /specialist_dashboard.php');
                }
                exit();
            } else {
                $delete_error = "Ошибка при отмене записи. Проверьте ваши права доступа.";
            }
        }
    } catch (PDOException $e) {
        $delete_error = "Ошибка при отмене записи: " . $e->getMessage();
    }
}

include "menu.php";
?>

    <div class="container mt-5 pt-5">
        <h1 class="text-center mb-4">Информация о записи</h1>

        <?php if (isset($error_message) || isset($delete_error)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?? $delete_error ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Детали записи #<?= $visit_id ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Дата:</strong> <?= htmlspecialchars($visit['day']) ?></p>
                                <p><strong>Время:</strong> <?= htmlspecialchars($visit['time']) ?></p>
                                <p><strong>Категория:</strong> <?= htmlspecialchars($visit['category_name']) ?></p>
                                <?php if (!empty($visit['category_description'])): ?>
                                    <p><strong>Описание категории:</strong> <?= nl2br(htmlspecialchars($visit['category_description'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($_SESSION['user_type'] == 'client'): ?>
                                    <p><strong>Специалист:</strong> <?= htmlspecialchars($visit['specialist_name']) ?></p>
                                    <p><strong>Телефон специалиста:</strong> <?= htmlspecialchars($visit['specialist_phone']) ?></p>
                                <?php else: ?>
                                    <p><strong>Клиент:</strong> <?= htmlspecialchars($visit['client_name']) ?></p>
                                    <p><strong>Телефон клиента:</strong> <?= htmlspecialchars($visit['client_phone']) ?></p>
                                    <?php if (!empty($visit['client_birth'])): ?>
                                        <p><strong>Дата рождения клиента:</strong> <?= htmlspecialchars($visit['client_birth']) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($_SESSION['user_type'] == 'client' && isset($visit['specialist_photo']) && !empty($visit['specialist_photo'])): ?>
                            <div class="text-center mb-4">
                                <h5>Фото специалиста</h5>
                                <img src="uploads/specialists/<?= htmlspecialchars($visit['specialist_photo']) ?>"
                                     class="img-fluid rounded"
                                     alt="Фото <?= htmlspecialchars($visit['specialist_name']) ?>"
                                     style="max-height: 200px;">
                            </div>
                        <?php endif; ?>

                        <?php if ($_SESSION['user_type'] == 'client' && isset($visit['specialist_description']) && !empty($visit['specialist_description'])): ?>
                            <div class="mb-4">
                                <h5>О специалисте:</h5>
                                <p><?= nl2br(htmlspecialchars($visit['specialist_description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($_SESSION['user_type'] == 'specialist'): ?>
                            <form method="post" class="mb-4">
                                <div class="mb-3">
                                    <label for="visit_notes" class="form-label"><strong>Заметки о приеме:</strong></label>
                                    <textarea class="form-control" id="visit_notes" name="visit_notes" rows="4"><?= htmlspecialchars($visit['notes'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="save_notes" class="btn btn-success">Сохранить заметки</button>
                            </form>
                        <?php elseif (!empty($visit['notes'])): ?>
                            <div class="mb-4">
                                <h5>Заметки специалиста:</h5>
                                <p><?= nl2br(htmlspecialchars($visit['notes'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="post" onsubmit="return confirm('Вы уверены, что хотите отменить эту запись?');">
                            <div class="d-flex justify-content-between">
                                <a href="<?= $_SESSION['user_type'] == 'client' ? 'client_dashboard.php' : 'specialist_dashboard.php' ?>" class="btn btn-secondary">Назад</a>
                                <button type="submit" name="cancel_visit" class="btn btn-danger">Отменить запись</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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