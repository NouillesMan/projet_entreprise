<?php
$config = require __DIR__ . "/config.php";

$dsn = sprintf(
  "mysql:host=%s;dbname=%s;charset=utf8mb4",
  $config["host"],
  $config["dbname"]
);

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $config["user"], $config["pass"], $options);
