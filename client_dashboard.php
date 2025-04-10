<?php
session_start();
require "dbconnect.php";

// Проверка авторизации и типа пользователя
if (!isset($_SESSION['login']) || $_SESSION['user_type'] != 'client') {
    $_SESSION['msg'] = "Доступ запрещен. Пожалуйста, войдите как клиент.";
    header('Location: /index.php');
    exit();
}

// Подключаем только навигационное меню без изображения
?>
    <html lang="ru">
    <head>
        <link rel="icon" href="assets/logo.jpg" type="image/jpg">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <title>Кабинет психолога - Личный кабинет клиента</title>
        <style>
            .navbar {
                padding-top: 10px;
                padding-bottom: 10px;
            }
            .login-form-container {
                padding: 10px;
                margin-top: 10px;
                border-radius: 5px;
            }
            .user-type-select {
                min-width: 150px;
            }
            body {
                padding-top: 80px;
            }
        </style>
    </head>
<body>

<?php
// Подключаем только навигационное меню
ob_start();
include "menu.php";
$menu_content = ob_get_clean();

// Удаляем тег <img> из загруженного контента
$menu_content = preg_replace('/<img[^>]+src="assets\/doc.png"[^>]*>/', '', $menu_content);

// Выводим обработанное меню
echo $menu_content;

// Получение информации о записях клиента
$client_id = $_SESSION['id'];
$sql = "SELECT v.id, v.day, v.time, s.name as specialist_name, s.id as specialist_id, cat.name as category_name
        FROM visit v 
        JOIN specialist s ON v.id_med = s.id 
        JOIN categories cat ON v.category = cat.id
        WHERE v.id_cli = :client_id 
        ORDER BY v.day, v.time";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':client_id', $client_id);
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Ошибка при получении данных о записях: " . $e->getMessage();
}

// Получение списка доступных специалистов
$sql_specialists = "SELECT id, name FROM specialist ORDER BY name";

try {
    $stmt_specialists = $conn->prepare($sql_specialists);
    $stmt_specialists->execute();
    $specialists = $stmt_specialists->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_specialists = "Ошибка при получении данных о специалистах: " . $e->getMessage();
}
?>

    <div class="container mt-5 pt-5">
        <section>
            <h1 class="text-center mb-4">Личный кабинет клиента</h1>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Профиль клиента</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Имя:</strong> <?= htmlspecialchars($_SESSION['name']) ?></p>
                            <p><strong>Телефон:</strong> <?= htmlspecialchars($_SESSION['phone_number']) ?></p>
                            <?php if (isset($_SESSION['birth']) && $_SESSION['birth']): ?>
                                <p><strong>Дата рождения:</strong> <?= htmlspecialchars($_SESSION['birth']) ?></p>
                            <?php endif; ?>
                            <a href="profile.php" class="btn btn-outline-primary">Редактировать профиль</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Быстрая запись</h5>
                        </div>
                        <div class="card-body">
                            <p>Запишитесь на прием к специалисту прямо сейчас!</p>
                            <a href="specialists_list.php" class="btn btn-success w-100">Выбрать специалиста</a>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-center mb-3">Ваши предстоящие записи</h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="list-group w-75 m-auto mb-4">
                <?php if (empty($visits)): ?>
                    <div class="alert alert-info" role="alert">
                        У вас пока нет запланированных записей. Воспользуйтесь формой выше, чтобы записаться на прием.
                    </div>
                <?php else: ?>
                    <?php foreach ($visits as $visit): ?>
                        <a href="visit.php?visitId=<?= $visit['id'] ?>" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
                            <div class="d-flex gap-2 w-100 justify-content-between">
                                <div>
                                    <h6 class="mb-0">Дата записи: <?= htmlspecialchars($visit['day']) ?></h6>
                                    <p class="mb-0 opacity-75">Время: <?= htmlspecialchars($visit['time']) ?></p>
                                    <p class="mb-0 opacity-75">Специалист: <?= htmlspecialchars($visit['specialist_name']) ?></p>
                                    <p class="mb-0 opacity-75">Категория: <?= htmlspecialchars($visit['category_name']) ?></p>
                                </div>
                                <span class="badge bg-primary rounded-pill align-self-center">Подробнее</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="text-center mb-3">Доступные специалисты</h2>

            <?php if (isset($error_specialists)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_specialists ?>
                </div>
            <?php endif; ?>

            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <?php if (!empty($specialists)): ?>
                    <?php foreach ($specialists as $specialist): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($specialist['name']) ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">Психолог</h6>
                                    <a href="specialist_profile.php?id=<?= $specialist['id'] ?>" class="btn btn-outline-primary">Подробнее</a>
                                    <a href="make_appointment.php?specialist_id=<?= $specialist['id'] ?>" class="btn btn-success">Записаться</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info" role="alert">
                            На данный момент специалисты не найдены.
                        </div>
                    </div>
                <?php endif; ?>
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