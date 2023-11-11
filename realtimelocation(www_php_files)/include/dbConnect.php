<?php
define('HOST','localhost');
    define('USER','root');
    define('PASS','');
    define('DB','realtimedb');

    class DBConnect {
    private $conn;

    // Connecting to database
    public function connect() {

    // Connecting to mysql database
    $this->conn = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect');

    // return database handler
    return $this->conn;
        }
    }