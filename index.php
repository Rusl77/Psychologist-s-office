Клиенты кабинета психолога:
<?php
require "dbconnect.php";
    $result = $conn->query("SELECT * from client");
    echo "<table border='1'>";
    while ($row = $result->fetch()) {
        echo '<tr><td>'.$row['id'].'</td><td>'.$row['name'].'</td><td>'.$row['Birth'].'</td></tr>';
    }
?>



