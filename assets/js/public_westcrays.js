/* ═══════════════════════════════════════════
   WEST CRAYS — Public Ordering Page JS
   DB columns: customer_id, status_id, quantity_kg,
               price_per_kg, total_amount, pickup_date
   ═══════════════════════════════════════════ */

const PRICE_PER_KG = (window.__crayfishPrice !== undefined) ? parseFloat(window.__crayfishPrice) : 120;
const MIN_ORDER_KG = (window.__crayfishMin !== undefined) ? parseFloat(window.__crayfishMin) : 0.5;
const MAX_ORDER_KG = (window.__crayfishMax !== undefined) ? parseFloat(window.__crayfishMax) : 100;

let cart = { weight: 0 };
let editingWeight = 0;

// ── Helpers ──
function fmt(amount) {
  return '₱' + amount.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

// ── Weight input ──
function setWeight(w) {
  const inp = document.getElementById('wcWeightInput');
  if (inp) { inp.value = w; onWeightInput(); }
}

function adjustWeight(delta) {
  const inp = document.getElementById('wcWeightInput');
  let v = parseFloat(inp.value) || 0;
  v = Math.max(MIN_ORDER_KG, Math.min(MAX_ORDER_KG, Math.round((v + delta) * 2) / 2));
  inp.value = v;
  onWeightInput();
}

function onWeightInput() {
  const inp  = document.getElementById('wcWeightInput');
  const btn  = document.getElementById('wcAddBtn');
  const box  = document.getElementById('wcLiveEstimate');
  const amt  = document.getElementById('wcEstimateAmt');
  const w    = parseFloat(inp.value) || 0;

  document.querySelectorAll('.wc-preset-btn').forEach(b => {
    const bw = parseFloat(b.textContent);
    b.classList.toggle('active', Math.abs(bw - w) < 0.01);
  });

  if (w >= MIN_ORDER_KG && w <= MAX_ORDER_KG) {
    btn.disabled = false;
    box.style.display = 'flex';
    amt.textContent = fmt(PRICE_PER_KG * w);
  } else {
    btn.disabled = true;
    if (box) box.style.display = 'none';
  }
}

// ── Cart ──
function addToCart() {
  const inp = document.getElementById('wcWeightInput');
  const w = parseFloat(inp.value) || 0;
  if (w < MIN_ORDER_KG || w > MAX_ORDER_KG) return;

  cart.weight = Math.round((cart.weight + w) * 2) / 2;
  renderCart();

  inp.value = '';
  document.getElementById('wcAddBtn').disabled = true;
  document.getElementById('wcLiveEstimate').style.display = 'none';
  document.querySelectorAll('.wc-preset-btn').forEach(b => b.classList.remove('active'));
}

function renderCart() {
  const w = cart.weight;
  const emptyEl  = document.getElementById('wcCartEmpty');
  const itemsEl  = document.getElementById('wcCartItems');
  const summary  = document.getElementById('wcCartSummary');
  const subEl    = document.getElementById('wcSubtotal');
  const totEl    = document.getElementById('wcTotalAmt');
  const badge    = document.getElementById('wcCartBadge');
  const badgeKg  = document.getElementById('wcCartKg');

  if (badge && badgeKg) {
    if (w > 0) { badge.style.display = 'inline-flex'; badgeKg.textContent = w; }
    else { badge.style.display = 'none'; }
  }

  if (w <= 0) {
    if (emptyEl) emptyEl.style.display = 'flex';
    if (itemsEl) itemsEl.innerHTML = '';
    if (summary) summary.style.display = 'none';
    updatePlaceBtn();
    return;
  }

  const total = PRICE_PER_KG * w;

  if (emptyEl) emptyEl.style.display = 'none';
  if (summary) summary.style.display = 'block';

  if (itemsEl) {
    itemsEl.innerHTML = `
      <div class="wc-cart-item">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:600;">Fresh Crayfish</div>
          <div style="font-size:12px;color:var(--text-soft);margin-top:2px;">
            ${fmt(PRICE_PER_KG)}/kg &nbsp;×&nbsp; <strong>${w} kg</strong> &nbsp;=&nbsp;
            <strong style="color:var(--green);">${fmt(total)}</strong>
          </div>
        </div>
        <div class="wc-cart-actions">
          <button class="wc-btn-edit" onclick="openEditModal()"><i class="fas fa-pen"></i> Edit</button>
          <button class="wc-btn-remove" onclick="openDeleteModal()"><i class="fas fa-trash"></i></button>
        </div>
      </div>`;
  }

  if (subEl) subEl.textContent = fmt(total);
  if (totEl) totEl.textContent = fmt(total);

  updatePlaceBtn();
}

function updatePlaceBtn() {
  const btn  = document.getElementById('wcPlaceBtn');
  const hint = document.getElementById('wcPlaceHint');
  const date = document.getElementById('wcPickupDate')?.value || '';
  const time = document.getElementById('wcPickupTime')?.value || '';
  const hasWeight = cart.weight > 0;
  const hasDate   = date.length > 0;
  const hasTime   = time.length > 0;

  if (hasWeight && hasDate && hasTime) {
    btn.disabled = false;
    if (hint) hint.style.display = 'none';
  } else {
    btn.disabled = true;
    if (hint) {
      hint.style.display = 'block';
      if (!hasWeight) hint.innerHTML = '<i class="fas fa-info-circle"></i> Add at least ' + MIN_ORDER_KG + ' kg to enable ordering.';
      else if (!hasDate) hint.innerHTML = '<i class="fas fa-info-circle"></i> Select a pickup date to enable ordering.';
      else if (!hasTime) hint.innerHTML = '<i class="fas fa-info-circle"></i> Select a pickup time to enable ordering.';
    }
  }
}

// Listen for pickup date/time changes
document.addEventListener('DOMContentLoaded', function() {
  const dateEl = document.getElementById('wcPickupDate');
  const timeEl = document.getElementById('wcPickupTime');
  if (dateEl) dateEl.addEventListener('change', updatePlaceBtn);
  if (timeEl) timeEl.addEventListener('change', updatePlaceBtn);
});

// ── Delete Modal ──
function openDeleteModal() {
  document.getElementById('wcDeleteName').textContent = cart.weight + ' kg Fresh Crayfish';
  document.getElementById('wcDeleteBg').classList.add('open');
  document.getElementById('wcDeleteModal').classList.add('open');
}

function closeDeleteModal() {
  document.getElementById('wcDeleteBg').classList.remove('open');
  document.getElementById('wcDeleteModal').classList.remove('open');
}

function confirmDelete() {
  cart.weight = 0;
  closeDeleteModal();
  renderCart();
}

// ── Edit Modal ──
function openEditModal() {
  editingWeight = cart.weight;
  updateEditModalUI();
  document.getElementById('wcEditBg').classList.add('open');
  document.getElementById('wcEditModal').classList.add('open');
}

function closeEditModal() {
  document.getElementById('wcEditBg').classList.remove('open');
  document.getElementById('wcEditModal').classList.remove('open');
  editingWeight = 0;
}

function adjustEditQty(delta) {
  editingWeight = Math.max(MIN_ORDER_KG, Math.min(MAX_ORDER_KG, Math.round((editingWeight + delta) * 2) / 2));
  updateEditModalUI();
}

function updateEditModalUI() {
  document.getElementById('wcEditQtyVal').textContent = editingWeight + ' kg';
  document.getElementById('wcEditSubtotal').textContent = fmt(PRICE_PER_KG * editingWeight);
}

function saveEdit() {
  cart.weight = editingWeight;
  renderCart();
  closeEditModal();
}

// ── Sign In Modal ──
function openSignInModal() {
  document.getElementById('wcSignInBg').classList.add('open');
  document.getElementById('wcSignInModal').classList.add('open');
}

function closeSignInModal() {
  document.getElementById('wcSignInBg').classList.remove('open');
  document.getElementById('wcSignInModal').classList.remove('open');
}

function submitSignIn() {
  const email = document.getElementById('wcSignInEmail').value.trim();
  const password = document.getElementById('wcSignInPass').value;

  if (!email) { alert('Please enter your email.'); return; }
  if (!password) { alert('Please enter your password.'); return; }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '../logic/auth_process.php';
  form.style.display = 'none';

  const emailInput = document.createElement('input');
  emailInput.name = 'email';
  emailInput.value = email;
  form.appendChild(emailInput);

  const passInput = document.createElement('input');
  passInput.name = 'password';
  passInput.value = password;
  form.appendChild(passInput);

  const btnInput = document.createElement('input');
  btnInput.name = 'login_btn';
  btnInput.value = '1';
  form.appendChild(btnInput);

  document.body.appendChild(form);

  closeSignInModal();

  form.submit();
}

// ── Payment mode toggle ──
let payNowMode = false;

function togglePayMode(payNow) {
  payNowMode = payNow;
  const nowBtn = document.getElementById('wcPayNowBtn');
  const laterBtn = document.getElementById('wcPayLaterBtn');
  const fields = document.getElementById('wcPayNowFields');

  if (payNow) {
    nowBtn.style.borderColor = 'var(--forest)';
    nowBtn.style.background = 'var(--forest)';
    nowBtn.style.color = '#fff';
    laterBtn.style.borderColor = 'var(--border)';
    laterBtn.style.background = '#fff';
    laterBtn.style.color = 'var(--forest)';
    if (fields) fields.style.display = 'block';
  } else {
    laterBtn.style.borderColor = 'var(--forest)';
    laterBtn.style.background = 'var(--forest)';
    laterBtn.style.color = '#fff';
    nowBtn.style.borderColor = 'var(--border)';
    nowBtn.style.background = '#fff';
    nowBtn.style.color = 'var(--forest)';
    if (fields) fields.display = 'none';
  }
  updatePayAmountHint();
}

function setFullPayment() {
  const total = PRICE_PER_KG * cart.weight;
  const inp = document.getElementById('wcPayAmount');
  if (inp) inp.value = total.toFixed(0);
  updatePayAmountHint();
}

function updatePayAmountHint() {
  const hint = document.getElementById('wcPayAmountHint');
  if (!hint) return;
  if (cart.weight > 0) {
    const total = PRICE_PER_KG * cart.weight;
    hint.textContent = 'Total: ' + fmt(total) + ' — enter any amount up to full total for partial payment.';
  } else {
    hint.textContent = '';
  }
}

// ── Place Order (AJAX) ──
function placeOrder() {
  const pickupDate = document.getElementById('wcPickupDate')?.value || '';
  const pickupTime = document.getElementById('wcPickupTime')?.value || '';

  if (!pickupDate) { alert('Please select a pickup date.'); document.getElementById('wcPickupDate')?.focus(); return; }
  if (!pickupTime) { alert('Please select a pickup time.'); document.getElementById('wcPickupTime')?.focus(); return; }
  if (cart.weight <= 0) { alert('Please add at least ' + MIN_ORDER_KG + ' kg to your order.'); return; }

  // Validate payment fields if paying now
  let payMethod = '';
  let payAmount = 0;
  if (payNowMode) {
    payMethod = document.getElementById('wcPaymentMethod')?.value || '';
    payAmount = parseFloat(document.getElementById('wcPayAmount')?.value) || 0;
    if (!payMethod) { alert('Please select a payment method.'); return; }
    if (payAmount <= 0) { alert('Please enter a payment amount.'); return; }
    const total = PRICE_PER_KG * cart.weight;
    if (payAmount > total) { payAmount = total; }
  }

  const btn = document.getElementById('wcPlaceBtn');
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

  const payload = new URLSearchParams();
  payload.append('weight_kg', cart.weight);
  payload.append('price_per_kg', PRICE_PER_KG);
  payload.append('pickup_date', pickupDate);
  payload.append('pickup_time', pickupTime);
  if (payNowMode) {
    payload.append('pay_now', '1');
    payload.append('payment_method', payMethod);
    payload.append('amount_paid', payAmount);
  }

  fetch('../logic/westcrays_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload.toString()
  })
    .then(res => res.json())
    .then(data => {
      btn.innerHTML = originalHTML;
      updatePlaceBtn();

      if (!data.success) {
        const msg = data.errors ? data.errors.join('\n') : (data.error || 'Something went wrong.');
        alert(msg);
        return;
      }

      const successMsg = document.getElementById('wcSuccessMsg');
      const successActions = document.getElementById('wcSuccessActions');
      if (successMsg) {
        let msg = `Order #${data.order_id} placed! <strong>${data.weight_kg} kg</strong> of fresh crayfish for <strong>${fmt(data.total)}</strong>.`;
        if (data.payment_status === 'Paid') {
          msg += ' <strong style="color:var(--green);">Payment received!</strong>';
        } else if (data.payment_status === 'Partial') {
          msg += ' Partial payment of <strong>' + fmt(data.amount_paid) + '</strong> received. Remaining: <strong>' + fmt(data.remaining) + '</strong>.';
        } else {
          msg += ' Payment due on pickup.';
        }
        successMsg.innerHTML = msg;
      }
      if (successActions) {
        successActions.innerHTML = `<a href="../pages/crayfish_receipt.php?order_id=${data.order_id}" class="wc-btn-primary" style="margin-top:12px;display:inline-flex;"><i class="fas fa-receipt"></i> View Receipt</a>`;
      }

      const box = document.getElementById('wcSuccess');
      if (box) { box.style.display = 'block'; box.scrollIntoView({ behavior: 'smooth' }); }

      // Reset
      cart.weight = 0;
      renderCart();
      document.getElementById('wcPickupDate').value = '';
      document.getElementById('wcPickupTime').value = '';
      document.getElementById('wcPaymentMethod').value = '';
      document.getElementById('wcPayAmount').value = '';
      togglePayMode(false);

      setTimeout(() => { if (box) box.style.display = 'none'; }, 20000);
    })
    .catch(err => {
      btn.innerHTML = originalHTML;
      updatePlaceBtn();
      console.error(err);
      alert('Something went wrong. Please try again or call the resort directly.');
    });
}

// ── Cancel Order ──
function cancelOrder(orderId) {
  if (!confirm('Are you sure you want to cancel order #' + orderId + '? Any payment made will be marked as refunded.')) return;

  const payload = new URLSearchParams();
  payload.append('action', 'cancel_order');
  payload.append('order_id', orderId);

  fetch('../logic/crayfish_payment_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload.toString()
  })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert(data.error || 'Cancellation failed.');
        return;
      }
      if (data.refund_amount > 0) {
        alert('Order #' + order_id + ' cancelled. Refund amount: ₱' + parseFloat(data.refund_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) + '. Please contact the resort to arrange your refund.');
      } else {
        alert('Order #' + orderId + ' cancelled successfully.');
      }
      location.reload();
    })
    .catch(err => {
      console.error(err);
      alert('Something went wrong. Please try again.');
    });
}

// ── Pay Modal (for existing orders) ──
let payModalOrderId = 0;
let payModalRemaining = 0;

function openPayModal(orderId, remaining) {
  payModalOrderId = orderId;
  payModalRemaining = remaining;
  document.getElementById('payModalOrderId').textContent = orderId;
  document.getElementById('payModalRemaining').textContent = fmt(remaining);
  document.getElementById('payModalAmount').value = remaining.toFixed(0);
  document.getElementById('payModalBg').style.display = 'flex';
}

function closePayModal() {
  document.getElementById('payModalBg').style.display = 'none';
  payModalOrderId = 0;
}

function submitPayModal() {
  const method = document.getElementById('payModalMethod').value;
  const amount = parseFloat(document.getElementById('payModalAmount').value) || 0;

  if (!method) { alert('Please select a payment method.'); return; }
  if (amount <= 0) { alert('Please enter an amount.'); return; }
  if (amount > payModalRemaining) { alert('Amount cannot exceed remaining balance of ' + fmt(payModalRemaining)); return; }

  const btn = document.getElementById('payModalSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

  const payload = new URLSearchParams();
  payload.append('action', 'make_payment');
  payload.append('order_id', payModalOrderId);
  payload.append('payment_method', method);
  payload.append('amount_paid', amount);

  fetch('../logic/crayfish_payment_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload.toString()
  })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-lock"></i> Confirm Payment';
      if (!data.success) {
        alert(data.error || 'Payment failed.');
        return;
      }
      alert('Payment of ' + fmt(data.amount_paid) + ' received! ' + (data.remaining > 0 ? 'Remaining: ' + fmt(data.remaining) : 'Order fully paid!'));
      closePayModal();
      location.reload();
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-lock"></i> Confirm Payment';
      console.error(err);
      alert('Something went wrong. Please try again.');
    });
}

// ── Expose globals ──
window.setWeight        = setWeight;
window.adjustWeight     = adjustWeight;
window.addToCart        = addToCart;
window.openDeleteModal  = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.confirmDelete    = confirmDelete;
window.openEditModal    = openEditModal;
window.closeEditModal   = closeEditModal;
window.adjustEditQty    = adjustEditQty;
window.saveEdit         = saveEdit;
window.placeOrder       = placeOrder;
window.openSignInModal  = openSignInModal;
window.closeSignInModal = closeSignInModal;
window.submitSignIn     = submitSignIn;
window.togglePayMode    = togglePayMode;
window.setFullPayment   = setFullPayment;
window.cancelOrder      = cancelOrder;
window.openPayModal     = openPayModal;
window.closePayModal    = closePayModal;
window.submitPayModal   = submitPayModal;

// ── Init ──
onWeightInput();
renderCart();
