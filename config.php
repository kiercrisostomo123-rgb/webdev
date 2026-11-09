<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

define(
    "DB_PATH",
    __DIR__ . "/database/attendance.sqlite"
);

define(
    "UPLOAD_DIR",
    __DIR__ . "/uploads/"
);

define(
    "UPLOAD_URL",
    "uploads/"
);

// Ensure required directories exist
if (!is_dir(__DIR__ . "/database")) {
    mkdir(__DIR__ . "/database", 0777, true);
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}