<?php
/**
 * RiTiVerse Cafe - Our Story & Philosophy Page
 */
require_once dirname(__DIR__) . '/includes/config.php';

$pageTitle = "Our Story | RiTiVerse Cafe, Vadodara, Gujarat";
$pageDesc = "Discover the story behind RiTiVerse Cafe. A welcoming dining space and cultural sanctuary located in Vadodara, Gujarat.";
$pageCss = ['home.css', 'about.css'];
$pageJs = [];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem;">
  <!-- About Hero Banner -->
  <section class="page-hero-banner">
    <div class="container">
      <span class="eyebrow" style="color: var(--accent-sky);">OUR PHILOSOPHY</span>
      <h1 class="page-hero-title">Where Heritage, Inclusion &amp; Flavour Bloom</h1>
      <p class="page-hero-subtitle">
        Situated in Vadodara, Gujarat, RiTiVerse Cafe is an unhurried sanctuary where authentic homestyle recipes, freshly brewed coffee, and heartwarming conversations flourish.
      </p>
    </div>
  </section>

  <!-- Story & Ingredients Split Sections -->
  <div class="container split-sections">
    <section id="story" class="split-row">
      <div class="split-col-7 split-text-content">
        <span class="eyebrow" style="color: var(--accent-sky-deep);">
          <?= e($business['story']['eyebrow']) ?>
        </span>
        <h2 class="split-heading">
          <?= e($business['story']['heading']) ?>
        </h2>
        <div class="split-paragraphs">
          <p><?= e($business['story']['paragraph1']) ?></p>
          <p><?= e($business['story']['paragraph2']) ?></p>
        </div>
        <div class="quote-badge">
          <div class="quote-line"></div>
          <blockquote class="quote-text">
            &ldquo;<?= e($business['story']['quote']) ?>&rdquo;
          </blockquote>
        </div>
      </div>

      <div class="split-col-5">
        <div class="split-image-card image-zoom-container">
          <img src="assets/images/cafe/interior-atmosphere.jpg" alt="RiTiVerse Cafe Interior and Ambient Dining Vibe">
        </div>
      </div>
    </section>

    <!-- Core Values Cards Grid -->
    <div>
      <div style="text-align: center; max-width: 32rem; margin: 0 auto 1.5rem auto;">
        <span class="eyebrow" style="color: var(--accent-terracotta);">CRAFT &amp; CARE</span>
        <h3 class="font-serif" style="font-size: 2.25rem; color: var(--text-espresso); margin-top: 0.25rem;">
          The Pillars of RiTiVerse
        </h3>
      </div>

      <div class="about-values-grid">
        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 21h18M3 10h18M5 10v11M19 10v11M9 10v11M15 10v11M12 2 2 7h20L12 2z"/>
            </svg>
          </div>
          <h4 class="about-value-title">Heritage &amp; Royal Legacy</h4>
          <p class="about-value-desc">
            Nestled in Vadodara, Gujarat, our cafe honors the city's rich legacy of women empowerment and royal progressive heritage.
          </p>
        </div>

        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
          </div>
          <h4 class="about-value-title">Queer-Led Radical Inclusion</h4>
          <p class="about-value-desc">
            As Gujarat's pioneering queer-led culinary enterprise, we champion equal dignity, safety, and empowerment for the LGBTQIA+ community and all patrons alike.
          </p>
        </div>

        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/>
            </svg>
          </div>
          <h4 class="about-value-title">Homestyle Comfort &amp; Fresh Coffee</h4>
          <p class="about-value-desc">
            From fragrant Mogra Shikanji and South Indian filter coffee to authentic Thepla Nachos and Misal Pav, every dish is prepared with authentic homestyle love.
          </p>
        </div>
      </div>
    </div>

    <!-- Fresh Ingredients Section -->
    <section class="split-row reverse-desktop">
      <div class="split-col-5">
        <div class="split-image-card aspect-4-5 image-zoom-container">
          <img src="assets/images/cafe/all-day-spread.jpg" alt="Fresh Homestyle Dishes, Snacks and Coffee Spread at RiTiVerse Cafe">
          <div class="farm-fresh-tag">Farm Fresh</div>
        </div>
      </div>

      <div class="split-col-7 split-text-content">
        <span class="eyebrow" style="color: var(--accent-terracotta);">
          <?= e($business['qualityIngredients']['eyebrow']) ?>
        </span>
        <h2 class="split-heading">
          <?= e($business['qualityIngredients']['heading']) ?>
        </h2>
        <p class="split-paragraphs">
          <?= e($business['qualityIngredients']['paragraph']) ?>
        </p>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <?php foreach ($business['qualityIngredients']['stats'] as $st): ?>
            <div class="stat-box">
              <span class="stat-box-value"><?= e($st['value']) ?></span>
              <span class="stat-box-label"><?= e($st['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="padding-top: 1rem;">
          <button type="button" class="btn-terracotta btn-open-reservation">
            Reserve Your Experience
          </button>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
