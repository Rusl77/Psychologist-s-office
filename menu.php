<html lang="ru">
<head>
    <link rel="icon" href="assets/logo.jpg" type="image/jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Кабинет психолога</title>
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

<img src="assets/doc.png">

<header class="container-fluid">
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Кабинет психолога</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Главная</a>
                    </li>

                    <?php
                    // Разделение меню в зависимости от типа пользователя
                    if (isset($_SESSION['user_type'])) {
                        if ($_SESSION['user_type'] == 'specialist') {
                            // Пункты меню для специалиста
                            echo '<li class="nav-item">
                                    <a class="nav-link" href="manage_visits.php">Мои клиенты</a>
                                  </li>
                                  <li class="nav-item">
                                    <a class="nav-link" href="manage_visits.php">Расписание</a>
                                  </li>';
                        } else {
                            // Пункты меню для клиента
                            echo '<li class="nav-item">
                                    <a class="nav-link" href="specialists_list.php">Специалисты</a>
                                  </li>
                                  <li class="nav-item">
                                    <a class="nav-link" href="client_dashboard.php">Мои записи</a>
                                  </li>';
                        }
                    } else {
                        // Пункты меню для неавторизованных пользователей
                        echo '<li class="nav-item">
                                <a class="nav-link" href="specialists_list.php">Специалисты</a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link" href="index.php">Услуги</a>
                              </li>';
                    }
                    ?>
                </ul>

                <?php
                if (!isset($_SESSION['login'])) {
                    // Форма входа для неавторизованных пользователей с более заметным выбором типа
                    ?>
                    <div class="login-form-container bg-dark">
                        <form class="d-flex flex-wrap" method="post">
                            <div class="mb-2 me-2">
                                <label for="user_type_select" class="form-label text-light">Тип пользователя:</label>
                                <select class="form-select user-type-select" id="user_type_select" name="user_type">
                                    <option value="client">Клиент</option>
                                    <option value="specialist">Специалист</option>
                                </select>
                            </div>
                            <div class="mb-2 me-2">
                                <label for="login_input" class="form-label text-light">Логин:</label>
                                <input class="form-control" id="login_input" type="text" placeholder="Логин" name="login" aria-label="Логин" required/>
                            </div>
                            <div class="mb-2 me-2">
                                <label for="password_input" class="form-label text-light">Пароль:</label>
                                <input class="form-control" id="password_input" type="password" placeholder="Пароль" name="password" aria-label="Пароль" required/>
                            </div>
                            <div class="d-flex align-items-end mb-2">
                                <button class="btn btn-success me-1" type="submit">Войти</button>
                                <a class="btn btn-outline-primary ms-1" href="register.php">Регистрация</a>
                            </div>
                        </form>
                    </div>
                    <?php
                } else {
                    // Информация для авторизованных пользователей
                    $user_type_text = ($_SESSION['user_type'] == 'specialist') ? 'Специалист' : 'Клиент';
                    $dashboard_url = ($_SESSION['user_type'] == 'specialist') ? 'specialist_dashboard.php' : 'client_dashboard.php';
                    echo '<div class="d-flex align-items-center">
                            <span class="text-light me-2"><strong>' . $user_type_text . ':</strong> ' . htmlspecialchars($_SESSION['name']) . '</span>
                            <a class="btn btn-outline-primary me-2" href="' . $dashboard_url . '">Личный кабинет</a>
                            <a class="btn btn-outline-danger" href="index.php?logout=1">Выйти</a>
                          </div>';
                }
                ?>
            </div>
        </div>
    </nav>
</header>