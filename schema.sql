-- Run this once in phpMyAdmin (or `mysql -u root < schema.sql`) to set up the database.

CREATE DATABASE IF NOT EXISTS it_support_log CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_support_log;

CREATE TABLE IF NOT EXISTS custom_columns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    engineer VARCHAR(50) NOT NULL,
    start_time DATETIME NOT NULL,
    user_name VARCHAR(150) DEFAULT '',
    department VARCHAR(150) DEFAULT '',
    source VARCHAR(50) DEFAULT '',
    priority VARCHAR(20) DEFAULT 'Medium',
    pc_name VARCHAR(100) DEFAULT '',
    pc_ip VARCHAR(50) DEFAULT '',
    status VARCHAR(20) DEFAULT 'Open',
    solved_time DATETIME NULL,
    problem TEXT,
    solution TEXT,
    solve_method VARCHAR(20) DEFAULT '',   -- Remote / Physical
    handover_to VARCHAR(150) DEFAULT NULL,
    handover_reason TEXT,
    handover_date DATE NULL,
    custom_data TEXT,               -- JSON string, e.g. {"Ticket No.":"T-102"}
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_start_time (start_time),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
);
