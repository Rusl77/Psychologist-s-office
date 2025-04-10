<?php
session_start();
require "dbconnect.php";

// Проверка авторизации
if (!isset($_SESSION['login']) || $_SESSION['user_type'] != 'client') {
    $_SESSION['msg'] = "Доступ запрещен. Пожалуйста, войдите как клиент.";
    header('Location: /index.php');
    exit();
}

// Получение ID специалиста из GET-параметра
$specialist_id = isset($_GET['specialist_id']) ? (int)$_GET['specialist_id'] : 0;

// Проверка наличия специалиста
if ($specialist_id <= 0) {
    $_SESSION['msg'] = "Ошибка: не указан ID специалиста.";
    header('Location: /client_dashboard.php');
    exit();
}

// Получение информации о специалисте
try {
    $stmt = $conn->prepare("SELECT id, name, photo, description FROM specialist WHERE id = :id");
    $stmt->bindValue(':id', $specialist_id);
    $stmt->execute();
    $specialist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$specialist) {
        $_SESSION['msg'] = "Ошибка: специалист не найден.";
        header('Location: /client_dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['msg'] = "Ошибка при получении данных о специалисте: " . $e->getMessage();
    header('Location: /client_dashboard.php');
    exit();
}

// Получение категорий услуг
try {
    $stmt = $conn->prepare("SELECT id, name, description FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_categories = "Ошибка при получении категорий: " . $e->getMessage();
}

// Получение доступных дат и времени для выбранного специалиста
// В реальном приложении здесь будет более сложная логика с учетом уже занятого времени
$available_dates = [];
$current_date = new DateTime();
for ($i = 0; $i < 14; $i++) {
    // Добавляем даты на две недели вперед, исключая выходные
    $date = clone $current_date;
    $date->modify("+$i days");
    if ($date->format('N') < 6) { // Исключаем субботу и воскресенье
        $available_dates[] = $date->format('Y-m-d');
    }
}

// Доступное время работы
$available_times = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00'];

// Проверка занятого времени
try {
    $stmt = $conn->prepare("SELECT day, time FROM visit WHERE id_med = :specialist_id");
    $stmt->bindValue(':specialist_id', $specialist_id);
    $stmt->execute();
    $booked_slots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error_slots = "Ошибка при проверке занятого времени: " . $e->getMessage();
}

// Обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $selected_date = $_POST['appointment_date'] ?? '';
    $selected_time = $_POST['appointment_time'] ?? '';
    $selected_category = $_POST['category_id'] ?? '';
    $client_id = $_SESSION['id'];

    // Валидация данных
    $errors = [];
    if (empty($selected_date) || !in_array($selected_date, $available_dates)) {
        $errors[] = "Пожалуйста, выберите действительную дату.";
    }
    if (empty($selected_time) || !in_array($selected_time, $available_times)) {
        $errors[] = "Пожалуйста, выберите действительное время.";
    }
    if (empty($selected_category)) {
        $errors[] = "Пожалуйста, выберите категорию.";
    }

    // Проверка, не занято ли уже это время
    $date_time_key = $selected_date;
    if (isset($booked_slots[$date_time_key]) && $booked_slots[$date_time_key] === $selected_time) {
        $errors[] = "Выбранное время уже занято. Пожалуйста, выберите другое время.";
    }

    // Если нет ошибок, сохраняем запись
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO visit (id_cli, id_med, day, time, category) 
                VALUES (:client_id, :specialist_id, :day, :time, :category)
            ");
            $stmt->bindValue(':client_id', $client_id);
            $stmt->bindValue(':specialist_id', $specialist_id);
            $stmt->bindValue(':day', $selected_date);
            $stmt->bindValue(':time', $selected_time);
            $stmt->bindValue(':category', $selected_category);
            $stmt->execute();

            $_SESSION['msg'] = "Вы успешно записались на прием!";
            header('Location: /client_dashboard.php');
            exit();
        } catch (PDOException $e) {
            $submission_error = "Ошибка при создании записи: " . $e->getMessage();
        }
    }
}

include "menu.php";
?>

    <div class="container mt-5 pt-5">
        <h1 class="text-center mb-4">Запись на прием</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Информация о специалисте</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($specialist['name']) ?></h5>

                        <?php if (!empty($specialist['photo'])): ?>
                            <div class="text-center mb-3">
                                <img src="uploads/specialists/<?= htmlspecialchars($specialist['photo']) ?>"
                                     class="img-fluid rounded"
                                     alt="Фото <?= htmlspecialchars($specialist['name']) ?>"
                                     style="max-height: 200px;">
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-3">
                                <img src="assets/default_specialist.jpg"
                                     class="img-fluid rounded"
                                     alt="Фото специалиста отсутствует"
                                     style="max-height: 200px;">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($specialist['description'])): ?>
                            <p class="card-text"><?= nl2br(htmlspecialchars($specialist['description'])) ?></p>
                        <?php else: ?>
                            <p class="card-text text-muted">Описание отсутствует</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Форма записи</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($submission_error)): ?>
                            <div class="alert alert-danger"><?= $submission_error ?></div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="category" class="form-label">Категория услуги</label>
                                <select class="form-select" id="category" name="category_id" required>
                                    <option value="">-- Выберите категорию --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Дата приема</label>
                                <select class="form-select" id="appointment_date" name="appointment_date" required>
                                    <option value="">-- Выберите дату --</option>
                                    <?php foreach ($available_dates as $date): ?>
                                        <option value="<?= $date ?>" <?= (isset($_POST['appointment_date']) && $_POST['appointment_date'] == $date) ? 'selected' : '' ?>>
                                            <?= (new DateTime($date))->format('d.m.Y') ?> (<?= (new DateTime($date))->format('l') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Время приема</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <option value="">-- Выберите время --</option>
                                    <?php foreach ($available_times as $time): ?>
                                        <option value="<?= $time ?>" <?= (isset($_POST['appointment_time']) && $_POST['appointment_time'] == $time) ? 'selected' : '' ?>>
                                            <?= $time ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="submit_appointment" class="btn btn-primary">Записаться на прием</button>
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