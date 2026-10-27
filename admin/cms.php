<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Luxury Dynamic Website CMS Studio with Left Sidebar
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Offer;
use App\Core\Security;

// Require owner or admin role
$staff = AuthService::requireRole(['owner', 'admin'], '/admin/login.php');

$msg = '';
$err = '';

// Handle Settings Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security session expired. Please refresh and try again.';
    } else {
        $posted = $_POST['settings'] ?? [];
        $postedPages = $_POST['pages'] ?? [];

        foreach ($posted as $key => $val) {
            $key = trim((string)$key);
            $val = is_string($val) ? trim($val) : $val;

            // Sanitize GSC unique verification code if user pasted full HTML tag or quotes
            if ($key === 'seo_google_site_verification' && is_string($val)) {
                if (preg_match('/content=["\']([^"\']+)["\']/i', $val, $m)) {
                    $val = $m[1];
                }
                $val = trim(strip_tags(str_replace(['"', "'"], '', $val)));
            }
            // Sanitize Bing unique verification code if user pasted full HTML tag or quotes
            if ($key === 'seo_bing_site_verification' && is_string($val)) {
                if (preg_match('/content=["\']([^"\']+)["\']/i', $val, $m)) {
                    $val = $m[1];
                }
                $val = trim(strip_tags(str_replace(['"', "'"], '', $val)));
            }
            // Sanitize Meta Pixel if user pasted script
            if ($key === 'seo_meta_pixel_id' && is_string($val) && preg_match('/[0-9]{8,20}/', $val, $m)) {
                $val = $m[0];
            }

            // Determine type casting
            $type = 'string';
            if (in_array($key, ['gst_enabled', 'announcement_enabled', 'loyalty_enabled', 'payment_counter_cash', 'payment_counter_upi', 'payment_online_razorpay', 'reviews_auto_approve_positive'], true)) {
                $type = 'bool';
                $val = ($val === '1' || $val === 'true' || $val === 1);
            } elseif (in_array($key, ['gst_cgst_percent', 'gst_sgst_percent', 'loyalty_max_redeem_percent'], true)) {
                $type = 'float';
                $val = (float)$val;
            } elseif (in_array($key, ['loyalty_points_per_100', 'loyalty_point_value_paise', 'loyalty_min_redeem_points'], true)) {
                $type = 'int';
                $val = (int)$val;
            } elseif ($key === 'promo_stacking_mode') {
                $type = 'string';
                $val = in_array($val, ['stackable', 'exclusive'], true) ? $val : 'stackable';
            }

            $group = str_starts_with($key, 'seo_') ? 'seo' : 'system';
            Setting::set($key, $val, $group, $type);
        }

        // Save Per-Page SEO data (JSON array)
        if (!empty($postedPages) && is_array($postedPages)) {
            $cleanPages = [];
            foreach ($postedPages as $pageKey => $pData) {
                if (!is_array($pData)) continue;
                $cleanPages[$pageKey] = [
                    'title' => trim((string)($pData['title'] ?? '')),
                    'description' => trim((string)($pData['description'] ?? '')),
                    'keywords' => trim((string)($pData['keywords'] ?? '')),
                    'og_image' => trim((string)($pData['og_image'] ?? '')),
                    'robots' => trim((string)($pData['robots'] ?? 'index, follow')),
                    'path' => trim((string)($pData['path'] ?? '')),
                ];
            }
            Setting::set('seo_pages_data', json_encode($cleanPages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'seo', 'json');
        }

        \App\Services\AppDataService::clearCache();
        AuditLog::log($staff['id'], 'cms_settings_updated', 'settings', null, ['keys_count' => count($posted), 'pages_count' => count($postedPages)]);
        $msg = 'Website CMS content & system configurations saved successfully! Changes reflect on the website immediately.';
    }
}

// Read current settings
$allSettings = Setting::all();
$current = [];
foreach ($allSettings as $s) {
    $current[$s['key']] = Setting::castValue($s['value'], $s['type']);
}

// Read active coupons and automated promotional offers
$couponsList = Coupon::all('id ASC');
$offersList = Offer::all('priority ASC, id ASC');

// Read full SEO & Tracking configurations
$allSeo = \App\Services\AppDataService::getAllSeoConfig();
$seoPages = $allSeo['pages'] ?? [];

$csrfToken = Security::generateCsrfToken();
$activeNav = 'cms';

// Release PHP session lock immediately so concurrent tabs or asset requests never hang
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website CMS & Dynamic Studio | Cielo Cafe Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-surface: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-terracotta-soft: rgba(201, 106, 82, 0.1);
      --accent-sky: #6B9AC4;
      --accent-sky-soft: rgba(107, 154, 196, 0.12);
      --accent-sky-deep: #4A7A9F;
      --border-warm: rgba(26, 20, 18, 0.08);
      --shadow-card: 0 4px 18px rgba(26, 20, 18, 0.04);
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 20px;
    }

    * { box-sizing: border-box; }

    html, body {
      max-width: 100vw;
      overflow-x: clip;
    }

    body {
      margin: 0;
      padding: 0;
      background: #F4F1EA;
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* Left Sidebar Styles */
    .admin-sidebar {
      width: 260px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #1A1412;
      color: #F7F4EE;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid rgba(247, 244, 238, 0.08);
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
    }

    .sidebar-header {
      padding: 1.75rem 1.5rem 1.25rem;
      border-bottom: 1px solid rgba(247, 244, 238, 0.06);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      color: #F7F4EE;
    }

    .sidebar-logo-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .sidebar-title {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      display: block;
      line-height: 1;
    }

    .sidebar-sub {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-top: 0.25rem;
      display: block;
    }

    .sidebar-nav-container {
      padding: 1.5rem 1rem;
      flex: 1;
      overflow-y: auto;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: rgba(247, 244, 238, 0.4);
      font-weight: 700;
      padding: 0 0.75rem;
      margin-bottom: 0.6rem;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 0.85rem;
      border-radius: 12px;
      text-decoration: none;
      color: rgba(247, 244, 238, 0.75);
      font-size: 0.88rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .sidebar-link:hover {
      color: #F7F4EE;
      background: rgba(247, 244, 238, 0.08);
    }

    .sidebar-link.active {
      color: #FFFFFF;
      background: var(--accent-terracotta);
      box-shadow: 0 4px 15px rgba(201, 106, 82, 0.35);
    }

    .sidebar-link-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .sidebar-badge-pulse {
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 999px;
    }

    .sidebar-link.active .sidebar-badge-pulse {
      background: #FFFFFF;
      color: var(--accent-terracotta);
    }

    .sidebar-link-external {
      font-size: 0.85rem;
      opacity: 0.5;
    }

    .sidebar-footer {
      padding: 1.25rem;
      border-top: 1px solid rgba(247, 244, 238, 0.06);
      background: rgba(0, 0, 0, 0.25);
    }

    .staff-card {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .staff-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-weight: 700;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .staff-info {
      display: flex;
      flex-direction: column;
    }

    .staff-name {
      font-size: 0.86rem;
      font-weight: 700;
      color: #F7F4EE;
      line-height: 1.2;
    }

    .staff-role-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.72rem;
      color: rgba(247, 244, 238, 0.6);
      margin-top: 2px;
    }

    .role-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #2D6A4F;
      box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.4);
    }

    .btn-sidebar-signout {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.65rem;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      color: #EFA694;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn-sidebar-signout:hover {
      background: var(--accent-terracotta);
      color: #FFFFFF;
    }

    /* Right Content Area */
    .admin-main-wrapper {
      margin-left: 260px;
      flex: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      width: calc(100% - 260px);
      max-width: calc(100% - 260px);
      min-width: 0;
      box-sizing: border-box;
    }

    /* Top Action Bar */
    .admin-topbar {
      background: #FFFFFF;
      border-bottom: 1px solid var(--border-warm);
      padding: 1.15rem 2.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 900;
      box-shadow: 0 2px 10px rgba(26, 20, 18, 0.02);
      gap: 1rem;
      flex-wrap: wrap;
    }

    .topbar-title-wrap {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .topbar-heading {
      font-family: 'Fraunces', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.1;
    }

    .topbar-subtext {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .topbar-right-tools {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .btn-refresh-pill {
      text-decoration: none;
      background: var(--bg-cream);
      border: 1px solid var(--border-warm);
      color: var(--text-espresso);
      padding: 0.55rem 1.15rem;
      border-radius: 999px;
      font-size: 0.82rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: all 0.2s ease;
    }

    .btn-refresh-pill:hover {
      background: #FFFFFF;
      border-color: var(--accent-sky);
      color: var(--accent-sky-deep);
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    /* Content Area */
    .cms-content-area {
      padding: 2.25rem 2.5rem 6rem;
      flex: 1;
      min-width: 0;
      box-sizing: border-box;
    }

    .toast-alert {
      background: #FFFFFF;
      border-left: 4px solid var(--accent-sky);
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow-card);
      font-size: 0.92rem;
      font-weight: 500;
    }

    .toast-alert.error {
      border-left-color: var(--accent-terracotta);
      color: var(--accent-terracotta);
    }

    /* Studio Layout */
    .studio-layout {
      max-width: 1060px;
      margin: 0 auto;
      width: 100%;
    }

    .form-sections {
      width: 100%;
    }

    /* Form Section Cards */
    .cms-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      padding: 2rem;
      margin-bottom: 2rem;
      transition: all 0.2s ease;
      min-width: 0;
      box-sizing: border-box;
    }

    .cms-card:hover {
      border-color: rgba(107, 154, 196, 0.3);
    }

    .section-header {
      border-bottom: 1px solid var(--border-warm);
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .section-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: var(--bg-cream);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .section-title {
      font-family: 'Fraunces', serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
    }

    .section-desc {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin: 2px 0 0;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1.25rem;
      min-width: 0;
    }

    .form-grid.single-col {
      grid-template-columns: minmax(0, 1fr);
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      min-width: 0;
    }

    .form-group label {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-espresso);
      word-break: break-word;
    }

    .form-group input, .form-group textarea, .form-group select {
      padding: 0.8rem 1rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
      color: var(--text-espresso);
      background: #FAFAFA;
      outline: none;
      transition: all 0.2s ease;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }

    .form-group select {
      text-overflow: ellipsis;
    }

    .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
      border-color: var(--accent-sky);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(107, 154, 196, 0.12);
    }

    .help-hint {
      font-size: 0.74rem;
      color: var(--text-muted);
      line-height: 1.4;
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    .help-hint code {
      word-break: break-all;
      overflow-wrap: anywhere;
      white-space: normal;
      background: rgba(26, 20, 18, 0.05);
      padding: 2px 5px;
      border-radius: 4px;
    }

    /* Floating Save Bar */
    .floating-save-bar {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      z-index: 100;
    }

    .btn-save-primary {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      padding: 1rem 2rem;
      border-radius: 999px;
      font-size: 0.95rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      box-shadow: 0 12px 30px rgba(26, 20, 18, 0.35);
      transition: all 0.2s ease;
    }

    .btn-save-primary:hover {
      background: #2C221F;
      transform: translateY(-2px);
      box-shadow: 0 16px 36px rgba(26, 20, 18, 0.45);
    }

    @media (max-width: 900px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }
      .admin-sidebar.open {
        transform: translateX(0);
      }
      .admin-main-wrapper {
        margin-left: 0;
        width: 100%;
        max-width: 100%;
      }
      .cms-content-area {
        padding: 1.25rem 1rem 6rem;
      }
      .admin-topbar {
        padding: 1rem 1.25rem;
      }
      .topbar-heading {
        font-size: 1.35rem;
      }
      .form-grid {
        grid-template-columns: minmax(0, 1fr) !important;
      }
      .cms-card {
        padding: 1.25rem;
      }
      .floating-save-bar {
        bottom: 1rem;
        right: 1rem;
      }
      .btn-save-primary {
        padding: 0.85rem 1.5rem;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>

  <!-- Left Sidebar Navigation Component -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Right Content Wrapper -->
  <div class="admin-main-wrapper">

    <!-- Top Action Bar -->
    <header class="admin-topbar">
      <div class="topbar-title-wrap">
        <h1 class="topbar-heading">Website CMS Studio</h1>
        <span class="topbar-subtext">Dynamic Website Copy, GST Tax Rates & Loyalty Engine Configuration</span>
      </div>

      <div class="topbar-right-tools">
        <a href="/index.php" target="_blank" class="btn-refresh-pill" style="text-decoration:none;">
          <span>Preview Public Website ↗</span>
        </a>
      </div>
    </header>

    <!-- Content Body -->
    <main class="cms-content-area">

      <!-- Alert Notifications -->
      <?php if ($msg !== ''): ?>
        <div class="toast-alert">
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e($msg) ?></span>
          </div>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted);">&times;</button>
        </div>
      <?php elseif ($err !== ''): ?>
        <div class="toast-alert error">
          <span><?= e($err) ?></span>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--accent-terracotta);">&times;</button>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/cms.php" id="cmsForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="studio-layout">

          <!-- Left Form Sections -->
          <div class="form-sections">

            <!-- 1. Hero & Branding -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Hero Section & Branding</h2>
                  <div class="section-desc">Grand editorial entrance text displayed at the top of the homepage.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Brand Main Title</label>
                  <input type="text" id="input_hero_name" name="settings[cms_hero_name]" value="<?= e((string)($current['cms_hero_name'] ?? 'RiTiVerse')) ?>">
                  <span class="help-hint">Primary Fraunces serif display heading.</span>
                </div>

                <div class="form-group">
                  <label>Brand Tagline Subtitle</label>
                  <input type="text" id="input_hero_tagline" name="settings[cms_hero_tagline]" value="<?= e((string)($current['cms_hero_tagline'] ?? 'Heritage Cafe & Cultural Space')) ?>">
                  <span class="help-hint">Italic second line underneath brand name.</span>
                </div>

                <div class="form-group">
                  <label>Top Accent Location Badge</label>
                  <input type="text" id="input_hero_badge" name="settings[cms_hero_badge]" value="<?= e((string)($current['cms_hero_badge'] ?? 'Vadodara, Gujarat')) ?>">
                </div>

                <div class="form-group">
                  <label>Hero CTA Button Label</label>
                  <input type="text" id="input_hero_cta" name="settings[cms_hero_cta]" value="<?= e((string)($current['cms_hero_cta'] ?? 'Explore Our Menu')) ?>">
                </div>
              </div>
            </div>

            <!-- 2. Story & Heritage -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Story & Heritage</h2>
                  <div class="section-desc">The narrative, quote, and culinary philosophy.</div>
                </div>
              </div>

              <div class="form-grid single-col">
                <div class="form-group">
                  <label>Story Main Heading</label>
                  <input type="text" name="settings[cms_story_heading]" value="<?= e((string)($current['cms_story_heading'] ?? 'A Heritage Sanctuary Born from Dignity, Inclusion & Authentic Taste')) ?>">
                </div>

                <div class="form-group">
                  <label>Editorial Pull Quote</label>
                  <textarea id="input_story_quote" name="settings[cms_story_quote]" rows="2"><?= e((string)($current['cms_story_quote'] ?? 'Where warm hospitality meets authentic taste in Vadodara, Gujarat.')) ?></textarea>
                </div>
              </div>
            </div>

            <!-- 3. Contact & Hours -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#2D6A4F;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Contact, Location & Operating Hours</h2>
                  <div class="section-desc">Phone, WhatsApp hotline, address, and daily opening timings.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Public Phone Hotline</label>
                  <input type="text" name="settings[cafe_phone]" value="<?= e((string)($current['cafe_phone'] ?? '+91 82003 06871')) ?>">
                </div>

                <div class="form-group">
                  <label>WhatsApp Number (Digits only)</label>
                  <input type="text" name="settings[cafe_whatsapp]" value="<?= e((string)($current['cafe_whatsapp'] ?? '918200306871')) ?>">
                </div>

                <div class="form-group">
                  <label>Contact Email</label>
                  <input type="email" name="settings[cafe_email]" value="<?= e((string)($current['cafe_email'] ?? 'hello@RiTiVersecafe.in')) ?>">
                </div>

                <div class="form-group">
                  <label>Operating Hours Text</label>
                  <input type="text" name="settings[cafe_hours]" value="<?= e((string)($current['cafe_hours'] ?? '11:00 AM - 10:30 PM')) ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label>Cafe Physical Address (Formatted)</label>
                  <input type="text" name="settings[cafe_address]" value="<?= e((string)($current['cafe_address'] ?? 'Vadodara, Gujarat')) ?>">
                </div>
              </div>
            </div>

            <!-- 4. GST & Taxes -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#C08A3E;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div>
                  <h2 class="section-title">GST & Financial Rules</h2>
                  <div class="section-desc">Government tax breakdown automatically applied to all digital orders and thermal bills.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>CGST Rate (%)</label>
                  <input type="number" step="0.1" name="settings[gst_cgst_percent]" value="<?= e((string)($current['gst_cgst_percent'] ?? 2.5)) ?>">
                  <span class="help-hint">Standard restaurant CGST is 2.5%.</span>
                </div>

                <div class="form-group">
                  <label>SGST Rate (%)</label>
                  <input type="number" step="0.1" name="settings[gst_sgst_percent]" value="<?= e((string)($current['gst_sgst_percent'] ?? 2.5)) ?>">
                  <span class="help-hint">Standard restaurant SGST is 2.5%.</span>
                </div>

                <div class="form-group">
                  <label>Cafe GSTIN Number</label>
                  <input type="text" name="settings[cafe_gstin]" value="<?= e((string)($current['cafe_gstin'] ?? '24ABCDE1234F1Z5')) ?>">
                </div>

                <div class="form-group">
                  <label>GST Calculation Status</label>
                  <select name="settings[gst_enabled]">
                    <option value="1" <?= (!empty($current['gst_enabled'])) ? 'selected' : '' ?>>Enabled (Tax is added to subtotal)</option>
                    <option value="0" <?= (empty($current['gst_enabled'])) ? 'selected' : '' ?>>Disabled (Inclusive / Zero Tax)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- 5. Loyalty Points System -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-terracotta);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Loyalty Points & Rewards Engine</h2>
                  <div class="section-desc">Points accrual and redemption limits credited directly to customer double-entry ledgers.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Points Earned per ₹100 Spent</label>
                  <input type="number" name="settings[loyalty_points_per_100]" value="<?= e((string)($current['loyalty_points_per_100'] ?? 10)) ?>">
                  <span class="help-hint">e.g. 10 points per ₹100 = 10% effective earning rate.</span>
                </div>

                <div class="form-group">
                  <label>Point Redemption Value (in Paise)</label>
                  <input type="number" name="settings[loyalty_point_value_paise]" value="<?= e((string)($current['loyalty_point_value_paise'] ?? 100)) ?>">
                  <span class="help-hint">100 paise = ₹1.00 per loyalty point.</span>
                </div>

                <div class="form-group">
                  <label>Minimum Points Required to Redeem</label>
                  <input type="number" name="settings[loyalty_min_redeem_points]" value="<?= e((string)($current['loyalty_min_redeem_points'] ?? 50)) ?>">
                </div>

                <div class="form-group">
                  <label>Maximum Bill Discount Cap (%)</label>
                  <input type="number" step="1" name="settings[loyalty_max_redeem_percent]" value="<?= e((string)($current['loyalty_max_redeem_percent'] ?? 50)) ?>">
                  <span class="help-hint">Protects margins (e.g. points cannot cover >50% of bill).</span>
                </div>
              </div>
            </div>

            <!-- 6. Promotional Engine: Coupons, Happy Hours & Stacking Rules -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Promotions, Happy Hours & Stacking Engine</h2>
                  <div class="section-desc">Audience targeting, automated time-window offers, sourdough combos, and coupon stacking policy.</div>
                </div>
              </div>

              <!-- Promo Stacking Configuration -->
              <div class="form-grid single-col" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                  <label>Promotional Stacking Policy</label>
                  <select name="settings[promo_stacking_mode]">
                    <option value="stackable" <?= (($current['promo_stacking_mode'] ?? 'stackable') === 'stackable') ? 'selected' : '' ?>>
                      Stackable Mode (Combos & Happy Hours combine with Coupon Codes)
                    </option>
                    <option value="exclusive" <?= (($current['promo_stacking_mode'] ?? 'stackable') === 'exclusive') ? 'selected' : '' ?>>
                      Exclusive Mode (Happy Hours & Combos block Coupon Codes)
                    </option>
                  </select>
                  <span class="help-hint">Controls how the authoritative cart evaluator applies multiple promotions at checkout.</span>
                </div>
              </div>

              <!-- Automated Time-Based Offers (Happy Hours & Combos) -->
              <div style="margin-top: 1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                  <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0;">Automated Offers & Combos</h3>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?= count($offersList) ?> Active Schedules</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($offersList as $off): ?>
                    <?php 
                      $rules = !empty($off['rules_json']) ? json_decode($off['rules_json'], true) : [];
                      $sched = !empty($off['schedule_json']) ? json_decode($off['schedule_json'], true) : [];
                      $daysText = !empty($sched['days']) ? implode(', ', array_slice($sched['days'], 0, 4)) . (count($sched['days']) > 4 ? '...' : '') : 'Everyday';
                      $timeText = (!empty($sched['start_time']) && !empty($sched['end_time'])) ? ($sched['start_time'] . ' - ' . $sched['end_time']) : 'All Day';
                    ?>
                    <div style="background:var(--bg-cream); border:1px solid var(--border-warm); border-radius:12px; padding:0.9rem 1.15rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.6rem;">
                      <div>
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                          <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:2px 8px; border-radius:4px; background:<?= $off['type'] === 'happy_hour' ? 'rgba(107, 154, 196, 0.2)' : 'rgba(201, 106, 82, 0.18)' ?>; color:<?= $off['type'] === 'happy_hour' ? '#3B6A8F' : '#A74A34' ?>;">
                            <?= $off['type'] === 'happy_hour' ? 'Happy Hour' : 'Sourdough Combo' ?>
                          </span>
                          <strong style="font-size:0.9rem; color:var(--text-espresso);"><?= e($off['title']) ?></strong>
                        </div>
                        <div style="font-size:0.78rem; color:var(--text-muted);">
                          Schedule: <span style="color:var(--text-espresso); font-weight:600;"><?= e($daysText) ?> (<?= e($timeText) ?>)</span>
                        </div>
                      </div>
                      <span style="font-size:0.75rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:4px 10px; border-radius:999px;">
                        Active
                      </span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Active Coupons & Audience Targeting -->
              <div style="margin-top: 1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                  <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0;">Vouchers & Audience Targeting</h3>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?= count($couponsList) ?> Configured Vouchers</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($couponsList as $cp): ?>
                    <?php
                      $audBadgeColor = match($cp['audience']) {
                        'vip' => 'background:rgba(26,20,18,0.08); color:var(--text-espresso); border:1px solid rgba(26,20,18,0.2);',
                        'first_time' => 'background:rgba(201,106,82,0.15); color:var(--accent-terracotta); border:1px solid rgba(201,106,82,0.3);',
                        default => 'background:rgba(107,154,196,0.15); color:var(--accent-sky-deep); border:1px solid rgba(107,154,196,0.3);',
                      };
                      $audLabel = match($cp['audience']) {
                        'vip' => 'VIP Patrons (5+ visits or 500+ pts)',
                        'first_time' => 'First-Time Guests Only',
                        default => 'All Dining Guests',
                      };
                      $discountDisplay = ($cp['type'] === 'percentage') 
                        ? ($cp['value'] . '% Off (Capped at ₹' . ($cp['max_discount_paise'] / 100) . ')')
                        : ('Flat ₹' . $cp['value'] . ' Off');
                    ?>
                    <div style="background:#FFFFFF; border:1px solid var(--border-warm); border-radius:12px; padding:0.9rem 1.15rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.6rem;">
                      <div>
                        <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.3rem;">
                          <span style="font-family:monospace; font-weight:800; font-size:0.92rem; background:rgba(26,20,18,0.06); padding:2px 8px; border-radius:6px; letter-spacing:0.05em; color:var(--text-espresso);">
                            <?= e($cp['code']) ?>
                          </span>
                          <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:999px; <?= $audBadgeColor ?>">
                            <?= e($audLabel) ?>
                          </span>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-espresso); font-weight:600;">
                          <?= e($cp['title']) ?> &middot; <span style="color:var(--accent-terracotta);"><?= e($discountDisplay) ?></span>
                        </div>
                        <div style="font-size:0.74rem; color:var(--text-muted); margin-top:2px;">
                          Min Order: ₹<?= number_format($cp['min_order_paise'] / 100) ?> &middot; Limit: <?= $cp['usage_limit_per_user'] ?>x per patron
                        </div>
                      </div>
                      <div style="text-align:right;">
                        <span style="font-size:0.75rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:4px 10px; border-radius:999px;">
                          <?= (!empty($cp['is_active'])) ? 'Active' : 'Inactive' ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            </div>

            <!-- 7. Verified Customer Reviews & Social Proof -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#F59E0B;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Verified Customer Reviews & Social Proof</h2>
                  <div class="section-desc">Patron dining feedback, automated +50 points reward gate, and homepage social proof showcases.</div>
                </div>
              </div>

              <div class="form-grid single-col" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                  <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="hidden" name="settings[reviews_auto_approve_positive]" value="0">
                    <input type="checkbox" name="settings[reviews_auto_approve_positive]" value="1" <?= (!isset($current['reviews_auto_approve_positive']) || $current['reviews_auto_approve_positive']) ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent-terracotta);">
                    <span>Auto-Publish Positive Reviews (4 & 5 Stars)</span>
                  </label>
                  <span class="help-hint">When enabled, genuine 4 and 5 star reviews with no profanity are published immediately to the website homepage showcase and +50 points are credited to the patron. Lower ratings (1-3 stars) are queued in moderation.</span>
                </div>
              </div>

              <div style="background:var(--bg-cream); border-radius:12px; padding:1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                  <div style="font-size:0.85rem; font-weight:700; color:var(--text-espresso);">Review Moderation Queue</div>
                  <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                    Inspect, approve, or hide customer dining testimonials and photos.
                  </div>
                </div>
                <a href="/admin/reviews.php" class="btn-action" style="text-decoration:none; background:var(--accent-terracotta); color:#ffffff; padding:0.6rem 1.25rem; border-radius:8px; font-size:0.82rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem;">
                  <span>Open Moderation Hub →</span>
                </a>
              </div>

            </div>

            <!-- 8. SEO, Webmaster & Marketing Tracking -->
            <div class="cms-card" id="seo-section">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div style="flex:1;">
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="section-title">SEO, Webmaster &amp; Marketing Tracking</h2>
                    <a href="/admin/seo.php" target="_blank" style="font-size:0.75rem; color:var(--accent-sky-deep); font-weight:700; text-decoration:none; background:var(--bg-cream); border:1px solid var(--border-warm); padding:4px 10px; border-radius:6px;">
                      Open Full Studio &amp; SERP Simulator ↗
                    </a>
                  </div>
                  <div class="section-desc">Control Google Search Console, Google Analytics (GA4), Bing, Meta Pixel, and Per-Page Search Metadata directly from here.</div>
                </div>
              </div>

              <!-- Subsection: Webmaster Verifications & Analytics -->
              <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0 0 0.85rem; padding-bottom:0.4rem; border-bottom:1px dashed var(--border-warm);">
                Webmaster Verifications &amp; Tracking Integrations
              </h3>

              <div class="form-grid" style="margin-bottom:1.5rem;">
                <div class="form-group" style="background:#F9FBFD; border:1px solid rgba(66, 133, 244, 0.2); border-radius:10px; padding:1rem;">
                  <label style="color:#1A73E8; font-weight:700;">Google Search Console Unique Code</label>
                  <input type="text" name="settings[seo_google_site_verification]" placeholder="e.g. 4bX8jQp9zM1_sK2wL8vN0pQ7rT3uY6" value="<?= e((string)($current['seo_google_site_verification'] ?? '')) ?>" oninput="cleanUniqueVerificationCode(this, 'gsc')" onblur="cleanUniqueVerificationCode(this, 'gsc')" style="background:#FFFFFF;">
                  <span class="help-hint">Sirf Unique Code enter karein. Inserts <code>&lt;meta name="google-site-verification" content="..."&gt;</code></span>
                </div>

                <div class="form-group" style="background:#F7FBFD; border:1px solid rgba(0, 120, 212, 0.2); border-radius:10px; padding:1rem;">
                  <label style="color:#0078D4; font-weight:700;">Bing Webmaster Unique Code (msvalidate.01)</label>
                  <input type="text" name="settings[seo_bing_site_verification]" placeholder="e.g. A483E58D98C127D480BC4B4958309482" value="<?= e((string)($current['seo_bing_site_verification'] ?? '')) ?>" oninput="cleanUniqueVerificationCode(this, 'bing')" onblur="cleanUniqueVerificationCode(this, 'bing')" style="background:#FFFFFF;">
                  <span class="help-hint">Sirf 32-character Unique Code enter karein. Inserts <code>&lt;meta name="msvalidate.01" content="..."&gt;</code></span>
                </div>

                <div class="form-group" style="background:#FAF8F5; border:1px solid var(--border-warm); border-radius:10px; padding:1rem;">
                  <label style="color:var(--text-espresso);">Google Analytics 4 (GA4)</label>
                  <input type="text" name="settings[seo_google_analytics_id]" placeholder="G-XXXXXXXXXX" value="<?= e((string)($current['seo_google_analytics_id'] ?? '')) ?>" style="background:#FFFFFF;">
                  <span class="help-hint">Measurement ID. Injects official <code>gtag.js</code> tracking.</span>
                </div>

                <div class="form-group" style="background:#FAFBFD; border:1px solid rgba(24, 119, 242, 0.2); border-radius:10px; padding:1rem;">
                  <label style="color:#1877F2;">Meta Pixel (Facebook Pixel)</label>
                  <input type="text" name="settings[seo_meta_pixel_id]" placeholder="Pixel ID (e.g. 1234567890)" value="<?= e((string)($current['seo_meta_pixel_id'] ?? '')) ?>" style="background:#FFFFFF;">
                  <span class="help-hint">Injects official <code>fbq('init')</code> script + noscript pixel.</span>
                </div>
              </div>

              <!-- Subsection: Global SEO Metadata Defaults -->
              <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0 0 0.85rem; padding-bottom:0.4rem; border-bottom:1px dashed var(--border-warm);">
                Global Website SEO Defaults
              </h3>

              <div class="form-grid" style="margin-bottom:1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label>Global Site Title / Title Template</label>
                  <input type="text" name="settings[seo_global_site_title]" value="<?= e((string)($current['seo_global_site_title'] ?? 'RiTiVerse | Heritage Cafe & Cultural Space · Vadodara, Gujarat')) ?>">
                  <span class="help-hint">Default browser tab title and Google SERP main headline.</span>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label>Default Meta Description</label>
                  <textarea name="settings[seo_global_meta_description]" rows="2"><?= e((string)($current['seo_global_meta_description'] ?? 'Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at RiTiVerse, Vadodara, Gujarat.')) ?></textarea>
                  <span class="help-hint">140–160 characters summary displayed under the link in search results.</span>
                </div>

                <div class="form-group">
                  <label>Default Meta Keywords</label>
                  <input type="text" name="settings[seo_global_meta_keywords]" value="<?= e((string)($current['seo_global_meta_keywords'] ?? 'RiTiVerse, RiTiVerse Cafe, Vadodara cafe, Best cafe in Vadodara, Gujarat cafe, Heritage cafe Vadodara')) ?>">
                </div>

                <div class="form-group">
                  <label>Canonical Base Domain</label>
                  <input type="url" name="settings[seo_global_canonical_url]" value="<?= e((string)($current['seo_global_canonical_url'] ?? 'https://ritiverse.in')) ?>">
                </div>

                <div class="form-group">
                  <label>Robots Indexing</label>
                  <select name="settings[seo_global_robots]">
                    <option value="index, follow" <?= (($current['seo_global_robots'] ?? 'index, follow') === 'index, follow') ? 'selected' : '' ?>>index, follow (Live Production - Recommended)</option>
                    <option value="noindex, nofollow" <?= (($current['seo_global_robots'] ?? '') === 'noindex, nofollow') ? 'selected' : '' ?>>noindex, nofollow (Development / Staging Only)</option>
                    <option value="noindex, follow" <?= (($current['seo_global_robots'] ?? '') === 'noindex, follow') ? 'selected' : '' ?>>noindex, follow (Do not index, follow links)</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Default Social Share (OG) Image</label>
                  <input type="text" name="settings[seo_global_og_image]" value="<?= e((string)($current['seo_global_og_image'] ?? 'assets/images/cafe/hero-table.jpg')) ?>">
                </div>
              </div>

              <!-- Subsection: Per-Page SEO Overrides -->
              <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0 0 0.85rem; padding-bottom:0.4rem; border-bottom:1px dashed var(--border-warm);">
                Per-Page SEO Overrides
              </h3>
              <p style="font-size:0.78rem; color:var(--text-muted); margin:0 0 1rem;">
                Customize the specific Google Title and Meta Description for every individual public page:
              </p>

              <?php
                $cmsPageList = [
                    'home' => ['name' => 'Home Page', 'route' => '/'],
                    'menu' => ['name' => 'Our Menu', 'route' => '/menu'],
                    'about' => ['name' => 'About & Heritage', 'route' => '/about'],
                    'events' => ['name' => 'Events & Gatherings', 'route' => '/events'],
                    'gallery' => ['name' => 'Visual Diary Gallery', 'route' => '/gallery'],
                    'contact' => ['name' => 'Contact & Hours', 'route' => '/contact'],
                    'table' => ['name' => 'Digital Table Order', 'route' => '/table'],
                ];
              ?>

              <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php foreach ($cmsPageList as $pKey => $pMeta): ?>
                  <?php $pData = $seoPages[$pKey] ?? []; ?>
                  <details style="background:var(--bg-cream); border:1px solid var(--border-warm); border-radius:10px; overflow:hidden;">
                    <summary style="padding:0.85rem 1rem; cursor:pointer; font-weight:700; font-size:0.86rem; display:flex; justify-content:space-between; align-items:center; user-select:none; flex-wrap:wrap; gap:0.5rem;">
                      <span><?= e($pMeta['name']) ?> <span style="font-family:monospace; font-size:0.75rem; color:var(--accent-sky-deep); background:var(--accent-sky-soft); padding:1px 6px; border-radius:4px; margin-left:6px;"><?= e($pMeta['route']) ?></span></span>
                      <span style="font-size:0.72rem; color:var(--text-muted); font-weight:normal;">Click to edit ▾</span>
                    </summary>
                    <div style="padding:1rem; border-top:1px solid var(--border-warm); background:#FFFFFF;">
                      <input type="hidden" name="pages[<?= e($pKey) ?>][path]" value="<?= e((string)($pData['path'] ?? ($pKey === 'home' ? '' : $pKey))) ?>">
                      <div class="form-grid single-col" style="gap:0.85rem;">
                        <div class="form-group">
                          <label style="font-size:0.8rem;">Page Title Tag</label>
                          <input type="text" name="pages[<?= e($pKey) ?>][title]" value="<?= e((string)($pData['title'] ?? '')) ?>" placeholder="Leave blank to use global site title">
                        </div>
                        <div class="form-group">
                          <label style="font-size:0.8rem;">Meta Description</label>
                          <textarea name="pages[<?= e($pKey) ?>][description]" rows="2" placeholder="Leave blank to use global description"><?= e((string)($pData['description'] ?? '')) ?></textarea>
                        </div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:0.75rem;">
                          <div class="form-group">
                            <label style="font-size:0.8rem;">Keywords</label>
                            <input type="text" name="pages[<?= e($pKey) ?>][keywords]" value="<?= e((string)($pData['keywords'] ?? '')) ?>">
                          </div>
                          <div class="form-group">
                            <label style="font-size:0.8rem;">Robots</label>
                            <select name="pages[<?= e($pKey) ?>][robots]">
                              <option value="index, follow" <?= (($pData['robots'] ?? 'index, follow') === 'index, follow') ? 'selected' : '' ?>>index, follow</option>
                              <option value="noindex, follow" <?= (($pData['robots'] ?? '') === 'noindex, follow') ? 'selected' : '' ?>>noindex, follow</option>
                              <option value="noindex, nofollow" <?= (($pData['robots'] ?? '') === 'noindex, nofollow') ? 'selected' : '' ?>>noindex, nofollow</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>
                  </details>
                <?php endforeach; ?>
              </div>

            </div>

          </div>

        </div>

        <!-- Floating Save Bar -->
        <div class="floating-save-bar">
          <button type="submit" class="btn-save-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span>Save All Settings</span>
          </button>
        </div>

      </form>

    </main>

  </div>

  <script>
    function cleanUniqueVerificationCode(input, type) {
      if (!input || !input.value) return;
      let v = input.value.trim();
      if (type === 'gsc') {
        const match = v.match(/content=["']([^"']+)["']/i);
        if (match) v = match[1];
      } else if (type === 'bing') {
        const match = v.match(/content=["']([^"']+)["']/i);
        if (match) v = match[1];
      }
      v = v.replace(/<[^>]*>?/gm, '').replace(/["']/g, '').trim();
      if (input.value !== v) {
        input.value = v;
      }
    }
  </script>

</body>
</html>
