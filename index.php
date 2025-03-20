<?php
require "dbconnect.php";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Document</title>
    <style>
        body{
            max-width: 1100px;
        }
        *{
            font-family: Arial;
            margin: 0 auto;
        }
        .item{

            background-color: palegreen;
            margin: 20px;
            padding: 10px;
            border-radius: 10px;


        }
        .name{
            vertical-align: top;
            width: 20%;
            max-width: 250px;
            display: inline-block;
        }
        .props{
            vertical-align: top;
            width: 57%;
            display: inline-block;
        }
        .price{
            vertical-align: top;
            width: 10%;
            max-width: 150px;
            display: inline-block;
            font-weight: bold ;
            text-align: right;
            font-size: large;
            margin-right: 15px;
        }
        .image{
            width: 10%;
            display: inline-block;
        }
        .bin-button{
            align: right;
            padding: 10px;
            margin: 5px;
            border-radius: 8px;
            border-width: 1px;
            border-color: green;
            width: 120px;
        }

    </style>
</head>
<body>
<h1>Веб-приложение "Кабинет психолога"</h1>

<?php
echo "<h2>Причина обращения:</h2>";


$result = $conn->query("SELECT * FROM categories");

while ($row = $result->fetch()){
    ?>
    <div class="categories">

        <div class="name">
            <?=$row['id']?>
        </div>
        <div class="props">
            <?=$row['name']?>
        </div>

            <button class="bin-button"><i class=""></i>&nbsp&nbsp Перейти</button>
        </div>
    </div>
    <?php

}
?>


</body>
</html>