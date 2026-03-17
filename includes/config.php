<?php
return [
  "host"   => getenv("DB_HOST")   ?: "db",
  "dbname" => getenv("DB_NAME")   ?: "inventaire_pc",
  "user"   => getenv("DB_USER")   ?: "root",
  "pass"   => getenv("DB_PASS")   ?: "root",
];
