<?php
session_start();
require "dbconnect.php";

// Получение списка всех специалистов
try {
    $stmt = $conn->prepare("SELECT id, name, phone_number, description, photo FROM specialist ORDER BY name");
    $stmt->execute();
    $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Ошибка при получении списка специалистов: " . $e->getMessage();
}

// Получение списка категорий для фильтрации
try {
    $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_categories = "Ошибка при получении категорий: " . $e->getMessage();
}

// Фильтрация по категории, если выбрана
$filtered_specialists = $specialists;
if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $category_id = (int)$_GET['category'];

    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT s.id, s.name, s.phone_number, s.description, s.photo
            FROM specialist s
            JOIN visit v ON s.id = v.id_med
            WHERE v.category = :category_id
            ORDER BY s.name
        ");
        $stmt->bindValue(':category_id', $category_id);
        $stmt->execute();
        $filtered_specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_filter = "Ошибка при фильтрации специалистов: " . $e->getMessage();
    }
}

include "menu.php";
?>

    <div class="container mt-5 pt-5">
        <h1 class="text-center mb-4">Наши специалисты</h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Фильтр по категориям -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Фильтр по услугам</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-8">
                        <select class="form-select" name="category">
                            <option value="">Все услуги</option>
                            <?php if (!isset($error_categories) && !empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Применить фильтр</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список специалистов -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <?php if (isset($error_filter)): ?>
                <div class="col-12">
                    <div class="alert alert-danger"><?= $error_filter ?></div>
                </div>
            <?php endif; ?>

            <?php if (empty($filtered_specialists)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <p>По вашему запросу не найдено специалистов.</p>
                        <?php if (isset($_GET['category'])): ?>
                            <p><a href="specialists_list.php" class="alert-link">Сбросить фильтр</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_specialists as $specialist): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($specialist['photo']) && file_exists('uploads/specialists/' . $specialist['photo'])): ?>
                                <img src="uploads/specialists/<?= htmlspecialchars($specialist['photo']) ?>"
                                     class="card-img-top"
                                     alt="Фото <?= htmlspecialchars($specialist['name']) ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="assets/default_specialist.jpg"
                                     class="card-img-top"
                                     alt="Фото отсутствует"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($specialist['name']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">Психолог</h6>

                                <?php if (!empty($specialist['description'])): ?>
                                    <p class="card-text"><?= mb_substr(htmlspecialchars($specialist['description']), 0, 100) ?>...</p>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between mt-3">
                                    <a href="specialist_profile.php?id=<?= $specialist['id'] ?>" class="btn btn-outline-primary">Подробнее</a>
                                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'client'): ?>
                                        <a href="make_appointment.php?specialist_id=<?= $specialist['id'] ?>" class="btn btn-success">Записаться</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mb-4">
            <a href="<?= isset($_SESSION['user_type']) ? ($_SESSION['user_type'] == 'client' ? 'client_dashboard.php' : 'specialist_dashboard.php') : 'index.php' ?>" class="btn btn-secondary">Назад</a>
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