<?php
    $host = "localhost";
    $user = "root";
    $password = "" ;
    $databaseName = "ecommerce";
    

    $conn  =  mysqli_connect($host, $user, $password, $databaseName);
    
    if (!$conn) {
         die("Connection failed");
    }

?>