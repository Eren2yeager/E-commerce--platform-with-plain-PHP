<?php
    $host = "sql313.infinityfree.com";
    $user = "if0_41067095";
    $password = "dfAw4C4vuH" ;
    $databaseName = "if0_41067095_ecommerce";
    

    $conn  =  mysqli_connect($host, $user, $password, $databaseName);
    
    if (!$conn) {
         die("Connection failed");
    }

?>