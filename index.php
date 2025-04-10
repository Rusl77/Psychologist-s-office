<?php
session_start();
require "dbconnect.php";
require "auth.php";
require "menu.php";


if((isset($_SESSION['msg']) && $_SESSION['msg']!='') or isset($msg)) {
    require "message.php";
    $_SESSION['msg']= '';
}


require "footer.php";
?>