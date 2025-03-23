<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
$conn = new mysqli('127.0.0.1', 'mariadb', 'mariadb', 'mariadb');

if ($conn->connect_error) {

die("Connection failed: " .
$conn->connect_error);

}

?>