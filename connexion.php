<?php

    $user ="root";
    $host = "localhost";
    $dbname ="blog";
    $password ="";

    try{
        return $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }



?>