<?php
/**
 * Cielo Cafe and Restro - Global Header Template
 */
require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? ($business['name'] . ' | Cafe & Restaurant in Vadodara');
$pageDesc = $pageDesc ?? $business['description'];
$openStatus = isCieloOpen();

$requestPath = trim(strtolower((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
$isHome = ($requestPath === '' || $requestPath === 'home' || $requestPath === 'index.php');
$headerThemeClass = $isHome ? 'theme-dark-bg' : 'theme-light-bg';

// Determine or default page key for per-page SEO
if (empty($pageKey)) {
    $pageKey = match (true) {
        $isHome => 'home',
        $requestPath === 'menu' || $requestPath === 'menu.php' => 'menu',
        $requestPath === 'about' || $requestPath === 'about.php' || $requestPath === 'story' => 'about',
        $requestPath === 'contact' || $requestPath === 'contact.php' => 'contact',
        $requestPath === 'events' || $requestPath === 'events.php' => 'events',
        $requestPath === 'gallery' || $requestPath === 'gallery.php' => 'gallery',
        $requestPath === 'checkout' || $requestPath === 'checkout.php' => 'checkout',
        $requestPath === 'table' || $requestPath === 'table.php' => 'table',
        str_starts_with($requestPath, 'order') => 'order_status',
        default => 'home'
    };
}

// Fetch Global & Per-Page SEO Data (Database + Fast Runtime Cache)
$seo = \App\Services\AppDataService::getSeo($pageKey, $pageTitle ?? null, $pageDesc ?? null);
$pageTitle = $seo['title'];
$pageDesc = $seo['description'];
$openStatus = isCieloOpen();

// Schema.org Restaurant / Cafe JSON-LD
$schemaData = [
    "@context" => "https://schema.org",
    "@type" => ["Restaurant", "CafeOrCoffeeShop"],
    "name" => $business['name'],
    "description" => $seo['description'],
    "image" => $seo['og_image'],
    "telephone" => $business['phone'],
    "email" => $business['email'],
    "url" => $seo['canonical'],
    "priceRange" => "₹₹",
    "servesCuisine" => ["Specialty Coffee", "Artisanal Sourdough Pizza", "Handcrafted Pasta", "European Cafe Plates", "Artisan Bakery", "Beverages"],
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => $business['address']['line1'] . ', ' . $business['address']['line2'],
        "addressLocality" => $business['address']['city'],
        "addressRegion" => $business['address']['state'],
        "postalCode" => $business['address']['postalCode'],
        "addressCountry" => "IN"
    ],
    "geo" => [
        "@type" => "GeoCoordinates",
        "latitude" => $business['coordinates']['lat'],
        "longitude" => $business['coordinates']['lng']
    ],
    "openingHoursSpecification" => [
        [
            "@type" => "OpeningHoursSpecification",
            "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            "opens" => "11:00",
            "closes" => "22:30"
        ]
    ],
    "hasMap" => $business['googleMapsUrl']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  
  <!-- Primary Global & Per-Page SEO -->
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <?php if (!empty($seo['keywords'])): ?>
    <meta name="keywords" content="<?= e($seo['keywords']) ?>">
  <?php endif; ?>
  <meta name="robots" content="<?= e($seo['robots'] ?? 'index, follow') ?>">
  <link rel="canonical" href="<?= e($seo['canonical']) ?>">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($seo['canonical']) ?>">
  <meta property="og:title" content="<?= e($seo['og_title']) ?>">
  <meta property="og:description" content="<?= e($seo['og_description']) ?>">
  <meta property="og:image" content="<?= e($seo['og_image']) ?>">
  <meta property="og:site_name" content="<?= e($business['name']) ?>">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= e($seo['canonical']) ?>">
  <meta name="twitter:title" content="<?= e($seo['twitter_title']) ?>">
  <meta name="twitter:description" content="<?= e($seo['twitter_description']) ?>">
  <meta name="twitter:image" content="<?= e($seo['twitter_image']) ?>">

  <!-- Search Engine Webmaster Verifications -->
  <?php if (!empty($seo['google_site_verification'])): ?>
    <!-- Google Search Console -->
    <meta name="google-site-verification" content="<?= e($seo['google_site_verification']) ?>">
  <?php endif; ?>
  <?php if (!empty($seo['bing_site_verification'])): ?>
    <!-- Bing Webmaster Tools / Search Console -->
    <meta name="msvalidate.01" content="<?= e($seo['bing_site_verification']) ?>">
  <?php endif; ?>

  <!-- Google Analytics (GA4) -->
  <?php if (!empty($seo['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= urlencode($seo['google_analytics_id']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= e($seo['google_analytics_id']) ?>');
    </script>
  <?php endif; ?>

  <!-- Meta Pixel (Facebook Pixel) -->
  <?php if (!empty($seo['meta_pixel_id'])): ?>
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '<?= e($seo['meta_pixel_id']) ?>');
      fbq('track', 'PageView');
    </script>
    <noscript>
      <img height="1" width="1" style="display:none"
           src="https://www.facebook.com/tr?id=<?= urlencode($seo['meta_pixel_id']) ?>&ev=PageView&noscript=1"/>
    </noscript>
  <?php endif; ?>

  <!-- Custom Header Scripts -->
  <?php if (!empty($seo['custom_head_code'])): ?>
    <?= $seo['custom_head_code'] ?>
  <?php endif; ?>

  <!-- Structured Data JSON-LD -->
  <script type="application/ld+json">
    <?= json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>

  <!-- Core Base & Component CSS -->
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/footer.css">
  <link rel="stylesheet" href="assets/css/modal.css">

  <!-- Page-Specific CSS Files -->
  <?php if (!empty($pageCss) && is_array($pageCss)): ?>
    <?php foreach ($pageCss as $cssFile): ?>
      <link rel="stylesheet" href="assets/css/<?= e($cssFile) ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body class="warm-contrast readable-font">
  <!-- Full-Screen Cinematic Loading Transition Screen -->
  <div id="cieloTransitionScreen" class="cielo-transition-screen" aria-hidden="true">
    <div class="transition-screen-inner">
      <div class="transition-brand-wrap">
        <span class="transition-brand-name">RiTiVerse</span>
        <span class="transition-brand-tagline">Heritage Cafe &amp; Cultural Space · Vadodara, Gujarat</span>
      </div>
      <div class="transition-progress-line" aria-hidden="true">
        <span class="transition-progress-bar"></span>
      </div>
    </div>
  </div>
  <script>
    (function() {
      try {
        if (sessionStorage.getItem('cielo_page_transition') === '1') {
          var s = document.getElementById('cieloTransitionScreen');
          if (s) {
            s.className = 'cielo-transition-screen state-hold';
          }
        }
      } catch (e) {}
    })();
  </script>

  <!-- Global Header (Villa Aurelia Minimalist Cinematic Style) -->
  <header class="cielo-header <?= $headerThemeClass ?>">
    <div class="cielo-navbar">
      <div class="container navbar-content">
        <!-- Logo in Editorial Fraunces Serif -->
        <a href="/" class="brand-logo" aria-label="RiTiVerse Homepage">
          <span class="brand-title">RiTiVerse</span>
        </a>

        <!-- Header Right Controls -->
        <div class="navbar-actions">
          <!-- Active Table Indicator Pill -->
          <?php
          $headerActiveTable = class_exists(\App\Services\TableSessionService::class)
              ? \App\Services\TableSessionService::getActiveTable()
              : null;
          ?>
          <?php if ($headerActiveTable): ?>
            <a href="/menu" class="header-table-badge-pill" title="Active Table <?= e($headerActiveTable['table_number']) ?> Session">
              <span class="table-pulse-dot" aria-hidden="true"></span>
              <span>T-<?= e($headerActiveTable['table_number']) ?></span>
            </a>
          <?php endif; ?>

          <!-- Patron Club Pill Button -->
          <?php
          $patronUser = class_exists(\App\Services\CustomerAuthService::class) 
              ? \App\Services\CustomerAuthService::getCurrentUser() 
              : null;
          ?>
          <a href="/profile" class="header-pill-btn header-patron-pill" style="text-decoration: none; <?= $patronUser ? 'border-color: var(--accent-terracotta); color: var(--accent-terracotta); background: rgba(201,106,82,0.1);' : '' ?>">
            <span><?= $patronUser ? ('★ ' . e(explode(' ', $patronUser['name'] ?: 'Patron')[0])) : 'Patron Club' ?></span>
          </a>

          <!-- Reserve a Table Pill Button -->
          <button type="button" class="header-pill-btn btn-open-reservation">
            Reserve a table
          </button>

          <!-- Minimalist Menu Trigger -->
          <button type="button" id="openDrawerBtn" class="header-menu-btn" aria-label="Open navigation menu">
            <span class="header-menu-text">Menu</span>
            <span class="header-menu-icon" aria-hidden="true">
              <span class="menu-bar-top"></span>
              <span class="menu-bar-bot"></span>
            </span>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Slide-In Navigation Drawer (Numbered Editorial Style) -->
  <div id="mobileDrawer" class="mobile-drawer-overlay" role="dialog" aria-modal="true" aria-label="Main Navigation">
    <div>
      <div class="drawer-header">
        <div>
          <a href="/" class="brand-logo drawer-brand-logo" aria-label="RiTiVerse Homepage" style="text-decoration: none; color: inherit; display: block;">
            <span class="font-display" style="font-size: 2rem; font-weight: 350; letter-spacing: -0.02em; color: var(--bg-cream);">RiTiVerse</span>
            <span style="font-size: 0.625rem; letter-spacing: 0.16em; color: var(--accent-sky); text-transform: uppercase; display: block; margin-top: 0.2rem;">Heritage Cafe &amp; Cultural Space · Vadodara, Gujarat</span>
          </a>
        </div>
        <button type="button" id="closeDrawerBtn" class="drawer-close" aria-label="Close navigation menu">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <nav class="drawer-nav">
        <?php if ($headerActiveTable): ?>
          <a href="/menu" class="drawer-nav-link" style="color: #2E7D32; font-weight: 700;">
            <span class="drawer-nav-num">●</span>
            <span class="drawer-nav-label">Your Table <?= e($headerActiveTable['table_number']) ?> (Active)</span>
          </a>
        <?php endif; ?>
        <a href="/profile" class="drawer-nav-link" style="color: var(--accent-terracotta);">
          <span class="drawer-nav-num">★</span>
          <span class="drawer-nav-label"><?= $patronUser ? ('Rewards (' . e($patronUser['name']) . ')') : 'Patron Club & Login' ?></span>
        </a>
        <a href="/menu" class="drawer-nav-link">
          <span class="drawer-nav-num">01</span>
          <span class="drawer-nav-label">Menu</span>
        </a>
        <a href="/#story" class="drawer-nav-link">
          <span class="drawer-nav-num">02</span>
          <span class="drawer-nav-label">Our Story</span>
        </a>
        <a href="/#celebrations" class="drawer-nav-link">
          <span class="drawer-nav-num">03</span>
          <span class="drawer-nav-label">Events & DJ</span>
        </a>
        <a href="/#gallery" class="drawer-nav-link">
          <span class="drawer-nav-num">04</span>
          <span class="drawer-nav-label">Gallery</span>
        </a>
        <a href="/#reviews" class="drawer-nav-link">
          <span class="drawer-nav-num">05</span>
          <span class="drawer-nav-label">Reviews</span>
        </a>
        <a href="/#location" class="drawer-nav-link">
          <span class="drawer-nav-num">06</span>
          <span class="drawer-nav-label">Visit Us</span>
        </a>
      </nav>
    </div>

    <div class="drawer-footer">
      <button type="button" class="btn-terracotta btn-open-reservation" style="width: 100%; padding: 0.9rem;">
        Reserve a Table
      </button>
      <a href="tel:<?= e($business['phoneRaw']) ?>" class="btn-outline" style="border-color: rgba(246,239,227,0.25); color: var(--bg-cream); width: 100%; padding: 0.8rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-sky);">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
        Call <?= e($business['phone']) ?>
      </a>
      <p style="text-align: center; font-size: 0.625rem; color: rgba(246,239,227,0.6); letter-spacing: 0.05em;">
        <?= e($business['address']['full']) ?>
      </p>
    </div>
  </div>
