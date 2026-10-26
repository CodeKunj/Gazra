<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Customer Checkout View
 * Allows customers to review items, choose payment method, and submit table order.
 */

require_once dirname(__DIR__) . '/includes/config.php';

use App\Services\TableSessionService;

$activeTable = TableSessionService::getActiveTable();
$activeUser = $_SESSION['cielo_user'] ?? null;

$pageTitle = "Checkout | RiTiVerse Cafe";
$pageDesc = "Review and confirm your dining order at RiTiVerse Cafe Vadodara.";
$pageCss = ['menu.css', 'cart-drawer.css', 'checkout.css'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" class="checkout-main">
  <div class="checkout-container">

    <!-- Breadcrumb & Table Info Header -->
    <div class="checkout-header-bar">
      <div>
        <a href="/menu" class="checkout-back-link">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Back to Menu
        </a>
        <h1 class="checkout-page-title">
          Review &amp; Place Order
        </h1>
      </div>

      <div class="checkout-table-badge">
        <span class="checkout-table-dot" aria-hidden="true"></span>
        <span class="checkout-table-num">
          <?= $activeTable ? "Table " . e($activeTable['table_number']) : "Counter / Takeaway" ?>
        </span>
        <span class="checkout-table-floor">
          <?= $activeTable ? e($activeTable['floor']) : "Guest Order" ?>
        </span>
      </div>
    </div>

    <!-- Empty Cart Notice (if navigated directly) -->
    <div id="checkoutEmptyWarning" class="checkout-empty-box" style="display: none;">
      <h2 class="checkout-empty-title">Your Cart is Empty</h2>
      <p class="checkout-empty-desc">Please choose delicious dishes from our artisanal menu before proceeding to checkout.</p>
      <a href="/menu" class="btn-primary" style="display: inline-block; padding: 0.85rem 1.75rem; border-radius: 999px; text-decoration: none; font-weight: 600;">
        Browse Menu
      </a>
    </div>

    <!-- Main Checkout Grid -->
    <div id="checkoutContentGrid">
      <form id="checkoutForm" onsubmit="handlePlaceOrder(event)" class="checkout-form-grid">

        <!-- Left: Customer Details & Payment Options -->
        <div class="checkout-fields-col">

          <!-- Section 1: Customer Contact -->
          <div class="checkout-card">
            <h2 class="checkout-card-title">
              <span>1. Your Contact Details</span>
            </h2>

            <div class="checkout-input-group">
              <label for="custPhone" class="checkout-label">
                Mobile Number <span class="checkout-req">*</span>
              </label>
              <div class="checkout-phone-wrapper">
                <span class="checkout-prefix">+91</span>
                <input type="tel" id="custPhone" name="phone" value="<?= e($activeUser['phone'] ?? '') ?>" placeholder="98765 43210" maxlength="10" required pattern="[6-9][0-9]{9}" inputmode="numeric" class="checkout-input">
              </div>
              <span class="checkout-hint">Used for live kitchen tracking SMS and instant loyalty points.</span>
            </div>

            <div class="checkout-input-group" style="margin-top: 1rem;">
              <label for="custName" class="checkout-label">
                Your Name <span style="font-weight: 400; color: var(--chk-muted);">(Optional)</span>
              </label>
              <input type="text" id="custName" name="name" value="<?= e($activeUser['name'] ?? '') ?>" placeholder="E.g. Dhrumil" class="checkout-input-boxed">
            </div>
          </div>

          <!-- Section 2: Payment Method -->
          <div class="checkout-card">
            <h2 class="checkout-card-title">
              <span>2. Select Payment Method</span>
            </h2>
            <p class="checkout-card-subtitle">Choose how you'd like to settle your order bill.</p>

            <div class="payment-methods-stack">
              <!-- Option A: Pay at Counter (Cash) -->
              <label class="payment-option-card is-selected" id="payOptCash">
                <input type="radio" name="payment_method" value="counter_cash" checked class="payment-radio" onchange="updatePaymentCardStyles()">
                <div>
                  <div class="payment-option-title">
                    <span>Pay at Counter (Cash)</span>
                    <span class="payment-badge-tag">Popular</span>
                  </div>
                  <div class="payment-option-desc">Settle bill with the cashier via cash when your order is served.</div>
                </div>
              </label>

              <!-- Option B: Pay via Counter UPI QR -->
              <label class="payment-option-card" id="payOptUpi">
                <input type="radio" name="payment_method" value="counter_upi" class="payment-radio" onchange="updatePaymentCardStyles()">
                <div>
                  <div class="payment-option-title">
                    <span>Pay via UPI / QR Code</span>
                    <span class="payment-badge-tag" style="background: rgba(107,154,196,0.15); color: #3F6E96;">GPay / PhonePe / Paytm</span>
                  </div>
                  <div class="payment-option-desc">Pay directly via your phone UPI app or scan our counter QR code.</div>
                </div>
              </label>

              <!-- Option C: Online Razorpay (Coming Soon) -->
              <div class="payment-option-card payment-option-disabled">
                <input type="radio" disabled class="payment-radio">
                <div>
                  <div class="payment-option-title" style="color: var(--chk-muted);">
                    <span>Online Cards &amp; NetBanking</span>
                    <span style="font-size: 0.68rem; background: var(--chk-terracotta); color:#FFF; padding: 0.15rem 0.5rem; border-radius: 4px;">Coming Soon</span>
                  </div>
                  <div class="payment-option-desc">Instant online gateway settlement with cards and net banking.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Kitchen Notes -->
          <div class="checkout-card">
            <h2 class="checkout-card-title">
              <span>3. Special Kitchen Instructions</span>
            </h2>
            <p class="checkout-card-subtitle">Allergies, spice adjustments, or custom instructions for our chef team.</p>
            <textarea id="checkoutNotes" rows="2" placeholder="E.g. Table prefers starters served together, please make marinara mild..." class="checkout-input-boxed" style="resize: vertical;"></textarea>
          </div>
        </div>

        <!-- Right: Order Summary & Checkout CTA -->
        <div class="checkout-summary-card">
          <h2 class="checkout-summary-title">
            <span>Order Summary</span>
            <span id="chkItemsCount" style="font-size: 0.85rem; font-family: var(--font-sans); color: var(--chk-muted); font-weight: 500;"></span>
          </h2>

          <!-- Items List Snapshot -->
          <div id="checkoutItemsList" class="checkout-items-list"></div>

          <!-- Financial Breakdown -->
          <div class="checkout-fin-breakdown">
            <div class="checkout-fin-row">
              <span>Subtotal</span>
              <span id="chkSubtotal" class="checkout-fin-val">₹ 0.00</span>
            </div>

            <div id="chkDiscountRow" class="checkout-fin-row" style="display: none; color: #2E7D32; font-weight: 600;">
              <span>Promo Discount</span>
              <span id="chkDiscount">- ₹ 0.00</span>
            </div>

            <div id="chkRewardRow" class="checkout-fin-row" style="display: none; color: var(--chk-terracotta); font-weight: 600;">
              <span id="chkRewardLabel">Milestone Reward</span>
              <span id="chkRewardVal">- ₹ 0.00</span>
            </div>

            <div class="checkout-fin-row">
              <span>Restaurant GST (5%)</span>
              <span id="chkTax" class="checkout-fin-val">₹ 0.00</span>
            </div>

            <div class="checkout-grand-total-row">
              <span>Total Payable</span>
              <span id="chkGrandTotal">₹ 0.00</span>
            </div>
          </div>

          <div id="checkoutErrMsg" class="checkout-error-banner" style="display: none;"></div>

          <button type="submit" id="btnPlaceOrder" class="btn-place-order">
            <span>Confirm &amp; Place Order</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>

      </form>
    </div>

  </div>
</main>

<script>
function updatePaymentCardStyles() {
  const method = document.querySelector('input[name="payment_method"]:checked')?.value || 'counter_cash';
  const cashOpt = document.getElementById('payOptCash');
  const upiOpt = document.getElementById('payOptUpi');
  if (cashOpt) cashOpt.classList.toggle('is-selected', method === 'counter_cash');
  if (upiOpt) upiOpt.classList.toggle('is-selected', method === 'counter_upi');
}

document.addEventListener('DOMContentLoaded', async () => {
  const rawCart = localStorage.getItem('cielo_cart_v1');
  const cart = rawCart ? JSON.parse(rawCart) : null;

  if (!cart || !Array.isArray(cart.items) || cart.items.length === 0) {
    document.getElementById('checkoutContentGrid').style.display = 'none';
    document.getElementById('checkoutEmptyWarning').style.display = 'block';
    return;
  }

  // Validate cart with server
  try {
    const res = await fetch('/api/cart_validate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        items: cart.items.map(i => ({
          id: i.id,
          variant_id: i.variantId,
          addon_ids: i.addonIds,
          quantity: i.quantity,
          note: i.note
        })),
        coupon_code: cart.couponCode || '',
        reward_id: cart.rewardId || null,
        redeem_points: cart.redeemPoints || 0
      })
    });
    const result = await res.json();

    if (result.success) {
      renderCheckoutSummary(result.data.line_items, result.data.financials);
    }
  } catch (err) {
    console.error('Error validating cart for checkout:', err);
  }
});

function renderCheckoutSummary(lineItems, fin) {
  const listEl = document.getElementById('checkoutItemsList');
  const countEl = document.getElementById('chkItemsCount');
  if (countEl) countEl.textContent = `(${lineItems.length} item${lineItems.length === 1 ? '' : 's'})`;

  listEl.innerHTML = lineItems.map(item => {
    const customs = [];
    if (item.variant_name) customs.push(item.variant_name);
    if (item.addons && item.addons.length > 0) {
      customs.push(item.addons.map(a => a.name).join(', '));
    }
    const price = (item.line_total_paise / 100).toFixed(2);

    return `
      <div class="checkout-item-row">
        <div>
          <div class="checkout-item-name">${item.quantity}x ${item.name}</div>
          ${customs.length > 0 ? `<div class="checkout-item-customs">${customs.join(' · ')}</div>` : ''}
          ${item.note ? `<div class="checkout-item-note">"${item.note}"</div>` : ''}
        </div>
        <div class="checkout-item-price">₹ ${price}</div>
      </div>
    `;
  }).join('');

  document.getElementById('chkSubtotal').textContent = `₹ ${fin.subtotal_inr.toFixed(2)}`;

  if (fin.total_discount_paise > 0) {
    document.getElementById('chkDiscountRow').style.display = 'flex';
    document.getElementById('chkDiscount').textContent = `- ₹ ${(fin.total_discount_paise / 100).toFixed(2)}`;
  }

  if (fin.applied_reward && fin.reward_discount_paise > 0) {
    document.getElementById('chkRewardRow').style.display = 'flex';
    document.getElementById('chkRewardLabel').textContent = `★ ${fin.applied_reward.title}`;
    document.getElementById('chkRewardVal').textContent = `- ₹ ${(fin.reward_discount_paise / 100).toFixed(2)}`;
  } else {
    document.getElementById('chkRewardRow').style.display = 'none';
  }

  document.getElementById('chkTax').textContent = `₹ ${(fin.total_tax_paise / 100).toFixed(2)}`;
  document.getElementById('chkGrandTotal').textContent = `₹ ${fin.grand_total_inr.toFixed(2)}`;
}

async function handlePlaceOrder(event) {
  event.preventDefault();

  const phone = document.getElementById('custPhone').value.trim();
  const name = document.getElementById('custName').value.trim();
  const notes = document.getElementById('checkoutNotes').value.trim();
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'counter_cash';

  const rawCart = localStorage.getItem('cielo_cart_v1');
  const cart = rawCart ? JSON.parse(rawCart) : null;

  if (!cart || !cart.items || cart.items.length === 0) {
    alert('Your cart is empty.');
    window.location.href = '/menu.php';
    return;
  }

  const btn = document.getElementById('btnPlaceOrder');
  const errEl = document.getElementById('checkoutErrMsg');
  btn.disabled = true;
  btn.innerHTML = 'Placing Order...';
  errEl.style.display = 'none';

  try {
    const res = await fetch('/api/order_create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone,
        name,
        payment_method: paymentMethod,
        kitchen_notes: notes,
        coupon_code: cart.couponCode || '',
        reward_id: cart.rewardId || null,
        redeem_points: cart.redeemPoints || 0,
        items: cart.items.map(i => ({
          id: i.id,
          variant_id: i.variantId,
          addon_ids: i.addonIds,
          quantity: i.quantity,
          note: i.note
        }))
      })
    });
    const result = await res.json();

    if (result.success) {
      // Clear localStorage cart
      localStorage.removeItem('cielo_cart_v1');
      // Redirect to live tracking screen
      window.location.href = result.data.redirect_url;
    } else {
      errEl.textContent = result.message || 'Failed to place order. Please try again.';
      errEl.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = '<span>Confirm & Place Order</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    }
  } catch (err) {
    errEl.textContent = 'Network error while placing order. Please check connection.';
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<span>Confirm & Place Order</span>';
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
