<?php

declare(strict_types=1);

/**
 * RiTiVerse Cafe - MySQL Full Database Exporter
 * Generates production-ready, phpMyAdmin-compatible .sql file
 * with full DDL schemas and current database contents.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$pdo = Database::getInstance()->getPdo();

$sqlOutput = [];

$sqlOutput[] = "-- ==============================================================================";
$sqlOutput[] = "-- RiTiVerse Cafe & Cultural Space - Complete MySQL Database Dump";
$sqlOutput[] = "-- Generated on: " . date('Y-m-d H:i:s T');
$sqlOutput[] = "-- Target: MySQL 5.7+ / 8.0+ / MariaDB 10.3+ / phpMyAdmin / cPanel";
$sqlOutput[] = "-- Character Set: utf8mb4 / utf8mb4_unicode_ci";
$sqlOutput[] = "-- ==============================================================================\n";

$sqlOutput[] = "SET NAMES utf8mb4;";
$sqlOutput[] = "SET FOREIGN_KEY_CHECKS = 0;";
$sqlOutput[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
$sqlOutput[] = "SET AUTOCOMMIT = 0;";
$sqlOutput[] = "START TRANSACTION;";
$sqlOutput[] = "SET time_zone = '+05:30';\n";

// Load DDL schema from schema.sql
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    throw new RuntimeException("schema.sql not found at {$schemaFile}");
}

$schemaContent = file_get_contents($schemaFile);
$sqlOutput[] = "-- -----------------------------------------------------------------------------";
$sqlOutput[] = "-- 1. Table Structures (DDL)";
$sqlOutput[] = "-- -----------------------------------------------------------------------------\n";
$sqlOutput[] = $schemaContent;
$sqlOutput[] = "\n-- -----------------------------------------------------------------------------";
$sqlOutput[] = "-- 2. Table Data Inserts (Current Live Data)";
$sqlOutput[] = "-- -----------------------------------------------------------------------------\n";

// Tables in order of foreign key dependencies
$tables = [
    'settings',
    'staff_users',
    'users',
    'tables',
    'menu_categories',
    'menu_items',
    'item_variants',
    'item_addons',
    'orders',
    'order_items',
    'payments',
    'coupons',
    'reward_rules',
    'points_ledger',
    'user_rewards',
    'cart_abandonment_logs',
    'reviews',
    'review_photos',
    'offers',
    'audit_log',
    'otp_verifications',
    'gallery_items'
];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        continue;
    }

    if (empty($rows)) {
        continue;
    }

    $sqlOutput[] = "--";
    $sqlOutput[] = "-- Dumping data for table `{$table}` (" . count($rows) . " rows)";
    $sqlOutput[] = "--";

    $cols = array_keys($rows[0]);
    $quotedCols = array_map(fn($c) => "`{$c}`", $cols);
    $colList = implode(', ', $quotedCols);

    // Group inserts into batches of 50
    $batches = array_chunk($rows, 50);

    foreach ($batches as $batch) {
        $valueRows = [];
        foreach ($batch as $row) {
            $valStrings = [];
            foreach ($cols as $col) {
                $val = $row[$col];
                if ($val === null) {
                    $valStrings[] = "NULL";
                } elseif (is_int($val) || is_float($val)) {
                    $valStrings[] = (string)$val;
                } elseif (is_numeric($val) && (string)(int)$val === $val && !str_starts_with($val, '0')) {
                    $valStrings[] = (string)$val;
                } else {
                    // Escape string for SQL
                    $escaped = str_replace(
                        ["\\", "\x00", "\n", "\r", "'", "\x1a"],
                        ["\\\\", "\\0", "\\n", "\\r", "\'", "\\Z"],
                        (string)$val
                    );
                    $valStrings[] = "'{$escaped}'";
                }
            }
            $valueRows[] = "(" . implode(', ', $valStrings) . ")";
        }

        $sqlOutput[] = "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valueRows) . ";\n";
    }
}

$sqlOutput[] = "COMMIT;";
$sqlOutput[] = "SET FOREIGN_KEY_CHECKS = 1;\n";
$sqlOutput[] = "-- Dump complete\n";

$fullSql = implode("\n", $sqlOutput);
$targetFile = __DIR__ . '/ritiverse.sql';

file_put_contents($targetFile, $fullSql);

echo "Successfully exported " . strlen($fullSql) . " bytes to: {$targetFile}\n";
