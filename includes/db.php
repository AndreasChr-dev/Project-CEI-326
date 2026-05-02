<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=cei326_lab;charset=utf8mb4',
        'root',   
        'user@mysql',       
        [
            
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed.');
}