<?php
session_start();
require "dbconnect.php";

// Проверка авторизации и типа пользователя
if (!isset($_SESSION['login']) || $_SESSION['user_type'] != 'specialist') {
    $_SESSION['msg'] = "Доступ запрещен. Пожалуйста, войдите как специалист.";
    header('Location: /index.php');
    exit();
}

require "menu.php";

// Получение информации о приемах специалиста
$specialist_id = $_SESSION['id'];
$sql = "SELECT v.id, v.day, v.time, c.name as client_name, c.id as client_id, cat.name as category_name
        FROM visit v 
        JOIN client c ON v.id_cli = c.id 
        JOIN categories cat ON v.category = cat.id
        WHERE v.id_med = :specialist_id 
        ORDER BY v.day, v.time";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':specialist_id', $specialist_id);
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Ошибка при получении данных о приемах: " . $e->getMessage();
}
?>

    <div class="container mt-5 pt-5">
        <section>
            <h1 class="text-center mb-4">Панель управления специалиста</h1>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Информация о профиле</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Имя:</strong> <?= htmlspecialchars($_SESSION['name']) ?></p>
                            <p><strong>Телефон:</strong> <?= htmlspecialchars($_SESSION['phone_number']) ?></p>
                            <a href="profile.php" class="btn btn-outline-primary">Редактировать профиль</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Быстрые действия</h5>
                        </div>
                        <div class="card-body">
                            <a href="manage_visits.php" class="btn btn-outline-success mb-2 w-100">Управление расписанием</a>
                            <a href="manage_visits.php" class="btn btn-outline-info mb-2 w-100">Просмотр клиентов</a>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-center mb-3">Ваши предстоящие приемы</h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="list-group w-75 m-auto mb-4">
                <?php if (empty($visits)): ?>
                    <div class="alert alert-info" role="alert">
                        У вас пока нет запланированных приемов.
                    </div>
                <?php else: ?>
                    <?php foreach ($visits as $visit): ?>
                        <a href="visit.php?visitId=<?= $visit['id'] ?>" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
                            <div class="d-flex gap-2 w-100 justify-content-between">
                                <div>
                                    <h6 class="mb-0">Дата приема: <?= htmlspecialchars($visit['day']) ?></h6>
                                    <p class="mb-0 opacity-75">Время: <?= htmlspecialchars($visit['time']) ?></p>
                                    <p class="mb-0 opacity-75">Клиент: <?= htmlspecialchars($visit['client_name']) ?></p>
                                    <p class="mb-0 opacity-75">Категория: <?= htmlspecialchars($visit['category_name']) ?></p>
                                </div>
                                <span class="badge bg-primary rounded-pill align-self-center">Подробнее</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="text-center">
                <a href="manage_visits.php" class="btn btn-primary">Управление расписанием</a>
            </div>
        </section>
    </div>

<?php
// Отображение сообщений, если они есть
if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') {
    require "message.php";
    $_SESSION['msg'] = '';
}

require "footer.php";
?>