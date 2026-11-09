<?php

require_once __DIR__ . "/config.php";

try {
    $db = new PDO("sqlite:" . DB_PATH, null, null, [
        PDO::ATTR_TIMEOUT => 10
    ]);

    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $db->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // SQLite disables foreign keys by default per connection; enable immediately
    $db->exec("PRAGMA foreign_keys = ON;");
    $db->exec("PRAGMA journal_mode = WAL;");

    /*
    |--------------------------------------------------------------------------
    | USERS TABLE
    |--------------------------------------------------------------------------
    */

    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    /*
    |--------------------------------------------------------------------------
    | STUDENTS TABLE
    |--------------------------------------------------------------------------
    */

    $db->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_number TEXT NOT NULL UNIQUE,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            year_section TEXT NOT NULL,
            picture TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE TABLE
    |--------------------------------------------------------------------------
    */

    $db->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            attendance_date TEXT NOT NULL,
            attendance_time TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id)
                REFERENCES students(id)
                ON DELETE CASCADE,
            UNIQUE (
                student_id,
                attendance_date
            )
        )
    ");

} catch (PDOException $e) {
    die(
        "Database connection failed: "
        . htmlspecialchars($e->getMessage())
    );
}