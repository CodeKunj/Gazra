<?php

declare(strict_types=1);

/**
 * RiTiVerse Cafe - SEO & Tracking Integrations Studio
 * Complete Global and Per-Page SEO, Google Search Console, Bing Webmaster,
 * Google Analytics (GA4), and Meta Pixel Management.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\AppDataService;
use App\Models\Setting;
use App\Models\AuditLog;
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
        $postedGlobal = $_POST['global'] ?? [];
        $postedPages = $_POST['pages'] ?? [];

        // Save Global SEO & Tracking
        foreach ($postedGlobal as $key => $val) {
            $key = trim((string)$key);
            $val = trim((string)$val);

            // Sanitize GSC unique verification code if user pasted full HTML tag or quotes
            if ($key === 'seo_google_site_verification') {
                if (preg_match('/content=["\']([^"\']+)["\']/i', $val, $m)) {
                    $val = $m[1];
                }
                $val = trim(strip_tags(str_replace(['"', "'"], '', $val)));
            }
            // Sanitize Bing unique verification code if user pasted full HTML tag or quotes
            if ($key === 'seo_bing_site_verification') {
                if (preg_match('/content=["\']([^"\']+)["\']/i', $val, $m)) {
                    $val = $m[1];
                }
                $val = trim(strip_tags(str_replace(['"', "'"], '', $val)));
            }
            // Sanitize Meta Pixel if user pasted script
            if ($key === 'seo_meta_pixel_id' && preg_match('/[0-9]{8,20}/', $val, $m)) {
                $val = $m[0];
            }

            Setting::set($key, $val, 'seo', 'string');
        }

        // Save Per-Page SEO data (JSON array)
        if (is_array($postedPages)) {
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

        // Clear runtime & file cache so public site instantly reflects updates
        AppDataService::clearCache();

        AuditLog::log($staff['id'], 'seo_settings_updated', 'settings', null, [
            'global_keys' => count($postedGlobal),
            'pages_count' => count($postedPages)
        ]);

        $msg = 'SEO & tracking configurations saved successfully! Changes are live across all pages immediately.';
    }
}

// Fetch all SEO configurations (Global + Pages)
$allSeo = AppDataService::getAllSeoConfig();
$global = $allSeo['global'] ?? [];
$pages = $allSeo['pages'] ?? [];

$csrfToken = Security::generateCsrfToken();
$activeNav = 'seo';

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
  <title>SEO &amp; Tracking Integrations Studio | RiTiVerse Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-page: #F4F1EA;
      --bg-surface: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-terracotta-soft: rgba(201, 106, 82, 0.1);
      --accent-sky: #6B9AC4;
      --accent-sky-soft: rgba(107, 154, 196, 0.12);
      --accent-sky-deep: #4A7A9F;
      --accent-sage: #2D6A4F;
      --border-warm: rgba(26, 20, 18, 0.1);
      --border-light: rgba(26, 20, 18, 0.06);
      --shadow-sm: 0 2px 8px rgba(26, 20, 18, 0.04);
      --shadow-card: 0 4px 20px rgba(26, 20, 18, 0.06);
      --shadow-lg: 0 12px 36px rgba(26, 20, 18, 0.1);
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
      background: var(--bg-page);
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      -webkit-font-smoothing: antialiased;
    }

    /* Right Main Layout */
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

    /* Top Sticky Action Bar */
    .admin-topbar {
      background: #FFFFFF;
      border-bottom: 1px solid var(--border-warm);
      padding: 1.15rem 2.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      position: sticky;
      top: 0;
      z-index: 900;
      box-shadow: 0 2px 12px rgba(26, 20, 18, 0.03);
    }

    .topbar-heading {
      font-family: 'Fraunces', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.15;
      letter-spacing: -0.01em;
    }

    .topbar-subtext {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }

    .btn-topbar-link {
      text-decoration: none;
      background: var(--bg-cream);
      border: 1px solid var(--border-warm);
      color: var(--text-espresso);
      padding: 0.65rem 1.25rem;
      border-radius: 999px;
      font-size: 0.84rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: all 0.2s ease;
    }

    .btn-topbar-link:hover {
      background: #FFFFFF;
      border-color: var(--accent-sky);
      transform: translateY(-1px);
      box-shadow: var(--shadow-sm);
    }

    /* Main Content Container */
    .seo-content-area {
      padding: 2.25rem 2.5rem 6rem;
      max-width: 1440px;
      width: 100%;
      min-width: 0;
      box-sizing: border-box;
      margin: 0 auto;
    }

    /* Quick Jump Navigation Pill Bar */
    .quick-nav-pills {
      display: flex;
      gap: 0.6rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      background: #FFFFFF;
      padding: 0.5rem;
      border-radius: 14px;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-sm);
    }

    .pill-btn {
      padding: 0.55rem 1.1rem;
      border-radius: 10px;
      border: none;
      background: transparent;
      color: var(--text-muted);
      font-family: inherit;
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .pill-btn:hover {
      background: var(--bg-cream);
      color: var(--text-espresso);
    }

    .pill-btn.active {
      background: var(--text-espresso);
      color: #FFFFFF;
      box-shadow: 0 2px 8px rgba(26,20,18,0.25);
    }

    /* Toast Notification */
    .toast-alert {
      background: #FFFFFF;
      border-left: 4px solid var(--accent-sage);
      border-radius: 12px;
      padding: 1.1rem 1.5rem;
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

    /* Two-Column Studio Layout */
    .studio-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 380px;
      gap: 2rem;
      align-items: start;
      min-width: 0;
    }

    .form-sections {
      min-width: 0;
      width: 100%;
    }

    /* Studio Card */
    .seo-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      padding: 2.25rem;
      margin-bottom: 2rem;
      transition: all 0.2s ease;
      min-width: 0;
      box-sizing: border-box;
    }

    .seo-card:hover {
      border-color: rgba(107, 154, 196, 0.45);
      box-shadow: var(--shadow-lg);
    }

    .section-header {
      border-bottom: 1px solid var(--border-warm);
      padding-bottom: 1.25rem;
      margin-bottom: 1.75rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .section-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: var(--bg-cream);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
      flex-shrink: 0;
      box-shadow: inset 0 0 0 1px rgba(26,20,18,0.05);
    }

    .section-title {
      font-family: 'Fraunces', serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .section-desc {
      font-size: 0.83rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
      line-height: 1.45;
    }

    /* Form Fields Grid */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1.35rem 1.5rem;
      min-width: 0;
    }

    .form-grid.single-col {
      grid-template-columns: minmax(0, 1fr);
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      min-width: 0;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      font-size: 0.86rem;
      font-weight: 700;
      color: var(--text-espresso);
      display: flex;
      align-items: center;
      justify-content: space-between;
      word-break: break-word;
      gap: 0.5rem;
    }

    .form-group input[type="text"],
    .form-group input[type="url"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
      padding: 0.8rem 1rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      background: #FAFAF7;
      color: var(--text-espresso);
      font-family: inherit;
      font-size: 0.92rem;
      transition: all 0.2s ease;
    }

    .form-group select {
      text-overflow: ellipsis;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      background: #FFFFFF;
      border-color: var(--accent-sky);
      box-shadow: 0 0 0 3px var(--accent-sky-soft);
    }

    .help-hint {
      font-size: 0.76rem;
      color: var(--text-muted);
      line-height: 1.45;
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    .help-hint code {
      background: rgba(26,20,18,0.06);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: monospace;
      color: var(--accent-terracotta);
      word-break: break-all;
      overflow-wrap: anywhere;
      white-space: normal;
    }

    /* Integration Cards */
    .integration-box {
      border: 1px solid var(--border-warm);
      border-radius: 14px;
      padding: 1.35rem;
      background: #FAF9F6;
      display: flex;
      flex-direction: column;
      gap: 0.65rem;
      transition: all 0.2s ease;
      min-width: 0;
      box-sizing: border-box;
      word-break: break-word;
    }

    .integration-box:hover {
      border-color: rgba(107, 154, 196, 0.4);
      background: #FFFFFF;
      box-shadow: var(--shadow-sm);
    }

    .integration-box.active-google {
      background: #F8FAFD;
      border-color: rgba(66, 133, 244, 0.25);
    }

    .integration-box.active-bing {
      background: #F6FBFC;
      border-color: rgba(0, 120, 212, 0.25);
    }

    .integration-box.active-meta {
      background: #F7FAFD;
      border-color: rgba(24, 119, 242, 0.25);
    }

    .integration-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.25rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .integration-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 6px;
    }

    .badge-google { background: rgba(66, 133, 244, 0.12); color: #1A73E8; }
    .badge-bing { background: rgba(0, 120, 212, 0.12); color: #0078D4; }
    .badge-meta { background: rgba(24, 119, 242, 0.12); color: #1877F2; }

    /* Per-Page SEO Accordion */
    .page-accordion-item {
      background: #FFFFFF;
      border: 1px solid var(--border-warm);
      border-radius: 12px;
      margin-bottom: 0.85rem;
      overflow: hidden;
      transition: all 0.2s ease;
      box-sizing: border-box;
    }

    .page-accordion-item:hover {
      border-color: rgba(107, 154, 196, 0.4);
    }

    .page-accordion-item.active {
      border-color: var(--accent-terracotta);
      box-shadow: var(--shadow-sm);
    }

    .page-accordion-header {
      padding: 1.1rem 1.35rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      background: #FAFAF7;
      user-select: none;
      transition: background 0.15s ease;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .page-accordion-header:hover {
      background: #F4F1EA;
    }

    .page-title-badge {
      font-weight: 700;
      font-size: 0.94rem;
      color: var(--text-espresso);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .page-path-pill {
      font-family: monospace;
      font-size: 0.75rem;
      color: var(--accent-sky-deep);
      background: var(--accent-sky-soft);
      padding: 3px 8px;
      border-radius: 6px;
    }

    .page-accordion-header .chevron {
      transition: transform 0.25s ease;
    }

    .page-accordion-item.active .page-accordion-header .chevron {
      transform: rotate(180deg);
    }

    .page-accordion-body {
      padding: 1.65rem;
      display: none;
      border-top: 1px solid var(--border-warm);
      background: #FFFFFF;
      box-sizing: border-box;
    }

    .page-accordion-item.active .page-accordion-body {
      display: block;
    }

    /* Live Preview Sticky Sidebar */
    .preview-sidebar {
      position: sticky;
      top: 5.5rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      width: 100%;
      max-width: 380px;
      min-width: 0;
      box-sizing: border-box;
    }

    .preview-card {
      background: #FFFFFF;
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      width: 100%;
      box-sizing: border-box;
    }

    .preview-header {
      background: #1A1412;
      color: #F7F4EE;
      padding: 0.9rem 1.35rem;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .preview-body {
      padding: 1.35rem;
      box-sizing: border-box;
      word-break: break-word;
      overflow-wrap: break-word;
    }

    /* Realistic Google Search SERP Mockup */
    .serp-box {
      background: #FFFFFF;
      border: 1px solid #DFE1E5;
      border-radius: 12px;
      padding: 1.15rem 1.25rem;
      box-shadow: 0 1px 6px rgba(32,33,36,0.06);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      box-sizing: border-box;
      overflow: hidden;
    }

    .serp-header {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      margin-bottom: 0.35rem;
      overflow: hidden;
    }

    .serp-favicon {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: #1A1412;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.78rem;
      font-weight: bold;
      font-family: 'Fraunces', serif;
      flex-shrink: 0;
    }

    .serp-source {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .serp-domain {
      font-size: 0.82rem;
      font-weight: 600;
      color: #202124;
      line-height: 1.2;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .serp-url {
      font-size: 0.72rem;
      color: #4D5156;
      line-height: 1.2;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .serp-title {
      font-size: 1.15rem;
      color: #1A0DAB;
      cursor: pointer;
      line-height: 1.35;
      margin-top: 0.45rem;
      font-weight: 400;
      word-break: break-word;
      overflow-wrap: break-word;
    }

    .serp-title:hover {
      text-decoration: underline;
    }

    .serp-snippet {
      font-size: 0.83rem;
      color: #4D5156;
      line-height: 1.5;
      margin-top: 0.45rem;
      word-break: break-word;
      overflow-wrap: break-word;
    }

    /* Social Card Mockup */
    .social-card-mock {
      background: #FFFFFF;
      border: 1px solid var(--border-warm);
      border-radius: 12px;
      overflow: hidden;
      margin-top: 1.25rem;
      box-shadow: var(--shadow-sm);
      box-sizing: border-box;
    }

    .social-card-img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      background: #E8E5DD;
      display: block;
    }

    .social-card-meta {
      padding: 0.95rem 1.15rem;
      background: #F8F9FA;
      box-sizing: border-box;
      word-break: break-word;
      overflow-wrap: break-word;
    }

    .social-card-domain {
      font-size: 0.7rem;
      color: #65676B;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.05em;
    }

    .social-card-title {
      font-size: 0.92rem;
      font-weight: 700;
      color: #050505;
      margin-top: 3px;
      line-height: 1.35;
      word-break: break-word;
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

    @media (max-width: 1200px) {
      .studio-layout {
        grid-template-columns: minmax(0, 1fr);
      }
      .preview-sidebar {
        position: static;
        max-width: 100%;
        margin-bottom: 2rem;
      }
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
      .seo-content-area {
        padding: 1.25rem 1rem 6rem;
      }
      .admin-topbar {
        padding: 1rem 1.25rem;
      }
      .topbar-heading {
        font-size: 1.35rem;
      }
      .quick-nav-pills {
        padding: 0.4rem;
        gap: 0.4rem;
        margin-bottom: 1.25rem;
      }
      .pill-btn {
        padding: 0.45rem 0.85rem;
        font-size: 0.78rem;
      }
      .form-grid {
        grid-template-columns: minmax(0, 1fr) !important;
      }
      .seo-card {
        padding: 1.25rem;
      }
      .page-accordion-body {
        padding: 1.15rem;
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

  <!-- Left Sidebar Navigation Component (Self-Contained & Responsive) -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Right Content Wrapper -->
  <div class="admin-main-wrapper">

    <!-- Top Action Bar -->
    <header class="admin-topbar">
      <div>
        <h1 class="topbar-heading">SEO &amp; Tracking Integrations Studio</h1>
        <span class="topbar-subtext">Google Search Console, Bing Webmaster, Google Analytics (GA4), Meta Pixel &amp; Per-Page SEO</span>
      </div>

      <div style="display:flex; align-items:center; gap:0.75rem;">
        <a href="/index.php" target="_blank" class="btn-topbar-link">
          <span>Preview Public Website ↗</span>
        </a>
      </div>
    </header>

    <!-- Content Body -->
    <main class="seo-content-area">

      <!-- Alert Notifications -->
      <?php if ($msg !== ''): ?>
        <div class="toast-alert">
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e($msg) ?></span>
          </div>
          <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted);">&times;</button>
        </div>
      <?php elseif ($err !== ''): ?>
        <div class="toast-alert error">
          <span><?= e($err) ?></span>
          <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--accent-terracotta);">&times;</button>
        </div>
      <?php endif; ?>

      <!-- Quick Section Navigation Pills -->
      <nav class="quick-nav-pills" aria-label="SEO Sections">
        <a href="#section-global" class="pill-btn active">🌐 Global SEO Defaults</a>
        <a href="#section-webmaster" class="pill-btn">🔍 Webmaster Verifications</a>
        <a href="#section-tracking" class="pill-btn">📈 Analytics &amp; Pixels</a>
        <a href="#section-pages" class="pill-btn">📑 Per-Page Manager</a>
      </nav>

      <form method="POST" action="/admin/seo.php" id="seoForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="studio-layout">

          <!-- Left Form Sections -->
          <div class="form-sections">

            <!-- 1. Global Website SEO -->
            <div class="seo-card" id="section-global">
              <div class="section-header">
                <div class="section-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Global Website SEO &amp; Social Defaults</h2>
                  <div class="section-desc">Default metadata used across the entire website when page-specific overrides are not provided.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group full-width">
                  <label>
                    <span>Global Site Title / Title Template</span>
                    <span id="title_char_count" style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;"></span>
                  </label>
                  <input type="text" id="global_site_title" name="global[seo_global_site_title]" value="<?= e((string)($global['site_title'] ?? 'RiTiVerse | Heritage Cafe & Cultural Space · Vadodara, Gujarat')) ?>" oninput="updateLiveSerpPreview()">
                  <span class="help-hint">Recommended: 50–60 characters. Appears in browser tabs and primary search engine snippets.</span>
                </div>

                <div class="form-group full-width">
                  <label>
                    <span>Default Meta Description</span>
                    <span id="desc_char_count" style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;"></span>
                  </label>
                  <textarea id="global_meta_desc" name="global[seo_global_meta_description]" rows="3" oninput="updateLiveSerpPreview()"><?= e((string)($global['meta_description'] ?? 'Experience warm hospitality, heritage architecture, and delicious homestyle cuisine at RiTiVerse, Vadodara, Gujarat.')) ?></textarea>
                  <span class="help-hint">Recommended: 140–160 characters. Compelling summary displayed below your title in Google search results.</span>
                </div>

                <div class="form-group full-width">
                  <label>Default Meta Keywords</label>
                  <input type="text" name="global[seo_global_meta_keywords]" value="<?= e((string)($global['meta_keywords'] ?? 'RiTiVerse, RiTiVerse Cafe, Vadodara cafe, Best cafe in Vadodara, Gujarat cafe, Heritage cafe Vadodara')) ?>">
                  <span class="help-hint">Comma-separated key phrases relevant to the brand and dining offerings.</span>
                </div>

                <div class="form-group">
                  <label>Canonical Base URL</label>
                  <input type="url" id="global_canonical_url" name="global[seo_global_canonical_url]" value="<?= e((string)($global['canonical_base'] ?? 'https://ritiverse.in')) ?>" oninput="updateLiveSerpPreview()">
                  <span class="help-hint">Production domain without trailing slash (e.g. <code>https://ritiverse.in</code>). Prevents duplicate content penalties.</span>
                </div>

                <div class="form-group">
                  <label>Global Robots Indexing Policy</label>
                  <select name="global[seo_global_robots]">
                    <option value="index, follow" <?= (($global['robots'] ?? 'index, follow') === 'index, follow') ? 'selected' : '' ?>>index, follow (Live Production - Recommended)</option>
                    <option value="noindex, nofollow" <?= (($global['robots'] ?? '') === 'noindex, nofollow') ? 'selected' : '' ?>>noindex, nofollow (Development / Staging Only)</option>
                    <option value="noindex, follow" <?= (($global['robots'] ?? '') === 'noindex, follow') ? 'selected' : '' ?>>noindex, follow (Hide from search, follow links)</option>
                  </select>
                  <span class="help-hint">Instructs Google, Bing, and other web crawlers how to index your site pages.</span>
                </div>

                <div class="form-group full-width">
                  <label>Default Social Share Banner (Open Graph / Twitter)</label>
                  <input type="text" id="global_og_image" name="global[seo_global_og_image]" value="<?= e((string)($global['og_image'] ?? 'assets/images/cafe/hero-table.jpg')) ?>" oninput="updateLiveSerpPreview()">
                  <span class="help-hint">Image preview shown when sharing links on WhatsApp, Facebook, LinkedIn, and Twitter (1200x630px recommended).</span>
                </div>
              </div>
            </div>

            <!-- 2. Search Engine Webmaster Verifications -->
            <div class="seo-card" id="section-webmaster">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Search Engine Webmaster Verifications</h2>
                  <div class="section-desc">Verify domain ownership for Google Search Console and Bing Webmaster Tools without editing theme files.</div>
                </div>
              </div>

              <div class="form-grid single-col">
                <!-- Google Search Console -->
                <div class="integration-box active-google">
                  <div class="integration-header">
                    <div style="display:flex; align-items:center; gap:0.55rem;">
                      <span class="integration-badge badge-google">Google Search Console</span>
                      <strong style="font-size:0.9rem; color:var(--text-espresso);">Unique Verification Token</strong>
                    </div>
                    <a href="https://search.google.com/search-console" target="_blank" style="font-size:0.75rem; color:#1A73E8; font-weight:600; text-decoration:none;">Open Google Search Console ↗</a>
                  </div>
                  <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted);">
                    Google Search Console Unique Code
                  </label>
                  <input type="text" id="input_gsc_token" name="global[seo_google_site_verification]" placeholder="e.g. 4bX8jQp9zM1_sK2wL8vN0pQ7rT3uY6" value="<?= e((string)($global['google_site_verification'] ?? '')) ?>" oninput="cleanUniqueVerificationCode(this, 'gsc')" onblur="cleanUniqueVerificationCode(this, 'gsc')">
                  <span class="help-hint">Yahaa sirf apna <strong>Unique Verification Code</strong> enter karein (HTML code nahi). Agar aap poora &lt;meta&gt; tag paste karenge to system automatically sirf unique code nikaal lega.</span>
                </div>

                <!-- Bing Search Console / Webmaster Tools -->
                <div class="integration-box active-bing">
                  <div class="integration-header">
                    <div style="display:flex; align-items:center; gap:0.55rem;">
                      <span class="integration-badge badge-bing">Bing Webmaster Tools</span>
                      <strong style="font-size:0.9rem; color:var(--text-espresso);">Unique Verification Token (msvalidate.01)</strong>
                    </div>
                    <a href="https://www.bing.com/webmasters" target="_blank" style="font-size:0.75rem; color:#0078D4; font-weight:600; text-decoration:none;">Open Bing Webmaster Tools ↗</a>
                  </div>
                  <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted);">
                    Bing Webmaster Unique Code
                  </label>
                  <input type="text" id="input_bing_token" name="global[seo_bing_site_verification]" placeholder="e.g. A483E58D98C127D480BC4B4958309482" value="<?= e((string)($global['bing_site_verification'] ?? '')) ?>" oninput="cleanUniqueVerificationCode(this, 'bing')" onblur="cleanUniqueVerificationCode(this, 'bing')">
                  <span class="help-hint">Yahaa sirf Bing ka 32-character <strong>Unique Code</strong> enter karein. Public header me <code>&lt;meta name="msvalidate.01" content="..."&gt;</code> automatically generate hota hai.</span>
                </div>
              </div>
            </div>

            <!-- 3. Tracking & Analytics Pixels -->
            <div class="seo-card" id="section-tracking">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sage);">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Analytics &amp; Marketing Tracking Pixels</h2>
                  <div class="section-desc">Connect Google Analytics 4 (GA4) and Meta Pixel (Facebook Pixel) with high-performance asynchronous loading.</div>
                </div>
              </div>

              <div class="form-grid">
                <!-- Google Analytics (GA4) -->
                <div class="integration-box active-google">
                  <div class="integration-header">
                    <span class="integration-badge badge-google">Google Analytics 4</span>
                    <a href="https://analytics.google.com/" target="_blank" style="font-size:0.75rem; color:#1A73E8; font-weight:600; text-decoration:none;">GA4 Console ↗</a>
                  </div>
                  <label>Measurement ID (G-XXXXXXXXXX)</label>
                  <input type="text" name="global[seo_google_analytics_id]" placeholder="G-XXXXXXXXXX" value="<?= e((string)($global['google_analytics_id'] ?? '')) ?>">
                  <span class="help-hint">Found in GA4 Admin &gt; Data Streams. Automatically activates official <code>gtag.js</code> tracking on all page visits.</span>
                </div>

                <!-- Meta Pixel (Facebook Pixel) -->
                <div class="integration-box active-meta">
                  <div class="integration-header">
                    <span class="integration-badge badge-meta">Meta Pixel (Facebook)</span>
                    <a href="https://business.facebook.com/events_manager2" target="_blank" style="font-size:0.75rem; color:#1877F2; font-weight:600; text-decoration:none;">Events Manager ↗</a>
                  </div>
                  <label>Meta Pixel ID (15–16 digits)</label>
                  <input type="text" name="global[seo_meta_pixel_id]" placeholder="e.g. 123456789012345" value="<?= e((string)($global['meta_pixel_id'] ?? '')) ?>">
                  <span class="help-hint">Loads official <code>fbq('init')</code> script + <code>&lt;noscript&gt;</code> fallback to track dining conversions and visitors.</span>
                </div>

                <!-- Custom Header Code -->
                <div class="form-group full-width">
                  <label>
                    <span>Custom Header Scripts (Google Tag Manager, Clarity, Hotjar)</span>
                    <span style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Injected inside &lt;head&gt;</span>
                  </label>
                  <textarea name="global[seo_custom_head_code]" rows="3" placeholder="<!-- Optional third-party scripts, Google Tag Manager GTM, or custom meta tags -->"><?= e((string)($global['custom_head_code'] ?? '')) ?></textarea>
                  <span class="help-hint">Paste optional tracking tags or custom DNS preconnect tags. Safely rendered directly in the head.</span>
                </div>
              </div>
            </div>

            <!-- 4. Per-Page SEO Manager -->
            <div class="seo-card" id="section-pages">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-terracotta);">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Per-Page SEO Manager</h2>
                  <div class="section-desc">Customize specific titles, search snippets, keywords, and social sharing banners for every individual page on the website.</div>
                </div>
              </div>

              <div class="page-accordion-list">
                <?php
                $pageLabels = [
                    'home' => ['icon' => '🏠', 'name' => 'Home Page', 'route' => '/'],
                    'menu' => ['icon' => '📋', 'name' => 'Artisanal Menu', 'route' => '/menu'],
                    'about' => ['icon' => '📖', 'name' => 'Our Story & Heritage', 'route' => '/about'],
                    'events' => ['icon' => '✨', 'name' => 'Private Events & Celebrations', 'route' => '/events'],
                    'gallery' => ['icon' => '📸', 'name' => 'Visual Diary & Gallery', 'route' => '/gallery'],
                    'contact' => ['icon' => '📍', 'name' => 'Contact, Location & Hours', 'route' => '/contact'],
                    'table' => ['icon' => '🍽️', 'name' => 'Digital Table Ordering', 'route' => '/table'],
                ];
                $idx = 0;
                ?>
                <?php foreach ($pageLabels as $pk => $pMeta): ?>
                  <?php 
                    $pData = $pages[$pk] ?? [];
                    $isActive = ($idx === 0);
                    $idx++;
                  ?>
                  <div class="page-accordion-item <?= $isActive ? 'active' : '' ?>" id="accordion_<?= e($pk) ?>">
                    <div class="page-accordion-header" onclick="toggleAccordion('<?= e($pk) ?>')">
                      <div class="page-title-badge">
                        <span><?= $pMeta['icon'] ?></span>
                        <strong><?= e($pMeta['name']) ?></strong>
                        <span class="page-path-pill"><?= e($pMeta['route']) ?></span>
                      </div>
                      <div style="display:flex; align-items:center; gap:0.5rem;">
                        <button type="button" onclick="event.stopPropagation(); previewPage('<?= e($pk) ?>')" style="background:var(--bg-cream); border:1px solid var(--border-warm); padding:4px 12px; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer;">
                          Preview in SERP
                        </button>
                        <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                      </div>
                    </div>

                    <div class="page-accordion-body">
                      <input type="hidden" name="pages[<?= e($pk) ?>][path]" value="<?= e((string)($pData['path'] ?? '')) ?>">

                      <div class="form-grid">
                        <div class="form-group full-width">
                          <label>Page Title (Title Tag)</label>
                          <input type="text" id="page_title_<?= e($pk) ?>" name="pages[<?= e($pk) ?>][title]" value="<?= e((string)($pData['title'] ?? '')) ?>" oninput="onPageInput('<?= e($pk) ?>')">
                          <span class="help-hint">Overrides default site title for this page in Google search snippets and browser tab.</span>
                        </div>

                        <div class="form-group full-width">
                          <label>Meta Description</label>
                          <textarea id="page_desc_<?= e($pk) ?>" name="pages[<?= e($pk) ?>][description]" rows="2" oninput="onPageInput('<?= e($pk) ?>')"><?= e((string)($pData['description'] ?? '')) ?></textarea>
                          <span class="help-hint">Summarizes this specific page's offering for search engine click-through rate optimization.</span>
                        </div>

                        <div class="form-group">
                          <label>Meta Keywords</label>
                          <input type="text" name="pages[<?= e($pk) ?>][keywords]" value="<?= e((string)($pData['keywords'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                          <label>Page Robots Rule</label>
                          <select name="pages[<?= e($pk) ?>][robots]">
                            <option value="index, follow" <?= (($pData['robots'] ?? 'index, follow') === 'index, follow') ? 'selected' : '' ?>>index, follow (Show in Search)</option>
                            <option value="noindex, follow" <?= (($pData['robots'] ?? '') === 'noindex, follow') ? 'selected' : '' ?>>noindex, follow (Do Not Index)</option>
                            <option value="noindex, nofollow" <?= (($pData['robots'] ?? '') === 'noindex, nofollow') ? 'selected' : '' ?>>noindex, nofollow (Private Flow)</option>
                          </select>
                        </div>

                        <div class="form-group full-width">
                          <label>Custom Social Share (OG) Image</label>
                          <input type="text" id="page_og_<?= e($pk) ?>" name="pages[<?= e($pk) ?>][og_image]" value="<?= e((string)($pData['og_image'] ?? '')) ?>" oninput="onPageInput('<?= e($pk) ?>')">
                          <span class="help-hint">Path or URL to preview banner when this specific page link is shared on WhatsApp or social apps.</span>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>

          <!-- Right Sticky Live SERP & Social Preview -->
          <div class="preview-sidebar">

            <div class="preview-card">
              <div class="preview-header">
                <span>Google Search Result Simulation</span>
                <span style="font-size:0.65rem; color:var(--accent-sky); text-transform:none;">Live Real-Time</span>
              </div>
              <div class="preview-body">
                
                <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.75rem;">
                  Currently previewing: <strong id="previewing_page_name" style="color:var(--text-espresso);">Home Page (/)</strong>
                </div>

                <!-- Google SERP Card -->
                <div class="serp-box">
                  <div class="serp-header">
                    <div class="serp-favicon">R</div>
                    <div class="serp-source">
                      <span class="serp-domain" id="serp_domain">ritiverse.in</span>
                      <span class="serp-url" id="serp_url">https://ritiverse.in</span>
                    </div>
                  </div>
                  <div class="serp-title" id="serp_title">
                    <?= e((string)($pages['home']['title'] ?? $global['site_title'])) ?>
                  </div>
                  <div class="serp-snippet" id="serp_snippet">
                    <?= e((string)($pages['home']['description'] ?? $global['meta_description'])) ?>
                  </div>
                </div>

                <!-- Social Card Preview -->
                <div class="social-card-mock">
                  <?php
                    $initialOg = (string)($pages['home']['og_image'] ?? $global['og_image'] ?? 'assets/images/cafe/hero-table.jpg');
                    $initialSrc = (str_starts_with($initialOg, 'http') || str_starts_with($initialOg, '/')) ? $initialOg : ('/' . ltrim($initialOg, '/'));
                  ?>
                  <img id="social_img" src="<?= e($initialSrc) ?>" class="social-card-img" alt="Social Preview" onerror="this.onerror=null; this.src='/assets/images/cafe/hero-table.jpg';">
                  <div class="social-card-meta">
                    <div class="social-card-domain" id="social_domain">ritiverse.in</div>
                    <div class="social-card-title" id="social_title"><?= e((string)($pages['home']['title'] ?? $global['site_title'])) ?></div>
                  </div>
                </div>

                <div style="margin-top:1.25rem; font-size:0.75rem; color:var(--text-muted); line-height:1.6; padding-top:1rem; border-top:1px dashed var(--border-warm);">
                  <strong>Zero-Downtime Cache Invalidation:</strong> Whenever you save here, the fast file/memory cache (<code>ritiverse_seo_data</code>) in <code>storage/cache/</code> is invalidated, so webmaster bots and visitors instantly receive the updated metadata.
                </div>

              </div>
            </div>

          </div>

        </div>

        <!-- Floating Save Bar -->
        <div class="floating-save-bar">
          <button type="submit" class="btn-save-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span>Save SEO &amp; Tracking Settings</span>
          </button>
        </div>

      </form>

    </main>

  </div>

  <script>
    let activePreviewKey = 'home';

    function toggleAccordion(pageKey) {
      const item = document.getElementById('accordion_' + pageKey);
      if (!item) return;
      const wasActive = item.classList.contains('active');
      
      // Close all
      document.querySelectorAll('.page-accordion-item').forEach(el => el.classList.remove('active'));
      
      // Open selected if not already open
      if (!wasActive) {
        item.classList.add('active');
        previewPage(pageKey);
      }
    }

    function previewPage(pageKey) {
      activePreviewKey = pageKey;
      const pageNames = {
        'home': 'Home Page (/)',
        'menu': 'Artisanal Menu (/menu)',
        'about': 'Our Story & Heritage (/about)',
        'events': 'Private Events (/events)',
        'gallery': 'Visual Diary (/gallery)',
        'contact': 'Contact & Hours (/contact)',
        'table': 'Digital Table Order (/table)'
      };

      document.getElementById('previewing_page_name').textContent = pageNames[pageKey] || pageKey;
      updateLiveSerpPreview();
    }

    function onPageInput(pageKey) {
      if (activePreviewKey === pageKey) {
        updateLiveSerpPreview();
      }
    }

    function updateLiveSerpPreview() {
      const baseCanonical = (document.getElementById('global_canonical_url').value || 'https://ritiverse.in').replace(/\/+$/, '');
      const globalTitle = document.getElementById('global_site_title').value || 'RiTiVerse | Heritage Cafe & Cultural Space · Vadodara, Gujarat';
      const globalDesc = document.getElementById('global_meta_desc').value || 'Experience warm hospitality and delicious homestyle cuisine at RiTiVerse, Vadodara, Gujarat.';
      const globalOg = document.getElementById('global_og_image').value || 'assets/images/cafe/hero-table.jpg';

      let pageTitle = '';
      let pageDesc = '';
      let pageOg = '';
      let pagePath = '';

      if (activePreviewKey && activePreviewKey !== 'global') {
        const titleEl = document.getElementById('page_title_' + activePreviewKey);
        const descEl = document.getElementById('page_desc_' + activePreviewKey);
        const ogEl = document.getElementById('page_og_' + activePreviewKey);
        
        pageTitle = titleEl ? titleEl.value : '';
        pageDesc = descEl ? descEl.value : '';
        pageOg = ogEl ? ogEl.value : '';
        pagePath = (activePreviewKey === 'home') ? '' : activePreviewKey;
      }

      const finalTitle = pageTitle || globalTitle;
      const finalDesc = pageDesc || globalDesc;
      const finalOg = pageOg || globalOg;
      const finalUrl = baseCanonical + (pagePath ? '/' + pagePath : '');

      let domainName = 'ritiverse.in';
      try {
        const u = new URL(baseCanonical);
        domainName = u.hostname;
      } catch (e) {}

      document.getElementById('serp_domain').textContent = domainName;
      document.getElementById('serp_url').textContent = finalUrl;
      document.getElementById('serp_title').textContent = finalTitle;
      document.getElementById('serp_snippet').textContent = finalDesc;

      document.getElementById('social_domain').textContent = domainName;
      document.getElementById('social_title').textContent = finalTitle;
      if (finalOg) {
        const resolvedOg = (finalOg.startsWith('http') || finalOg.startsWith('/')) ? finalOg : ('/' + finalOg);
        const sImg = document.getElementById('social_img');
        if (sImg && sImg.getAttribute('data-active-src') !== resolvedOg) {
          sImg.setAttribute('data-active-src', resolvedOg);
          sImg.src = resolvedOg;
        }
      }

      // Update char counts
      const titleLen = globalTitle.length;
      const descLen = globalDesc.length;
      document.getElementById('title_char_count').textContent = titleLen + ' chars';
      document.getElementById('desc_char_count').textContent = descLen + ' chars';
    }

    // Automatically clean full HTML meta tags to keep ONLY the unique token/code
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
      // Strip any accidental brackets or quotes
      v = v.replace(/<[^>]*>?/gm, '').replace(/["']/g, '').trim();
      if (input.value !== v) {
        input.value = v;
      }
    }

    // Initialize live preview on page load
    document.addEventListener('DOMContentLoaded', () => {
      updateLiveSerpPreview();
    });
  </script>

</body>
</html>
