<?php


if (isset($_POST['login']) and $_POST['login']!='')
{
    try {
        $login = trim(htmlspecialchars($_POST['login']));
        $password = $_POST['password'];
        $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'client'; // По умолчанию клиент

        // Выбираем таблицу в зависимости от типа пользователя
        $table = ($user_type == 'specialist') ? 'specialist' : 'client';

        $sql = "SELECT * FROM {$table} WHERE login=:login";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':login', $login);
        $stmt->execute();


        if ($row=$stmt->fetch(PDO::FETCH_LAZY))
        {
            if ($_POST['password']!= $row['password']) $msg = "Неправильный пароль!";
            else {
                $_SESSION['login'] = $login;
                $_SESSION['id'] = (int)$row['id'];
                $_SESSION['user_type'] = $user_type; // Запоминаем тип пользователя
                $_SESSION['name'] = htmlspecialchars($row['name']);
                $_SESSION['phone_number'] = htmlspecialchars($row['phone_number']);
                $_SESSION['last_activity'] = time();
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

                if ($user_type == 'client') {
                    $_SESSION['birth'] = htmlspecialchars($row['Birth'] ?? '');
                }

                $msg = "Вы успешно вошли как " . ($user_type == 'specialist' ? 'специалист' : 'клиент') . "!";

                // Перенаправление на соответствующую страницу
                header('Location: ' . ($user_type == 'specialist' ? '/specialist_dashboard.php' : '/client_dashboard.php'));
                exit();
            }
        } else {
            $msg = "Пользователь не найден!";
        }

    } catch (PDOexception $error) {
        $msg = "Ошибка аутентификации: " . $error->getMessage();
    }


    $_SESSION['msg'] = $msg;
}

if (isset($_GET['logout']))
{
    $_SESSION = null;
    $_SESSION['msg'] =  "Вы успешно вышли из системы";
    header('Location: /');
    exit( );
}





?>