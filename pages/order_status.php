<?php

declare(strict_types=1);

/**
 * RiTiVerse Cafe - Live Order Tracking Page
 * Displays real-time 4-step kitchen progress, receipt breakdown, and counter payment directions.
 */

require_once dirname(__DIR__) . '/includes/config.php';

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Setting;
use App\Core\Money;
use App\Core\QrCodeGenerator;

$orderNumber = trim((string)($_GET['order'] ?? ''));

$order = null;
if ($orderNumber !== '') {
    $order = Order::findBy('order_number', $orderNumber);
}

$pageTitle = "Order Tracking | RiTiVerse Cafe";
$pageDesc = "Track your live kitchen order status and settle payments at RiTiVerse Cafe Vadodara.";
$pageCss = ['menu.css', 'order-status.css'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" class="order-tracking-main">
  <div class="order-tracking-container">

    <?php if (!$order): ?>
      <!-- Order Not Found Screen -->
      <div class="order-tracking-card" style="text-align: center; padding: 3rem 1.5rem;">
        <h2 style="font-family: var(--font-display, 'Fraunces', serif); font-size: 1.5rem; color: var(--trk-espresso); margin: 0 0 0.75rem;">
          Order Not Found
        </h2>
        <p style="color: var(--trk-muted); margin: 0 0 1.5rem; font-size: 0.9rem;">
          We could not locate this order. Please check your tracking link or scan your table QR code.
        </p>
        <a href="/menu" class="btn-primary" style="display: inline-block; padding: 0.85rem 1.75rem; border-radius: 999px; text-decoration: none; font-weight: 600;">
          Return to Menu
        </a>
      </div>
    <?php else: ?>
      <?php
        $table = Table::find((int)$order['table_id']);
        $items = OrderItem::where('`order_id` = :oid', ['oid' => (int)$order['id']]);
        $status = (string)$order['status'];

        $upiId = class_exists(Setting::class) ? Setting::get('payment_upi_id', '8200306871@okbizaxis') : '8200306871@okbizaxis';
        $upiAmount = number_format(Money::toRupees((int)$order['total_paise']), 2, '.', '');
        $upiUri = "upi://pay?pa={$upiId}&pn=" . urlencode('RiTiVerse Cafe') . "&am={$upiAmount}&cu=INR&tn=" . urlencode("Order " . $order['order_number']);
        $upiQrSvg = class_exists(QrCodeGenerator::class) ? QrCodeGenerator::generateSvg($upiUri, 150, '#1A1412', '#FFFFFF', 1) : '';
      ?>

      <!-- Order Tracking Card -->
      <div class="order-tracking-card">
        
        <!-- Top Order Info -->
        <div class="order-header-bar">
          <div>
            <span class="order-live-tag">LIVE KITCHEN STATUS</span>
            <h1 class="order-number-title">
              <?= e($order['order_number']) ?>
            </h1>
            <span class="order-time-meta">
              Placed at <?= date('h:i A', strtotime($order['created_at'])) ?> · <?= date('M d, Y', strtotime($order['created_at'])) ?>
            </span>
          </div>

          <div>
            <span class="order-table-pill">
              <span class="live-pulse-dot" style="margin-right: 0.2rem;"></span>
              <span>Table <?= e($table['table_number'] ?? 'Counter') ?> · <?= e($table['floor'] ?? 'Ground Floor') ?></span>
            </span>
          </div>
        </div>

        <!-- 4-Step Visual Progress Stepper -->
        <div class="order-stepper-box">
          <div id="statusTrackerGrid" class="status-tracker-grid">

            <!-- Step 1: Placed -->
            <div class="step-node" data-step="1">
              <div class="step-circle is-active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div class="step-label">Received</div>
            </div>

            <!-- Step 2: Preparing -->
            <div class="step-node" data-step="2">
              <div class="step-circle <?= in_array($status, ['preparing', 'ready', 'completed']) ? 'is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c2 3 5 4 5 8a5 5 0 0 1-10 0c0-4 3-5 5-8Z"/><path d="M12 18v4"/><path d="M8 22h8"/></svg>
              </div>
              <div class="step-label">Cooking</div>
            </div>

            <!-- Step 3: Ready -->
            <div class="step-node" data-step="3">
              <div class="step-circle <?= in_array($status, ['ready', 'completed']) ? 'is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              </div>
              <div class="step-label">Ready</div>
            </div>

            <!-- Step 4: Completed -->
            <div class="step-node" data-step="4">
              <div class="step-circle <?= ($status === 'completed') ? 'is-active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              </div>
              <div class="step-label">Served</div>
            </div>
          </div>

          <div id="liveStatusBanner" class="live-status-banner">
            <span class="live-pulse-dot"></span>
            <span id="liveStatusText">
              <?php if ($status === 'placed'): ?>
                Kitchen received your order. Preparing fresh ingredients...
              <?php elseif ($status === 'preparing'): ?>
                Chef is freshly cooking your dishes in the kitchen!
              <?php elseif ($status === 'ready'): ?>
                Your order is freshly plated and on its way to your table!
              <?php elseif ($status === 'completed'): ?>
                Order complete! Hope you enjoyed your dining experience.
              <?php else: ?>
                Status: <?= ucfirst($status) ?>
              <?php endif; ?>
            </span>
          </div>
        </div>

        <!-- Payment Instructions & Live Settlement Box -->
        <div class="order-payment-box">
          <div class="payment-box-top-row">
            <div>
              <div class="payment-status-label">Payment Status</div>
              <div class="payment-status-val">
                <?php if ($order['payment_status'] === 'paid'): ?>
                  <span style="color: #2E7D32;">✔ Settled &amp; Paid</span>
                <?php else: ?>
                  <span style="color: var(--trk-terracotta);">Pending Settlement (Cash / UPI)</span>
                <?php endif; ?>
              </div>
            </div>

            <div style="text-align: right;">
              <div class="payment-status-label">Total Amount</div>
              <div class="payment-total-amount">
                <?= Money::format((int)$order['total_paise']) ?>
              </div>
            </div>
          </div>

          <?php if ($order['payment_status'] !== 'paid'): ?>
            <!-- Mobile 1-Tap UPI Settlement Card -->
            <div class="mobile-upi-pay-card">
              <div style="font-size: 0.82rem; color: var(--trk-muted); line-height: 1.4;">
                Settle directly on your phone via any UPI app, or pay cash/card at counter with order <strong><?= e($order['order_number']) ?></strong>.
              </div>

              <!-- 1-Tap UPI Deep link for Mobile Phones -->
              <a href="<?= e($upiUri) ?>" class="btn-upi-mobile-pay" id="btnUpiAppPay" target="_blank" rel="noopener">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span>Pay ₹<?= $upiAmount ?> via UPI (GPay / PhonePe / Paytm)</span>
              </a>

              <?php if (!empty($upiQrSvg)): ?>
                <button type="button" class="btn-qr-accordion-toggle" onclick="toggleUpiQrDisplay()">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01"/></svg>
                  <span id="qrToggleText">Show Counter UPI QR Code</span>
                </button>

                <div id="upiQrWrap" class="upi-qr-display-wrap" style="display: none;">
                  <?= $upiQrSvg ?>
                  <span style="font-size: 0.75rem; color: var(--trk-muted);">Scan using Google Pay, PhonePe, Paytm, or CRED</span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Itemized Receipt -->
        <div>
          <h2 class="receipt-section-title">
            Order Items (<?= count($items) ?>)
          </h2>

          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($items as $it): ?>
              <?php 
                $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
              ?>
              <div class="receipt-item-row">
                <div>
                  <div class="receipt-item-title">
                    <?= (int)$it['quantity'] ?>x <?= e($it['item_name']) ?>
                  </div>
                  <?php if (!empty($it['variant_name'])): ?>
                    <div class="receipt-item-customs"><?= e($it['variant_name']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($addons)): ?>
                    <div class="receipt-item-customs" style="color: var(--trk-sky);">
                      <?= implode(', ', array_map(fn($a) => '+ ' . e($a['name']), $addons)) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($it['item_note'])): ?>
                    <div class="receipt-item-note">
                      "<?= e($it['item_note']) ?>"
                    </div>
                  <?php endif; ?>
                </div>

                <div class="receipt-item-price">
                  <?= Money::format((int)$it['total_price_paise']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Financial Breakdown Summary -->
          <div class="receipt-breakdown">
            <div class="receipt-row">
              <span>Subtotal</span>
              <span><?= Money::format((int)$order['subtotal_paise']) ?></span>
            </div>
            <?php if ((int)$order['discount_paise'] > 0): ?>
              <div class="receipt-row" style="color: #2E7D32; font-weight: 600;">
                <span>Discount Applied</span>
                <span>- <?= Money::format((int)$order['discount_paise']) ?></span>
              </div>
            <?php endif; ?>
            <div class="receipt-row">
              <span>Restaurant GST (5%)</span>
              <span><?= Money::format((int)$order['tax_paise']) ?></span>
            </div>
            <div class="receipt-grand-total">
              <span>Grand Total</span>
              <span><?= Money::format((int)$order['total_paise']) ?></span>
            </div>
          </div>
        </div>

        <!-- Bottom Actions -->
        <div class="order-bottom-actions">
          <a href="/menu" class="btn-order-more">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 0.35rem;"><path d="M12 5v14M5 12h14"/></svg>
            <span>Order More Dishes</span>
          </a>
          <?php if ($status === 'completed'): ?>
            <a href="/profile" class="btn-order-review">
              <span>★</span>
              <span>Review Order (+50 Pts)</span>
            </a>
          <?php endif; ?>
        </div>

      </div>

    <?php endif; ?>

  </div>
</main>

<script>
function toggleUpiQrDisplay() {
  const el = document.getElementById('upiQrWrap');
  const txt = document.getElementById('qrToggleText');
  if (!el) return;
  if (el.style.display === 'none') {
    el.style.display = 'flex';
    if (txt) txt.textContent = 'Hide Counter UPI QR Code';
  } else {
    el.style.display = 'none';
    if (txt) txt.textContent = 'Show Counter UPI QR Code';
  }
}

// Live Status Polling Engine (Every 5 seconds)
const orderNum = "<?= e($orderNumber) ?>";
if (orderNum) {
  setInterval(async () => {
    try {
      const res = await fetch(`/api/order_status.php?order=${encodeURIComponent(orderNum)}`);
      const data = await res.json();
      if (data.success) {
        updateLiveStatusUI(data.data);
      }
    } catch (e) {
      console.warn('Polling error:', e);
    }
  }, 5000);
}

function updateLiveStatusUI(orderData) {
  const stepIdx = orderData.step_index;
  document.querySelectorAll('.step-node').forEach(node => {
    const s = Number(node.getAttribute('data-step'));
    const circle = node.querySelector('.step-circle');
    if (circle) {
      if (s <= stepIdx) {
        circle.classList.add('is-active');
      } else {
        circle.classList.remove('is-active');
      }
    }
  });

  const txtEl = document.getElementById('liveStatusText');
  if (txtEl) {
    if (orderData.status === 'placed') {
      txtEl.textContent = 'Kitchen received your order. Preparing fresh ingredients...';
    } else if (orderData.status === 'preparing') {
      txtEl.textContent = 'Chef is freshly cooking your dishes in the kitchen!';
    } else if (orderData.status === 'ready') {
      txtEl.textContent = 'Your order is freshly plated and on its way to your table!';
    } else if (orderData.status === 'completed') {
      txtEl.textContent = 'Order complete! Hope you enjoyed your dining experience.';
    }
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
