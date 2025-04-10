<?php
session_start();
require "dbconnect.php";

// Если пользователь уже авторизован, перенаправляем его на соответствующую страницу
if (isset($_SESSION['login'])) {
    header('Location: ' . ($_SESSION['user_type'] == 'specialist' ? '/specialist_dashboard.php' : '/client_dashboard.php'));
    exit();
}

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_type = $_POST['user_type'] ?? 'client';
    $birth = ($user_type == 'client') ? trim($_POST['birth'] ?? '') : null;

    // Валидация данных
    $errors = [];
    if (empty($login)) {
        $errors[] = "Логин не может быть пустым.";
    }
    if (empty($password)) {
        $errors[] = "Пароль не может быть пустым.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать не менее 6 символов.";
    }
    if (empty($name)) {
        $errors[] = "Имя не может быть пустым.";
    }
    if (empty($phone_number)) {
        $errors[] = "Телефон не может быть пустым.";
    }

    // Проверка на уникальность логина
    $table = ($user_type == 'specialist') ? 'specialist' : 'client';
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$table} WHERE login = :login");
        $stmt->bindValue(':login', $login);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Пользователь с таким логином уже существует. Пожалуйста, выберите другой логин.";
        }
    } catch (PDOException $e) {
        $errors[] = "Ошибка при проверке логина: " . $e->getMessage();
    }

    // Если нет ошибок, создаем пользователя
    if (empty($errors)) {
        try {
            // Формируем SQL запрос в зависимости от типа пользователя
            if ($user_type == 'client') {
                $sql = "INSERT INTO client (login, password, name, phone_number, Birth) VALUES (:login, :password, :name, :phone_number, :birth)";
            } else {
                $sql = "INSERT INTO specialist (login, password, name, phone_number) VALUES (:login, :password, :name, :phone_number)";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':login', $login);
            // В реальном приложении здесь должно быть хеширование пароля
            $stmt->bindValue(':password', $password);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':phone_number', $phone_number);

            if ($user_type == 'client') {
                $stmt->bindValue(':birth', $birth);
            }

            $stmt->execute();
            $user_id = $conn->lastInsertId();

            // Авторизуем пользователя
            $_SESSION['login'] = $login;
            $_SESSION['id'] = (int)$user_id;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['name'] = $name;
            $_SESSION['phone_number'] = $phone_number;
            $_SESSION['last_activity'] = time();
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

            if ($user_type == 'client' && !empty($birth)) {
                $_SESSION['birth'] = $birth;
            }

            $_SESSION['msg'] = "Вы успешно зарегистрированы и авторизованы!";
            header('Location: ' . ($user_type == 'specialist' ? '/specialist_dashboard.php' : '/client_dashboard.php'));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Ошибка при создании пользователя: " . $e->getMessage();
        }
    }
}

include "menu.php";
?>

    <div class="container mt-5 pt-5">
        <h1 class="text-center mb-4">Регистрация</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Создание аккаунта</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Тип пользователя</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="client_type" value="client" checked>
                                    <label class="form-check-label" for="client_type">
                                        Клиент
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="specialist_type" value="specialist" <?= (isset($_POST['user_type']) && $_POST['user_type'] == 'specialist') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="specialist_type">
                                        Специалист
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="login" class="form-label">Логин</label>
                                <input type="text" class="form-control" id="login" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <div class="form-text">Минимальная длина пароля: 6 символов.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Имя</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3" id="birth_field">
                                <label for="birth" class="form-label">Дата рождения</label>
                                <input type="date" class="form-control" id="birth" name="birth" value="<?= htmlspecialchars($_POST['birth'] ?? '') ?>">
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Назад</a>
                                <button type="submit" name="register" class="btn btn-primary">Зарегистрироваться</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Показывать/скрывать поле даты рождения в зависимости от типа пользователя
        document.addEventListener('DOMContentLoaded', function() {
            const clientType = document.getElementById('client_type');
            const specialistType = document.getElementById('specialist_type');
            const birthField = document.getElementById('birth_field');

            function updateBirthField() {
                if (clientType.checked) {
                    birthField.style.display = 'block';
                } else {
                    birthField.style.display = 'none';
                }
            }

            clientType.addEventListener('change', updateBirthField);
            specialistType.addEventListener('change', updateBirthField);

            // Инициализация при загрузке страницы
            updateBirthField();
        });
    </script>

<?php
// Отображение сообщений, если они есть
if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') {
    require "message.php";
    $_SESSION['msg'] = '';
}

require "footer.php";
?>