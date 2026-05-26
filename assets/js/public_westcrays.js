// ── Config ──
const PRICE_PER_KG = 120;

// Cart stores the selected weight
let cart = { weight: 0 };

// Edit modal state
let editingWeight = 0;
let deleteCalledFromEdit = false;

// ── Helpers ──
function escapeHtml(str) {
  return String(str).replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
}

function fmt(amount) {
  return '₱' + amount.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2});
}

// ── Render the single product card ──
function renderProduct() {
  const area = document.getElementById('productArea');
  if (!area) return;

  area.innerHTML = `
    <div class="product-card">
      <div class="product-card-img">
        <img src="../assets/images/westcrays1.jpg" alt="Fresh Crayfish"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
        <div class="product-card-placeholder" style="display:none;">
          <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 36 L16 22 L24 30 L30 22 L42 36 Z" fill="#c8c2b5"/>
            <circle cx="34" cy="16" r="6" fill="#c8c2b5"/>
          </svg>
          <span>crayfish</span>
        </div>
        <span class="product-card-badge"><i class="fas fa-leaf"></i> Farm Fresh</span>
      </div>
      <div class="product-card-body">
        <div class="product-card-name">Fresh Crayfish</div>
        <div class="product-card-desc">Handpicked live crayfish from our farm. Sold per kilogram — type how many kg you need.</div>
        <div class="product-card-price-row">
          <span class="product-card-price">${fmt(PRICE_PER_KG)}</span>
          <span class="product-card-unit">per kg</span>
        </div>

        <div class="weight-selector">
          <label class="weight-label"><i class="fas fa-weight-hanging"></i> Enter weight (kg)</label>
          <div class="weight-controls">
            <input type="number" id="weightInput" class="weight-input" min="0.5" max="100" step="0.5" placeholder="e.g. 2" />
            <span class="weight-kg-label">kg</span>
            <div class="weight-live-total" id="weightLiveTotal" style="display:none;">
              <span class="weight-live-label">Live total</span>
              <span class="weight-live-amount" id="weightLiveAmt">${fmt(0)}</span>
            </div>
          </div>
          <div class="weight-hint">Minimum 0.5 kg · Maximum 100 kg</div>
        </div>

        <button class="add-to-cart-btn" id="addToCartBtn" disabled onclick="addToCart()">
          <i class="fas fa-cart-plus"></i> Add to Order
        </button>
      </div>
    </div>`;

  const inp  = document.getElementById('weightInput');
  const btn  = document.getElementById('addToCartBtn');
  const box  = document.getElementById('weightLiveTotal');
  const amt  = document.getElementById('weightLiveAmt');

  function onWeightChange() {
    const w = parseFloat(inp.value) || 0;
    if (w >= 0.5) {
      btn.disabled = false;
      box.style.display = 'flex';
      amt.textContent = fmt(PRICE_PER_KG * w);
    } else {
      btn.disabled = true;
      box.style.display = 'none';
    }
  }

  inp.addEventListener('input', onWeightChange);
  inp.addEventListener('change', onWeightChange);
}

// ── Cart ──
function addToCart() {
  const inp = document.getElementById('weightInput');
  const w = parseFloat(inp.value) || 0;
  if (w < 0.5) return;

  cart.weight += w;
  renderCart();

  // Reset input
  inp.value = '';
  document.getElementById('addToCartBtn').disabled = true;
  document.getElementById('weightLiveTotal').style.display = 'none';
}

function renderCart() {
  const w = cart.weight;
  const emptyEl    = document.getElementById('cartEmpty');
  const itemsEl    = document.getElementById('cartItems');
  const totalDiv   = document.getElementById('cartTotal');
  const subtotalEl = document.getElementById('subtotalAmt');
  const totalSpan  = document.getElementById('totalAmt');
  const checkBtn   = document.getElementById('checkoutBtn');
  const badge      = document.getElementById('cartCountBadge');
  const badgeNum   = document.getElementById('cartCountNum');

  if (badge && badgeNum) {
    if (w > 0) { badge.style.display = 'inline-flex'; badgeNum.textContent = w; }
    else       { badge.style.display = 'none'; }
  }

  if (w <= 0) {
    if (emptyEl)  emptyEl.style.display  = 'flex';
    if (itemsEl)  itemsEl.innerHTML      = '';
    if (totalDiv) totalDiv.style.display = 'none';
    if (checkBtn) checkBtn.disabled      = true;
    return;
  }

  const total = PRICE_PER_KG * w;

  if (emptyEl)  emptyEl.style.display  = 'none';
  if (totalDiv) totalDiv.style.display = 'block';
  if (checkBtn) checkBtn.disabled      = false;

  if (itemsEl) {
    itemsEl.innerHTML = `
      <div class="cart-item">
        <div class="cart-thumb"><span class="thumb-icon">🦞</span></div>
        <div class="cart-item-info">
          <div class="cart-item-name">Fresh Crayfish</div>
          <div class="cart-item-price">${fmt(PRICE_PER_KG)}/kg &nbsp;&times;&nbsp; <strong>${w} kg</strong> &nbsp;=&nbsp; <strong style="color:var(--green);">${fmt(total)}</strong></div>
        </div>
        <div class="cart-actions">
          <button class="edit-btn" onclick="openEditModal()"><i class="fas fa-pen"></i> Edit</button>
          <button class="delete-btn" onclick="openDeleteModal(false)"><i class="fas fa-trash"></i></button>
        </div>
      </div>`;
  }

  if (subtotalEl) subtotalEl.textContent = fmt(total);
  if (totalSpan)  totalSpan.textContent  = fmt(total);
}

// ── Delete Modal ──
function openDeleteModal(fromEdit) {
  deleteCalledFromEdit = fromEdit;
  document.getElementById('deleteItemName').textContent = 'Fresh Crayfish';
  document.getElementById('deleteBackdrop').classList.add('open');
  document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
  document.getElementById('deleteBackdrop').classList.remove('open');
  document.getElementById('deleteModal').classList.remove('open');
  deleteCalledFromEdit = false;
}

function confirmDelete() {
  cart.weight = 0;
  closeDeleteModal();
  if (deleteCalledFromEdit) closeModal();
  renderCart();
}

// ── Edit Modal ──
function openEditModal() {
  editingWeight = cart.weight;

  document.getElementById('editProductName').textContent  = 'Fresh Crayfish';
  document.getElementById('editProductPrice').textContent = fmt(PRICE_PER_KG) + ' per kg';

  const thumbEl = document.getElementById('editThumb');
  thumbEl.innerHTML = `<img src="../assets/images/westcrays1.jpg" alt="Fresh Crayfish" onerror="this.parentElement.textContent='🦞'">`;

  updateModalQty();

  document.getElementById('modalBackdrop').classList.add('open');
  document.getElementById('editModal').classList.add('open');
}

function closeModal() {
  document.getElementById('modalBackdrop').classList.remove('open');
  document.getElementById('editModal').classList.remove('open');
  editingWeight = 0;
}

function adjustEditQty(delta) {
  editingWeight = Math.max(0, editingWeight + delta);
  updateModalQty();
}

function updateModalQty() {
  const displayWeight = editingWeight < 0.5 ? 0 : editingWeight;
  document.getElementById('editQtyNum').textContent   = displayWeight + ' kg';
  document.getElementById('editSubtotal').textContent = fmt(PRICE_PER_KG * displayWeight);
}

function saveEdit() {
  cart.weight = editingWeight < 0.5 ? 0 : editingWeight;
  renderCart();
  closeModal();
}

function openDeleteFromEdit() {
  openDeleteModal(true);
}

// ── Place Order (AJAX) ──
function placeOrder() {
  const name    = (document.getElementById('guestName')?.value || '').trim();
  const phone   = (document.getElementById('guestPhone')?.value || '').trim();
  const address = (document.getElementById('guestAddress')?.value || '').trim();
  const time    = document.getElementById('guestTime')?.value || '';
  const prep    = document.getElementById('guestPrep')?.value || '';
  const notes   = (document.getElementById('guestNotes')?.value || '').trim();

  // Client-side validation
  if (!name)    { alert('Please enter your full name.');        document.getElementById('guestName')?.focus();    return; }
  if (!phone)   { alert('Please enter your contact number.');   document.getElementById('guestPhone')?.focus();   return; }
  if (!address) { alert('Please enter your delivery address.'); document.getElementById('guestAddress')?.focus(); return; }
  if (!time)    { alert('Please select a preferred delivery time.'); document.getElementById('guestTime')?.focus(); return; }

  const w     = cart.weight;
  const total = PRICE_PER_KG * w;

  // Show loading state on the button
  const btn = document.getElementById('checkoutBtn');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

  // Build the POST payload
  const payload = new URLSearchParams();
  payload.append('guest_name', name);
  payload.append('guest_phone', phone);
  payload.append('delivery_address', address);
  payload.append('weight_kg', w);
  payload.append('price_per_kg', PRICE_PER_KG);
  payload.append('delivery_time', time);
  payload.append('preparation', prep);
  payload.append('notes', notes);

  fetch('../logic/westcrays_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload.toString()
  })
    .then(res => res.json())
    .then(data => {
      btn.innerHTML = originalText;
      if (btn.dataset.cartActive !== 'false') btn.disabled = false;

      if (!data.success) {
        // Show server-side validation errors
        const msg = data.errors ? data.errors.join('\n') : data.error;
        alert(msg);
        return;
      }

      // ── Show success ──
      const successMsg = document.getElementById('successMsg');
      if (successMsg) {
        let msg = `Order #${data.order_id} placed successfully! <strong>${data.weight_kg} kg</strong> Fresh Crayfish for <strong>${fmt(data.total)}</strong>.`;
        msg += ` Delivered to <strong>${escapeHtml(data.address)}</strong> around <strong>${escapeHtml(data.delivery_time)}</strong>.`;
        if (data.preparation) msg += ` Preparation: <strong>${escapeHtml(data.preparation)}</strong>.`;
        msg += ` We'll contact you at <strong>${escapeHtml(data.phone)}</strong>.`;
        if (data.notes) msg += ` <em>Note: ${escapeHtml(data.notes)}</em>`;
        successMsg.innerHTML = msg;
      }

      const box = document.getElementById('successBox');
      if (box) { box.style.display = 'block'; box.scrollIntoView({ behavior: 'smooth' }); }

      // Reset everything
      cart.weight = 0;
      renderCart();
      document.getElementById('guestAddress').value = '';
      document.getElementById('guestNotes').value = '';
      document.getElementById('guestTime').value = '';
      document.getElementById('guestPrep').value = '';
      if (!window.__isLoggedIn) {
        document.getElementById('guestName').value = '';
        document.getElementById('guestPhone').value = '';
      }

      setTimeout(() => { if (box) box.style.display = 'none'; }, 10000);
    })
    .catch(err => {
      btn.innerHTML = originalText;
      if (btn.dataset.cartActive !== 'false') btn.disabled = false;
      console.error(err);
      alert('Something went wrong. Please try again or call the resort.');
    });
}

// ── Nav dropdown ──
const navItems = document.querySelectorAll('.nav-item');
navItems.forEach(item => {
  const link = item.querySelector('a');
  if (link) {
    link.addEventListener('click', e => {
      e.preventDefault(); e.stopPropagation();
      const isOpen = item.classList.contains('open');
      navItems.forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  }
});
document.addEventListener('click', () => navItems.forEach(i => i.classList.remove('open')));

// ── Expose globals for inline onclick ──
window.openModal          = openEditModal;
window.closeModal         = closeModal;
window.adjustEditQty      = adjustEditQty;
window.saveEdit           = saveEdit;
window.openDeleteModal    = openDeleteModal;
window.closeDeleteModal   = closeDeleteModal;
window.confirmDelete      = confirmDelete;
window.openDeleteFromEdit = openDeleteFromEdit;
window.placeOrder         = placeOrder;
window.addToCart          = addToCart;

// ── Init ──
renderProduct();
renderCart();
