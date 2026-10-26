<?php
/**
 * Cielo Cafe and Restro - Core Configuration & Data Loader
 * Production-ready lightweight PHP backend
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Set Indian Standard Time for accurate operating hours calculation
date_default_timezone_set('Asia/Kolkata');

// Load Data Sources directly from Database with high-performance Cache
$business = \App\Services\AppDataService::getBusiness();
$menu     = \App\Services\AppDataService::getMenu();
$gallery  = \App\Services\AppDataService::getGallery();
$reviews  = \App\Services\AppDataService::getReviews();

// Dynamic CMS quick-access helpers
$cmsHeroName    = (string)(\App\Models\Setting::get('cms_hero_name') ?: 'RiTiVerse');
$cmsHeroTagline = (string)(\App\Models\Setting::get('cms_hero_tagline') ?: 'Heritage Cafe & Cultural Space');
$cmsHeroBadge   = (string)(\App\Models\Setting::get('cms_hero_badge') ?: 'Vadodara, Gujarat');
$cmsHeroCta     = (string)(\App\Models\Setting::get('cms_hero_cta') ?: 'Explore Our Menu');

// Helper to escape HTML safely
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper to resolve asset URLs safely
function asset_url(?string $path): string {
    if (!$path) return '';
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'assets/')) {
        return $cleanPath;
    }
    return 'assets/' . $cleanPath;
}

// Backward-compatible alias
function asset(string $path): string {
    return asset_url($path);
}

// Live calculation of Cafe Open/Closed status (11:00 AM to 10:30 PM)
function isRiTiVerseOpen(): array {
    $currentHour = (float)date('H') + ((float)date('i') / 60);
    // 11:00 AM (11.0) to 10:30 PM (22.5)
    $isOpen = ($currentHour >= 11.0 && $currentHour <= 22.5);
    return [
        'isOpen' => $isOpen,
        'statusText' => $isOpen ? 'Open Now' : 'Closed Now',
        'badgeClass' => $isOpen ? 'badge-open' : 'badge-closed',
        'timeFormatted' => date('h:i A')
    ];
}

// Backward-compatible aliases
function isGazraOpen(): array {
    return isRiTiVerseOpen();
}

function isCieloOpen(): array {
    return isRiTiVerseOpen();
}
