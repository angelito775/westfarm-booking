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

// ── Place Order (AJAX) ──
function placeOrder() {
  const pickupDate = document.getElementById('wcPickupDate')?.value || '';
  const pickupTime = document.getElementById('wcPickupTime')?.value || '';

  if (!pickupDate) { alert('Please select a pickup date.'); document.getElementById('wcPickupDate')?.focus(); return; }
  if (!pickupTime) { alert('Please select a pickup time.'); document.getElementById('wcPickupTime')?.focus(); return; }
  if (cart.weight <= 0) { alert('Please add at least ' + MIN_ORDER_KG + ' kg to your order.'); return; }

  const btn = document.getElementById('wcPlaceBtn');
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

  const payload = new URLSearchParams();
  payload.append('weight_kg', cart.weight);
  payload.append('price_per_kg', PRICE_PER_KG);
  payload.append('pickup_date', pickupDate);
  payload.append('pickup_time', pickupTime);

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
      if (successMsg) {
        successMsg.innerHTML = `Order #${data.order_id} placed! <strong>${data.weight_kg} kg</strong> of fresh crayfish for <strong>${fmt(data.total)}</strong>. Pick up on <strong>${data.pickup_date}</strong> during <strong>${data.pickup_time}</strong>. We'll have them harvested and packed live for you.`;
      }

      const box = document.getElementById('wcSuccess');
      if (box) { box.style.display = 'block'; box.scrollIntoView({ behavior: 'smooth' }); }

      // Reset
      cart.weight = 0;
      renderCart();
      document.getElementById('wcPickupDate').value = '';
      document.getElementById('wcPickupTime').value = '';

      setTimeout(() => { if (box) box.style.display = 'none'; }, 15000);
    })
    .catch(err => {
      btn.innerHTML = originalHTML;
      updatePlaceBtn();
      console.error(err);
      alert('Something went wrong. Please try again or call the resort directly.');
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

// ── Init ──
onWeightInput();
renderCart();
