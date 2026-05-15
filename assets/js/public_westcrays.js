const products = [
  { id:1, name:'Black Creek Crayfish',    desc:'Procambarus Pictus',              price:199, img:'westcrays3.png',  tag:'popular' },
  { id:2, name:'White Specter Crayfish',  desc:'Procambarus clarkii',             price:299, img:'westcrays4.webp', tag:'popular' },
  { id:3, name:'Ghost Crayfish',          desc:'Procambarus clarkii ghost',       price:149, img:'westcrays5.webp', tag:'new'     },
  { id:4, name:'Scarlet Crayfish',        desc:'Bright red, vibrant appearance',  price:199, img:'westcrays6.webp', tag:'popular' },
  { id:5, name:'Neon or Fireball Crayfish', desc:'Freshwater Crayfish',           price:249, img:'westcrays7.jpg',  tag:'new'     },
  { id:6, name:'Blue Ghost Crayfish',     desc:'Exclusive West Farm bred variety', price:399, img:'westcrays8.webp', tag:'new'    },
];

let cart = {};

// ── Edit modal state ──
let editingId   = null;
let editingQty  = 1;
let editingSwapId = null; // tracks if user picked a different product

// ── Delete modal state ──
let pendingDeleteId       = null;
let deleteCalledFromEdit  = false;

// ── Helpers ──
function escapeHtml(str) {
  return String(str).replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
}

function createPlaceholder() {
  const div = document.createElement('div');
  div.className = 'img-placeholder';
  div.innerHTML = `
    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M6 36 L16 22 L24 30 L30 22 L42 36 Z" fill="#c8c2b5"/>
      <circle cx="34" cy="16" r="6" fill="#c8c2b5"/>
    </svg>
    <span>crayfish</span>`;
  return div;
}

// ── Render product cards ──
function renderProducts() {
  const grid = document.getElementById('productGrid');
  if (!grid) return;
  grid.innerHTML = '';

  products.forEach(p => {
    const card = document.createElement('div');
    card.className = 'card';

    const imgDiv = document.createElement('div');
    imgDiv.className = 'card-img';

    const tagSpan = document.createElement('span');
    tagSpan.className = `card-tag ${p.tag === 'popular' ? 'tag-popular' : 'tag-new'}`;
    tagSpan.textContent = p.tag === 'popular' ? 'Popular' : 'New';
    imgDiv.appendChild(tagSpan);

    if (p.img) {
      const img = document.createElement('img');
      img.src = p.img;
      img.alt = p.name;
      img.onerror = function () { this.remove(); imgDiv.appendChild(createPlaceholder()); };
      imgDiv.appendChild(img);
    } else {
      imgDiv.appendChild(createPlaceholder());
    }

    const body = document.createElement('div');
    body.className = 'card-body';
    body.innerHTML = `
      <div class="card-name">${escapeHtml(p.name)}</div>
      <div class="card-desc">${escapeHtml(p.desc)}</div>
      <div class="card-footer">
        <span class="card-price">&#8369;${p.price}</span>
        <button class="add-btn" data-id="${p.id}">+ Add</button>
      </div>`;

    card.appendChild(imgDiv);
    card.appendChild(body);
    grid.appendChild(card);
  });

  document.querySelectorAll('.add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      addToCart(parseInt(btn.dataset.id));
      btn.textContent = '✓ Added';
      btn.classList.add('added');
      setTimeout(() => { btn.textContent = '+ Add'; btn.classList.remove('added'); }, 800);
    });
  });
}

// ── Cart ──
function addToCart(id) {
  cart[id] = (cart[id] || 0) + 1;
  renderCart();
}

function deleteFromCart(id) {
  // Always go through the confirmation modal
  openDeleteModal(id, false);
}

function renderCart() {
  const keys      = Object.keys(cart);
  const emptyEl   = document.getElementById('cartEmpty');
  const itemsEl   = document.getElementById('cartItems');
  const totalDiv  = document.getElementById('cartTotal');
  const totalSpan = document.getElementById('totalAmt');
  const checkBtn  = document.getElementById('checkoutBtn');

  if (!keys.length) {
    if (emptyEl)  emptyEl.style.display  = 'block';
    if (itemsEl)  itemsEl.innerHTML      = '';
    if (totalDiv) totalDiv.style.display = 'none';
    if (checkBtn) checkBtn.disabled      = true;
    return;
  }

  if (emptyEl)  emptyEl.style.display  = 'none';
  if (totalDiv) totalDiv.style.display = 'flex';
  if (checkBtn) checkBtn.disabled      = false;

  let total = 0;

  if (itemsEl) {
    itemsEl.innerHTML = keys.map(id => {
      const p   = products.find(x => x.id == id);
      const qty = cart[id];
      if (!p) return '';
      total += p.price * qty;
      return `
        <div class="cart-item" id="cart-item-${id}">
          <div class="cart-thumb"><span class="thumb-icon">🦞</span></div>
          <div class="cart-item-info">
            <div class="cart-item-name">${escapeHtml(p.name)}</div>
            <div class="cart-item-price">&#8369;${p.price} each &nbsp;·&nbsp; qty: <strong>${qty}</strong></div>
          </div>
          <div class="cart-actions">
            <button class="edit-btn"   onclick="openModal(${id})">✏️ Edit</button>
            <button class="delete-btn" onclick="openDeleteModal(${id}, false)">🗑️ Delete</button>
          </div>
        </div>`;
    }).join('');
  }

  if (totalSpan) totalSpan.textContent = '₱' + total.toLocaleString();
}

// ── Delete Confirmation Modal ──
function openDeleteModal(id, fromEdit) {
  const p = products.find(x => x.id == id);
  if (!p) return;

  pendingDeleteId      = id;
  deleteCalledFromEdit = fromEdit;

  document.getElementById('deleteItemName').textContent = p.name;
  document.getElementById('deleteBackdrop').classList.add('open');
  document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
  document.getElementById('deleteBackdrop').classList.remove('open');
  document.getElementById('deleteModal').classList.remove('open');
  pendingDeleteId      = null;
  deleteCalledFromEdit = false;
}

function confirmDelete() {
  if (pendingDeleteId === null) return;
  delete cart[pendingDeleteId];
  closeDeleteModal();
  if (deleteCalledFromEdit) closeModal();
  renderCart();
}

// ── Edit Modal ──
function openModal(id) {
  const p = products.find(x => x.id == id);
  if (!p) return;

  editingId     = id;
  editingSwapId = null;
  editingQty    = cart[id] || 1;

  refreshEditModalContent(p);

  // Build the "switch variety" picker
  renderSwapOptions(id);

  // Open
  document.getElementById('modalBackdrop').classList.add('open');
  document.getElementById('editModal').classList.add('open');
}

function refreshEditModalContent(p) {
  document.getElementById('editProductName').textContent  = p.name;
  document.getElementById('editProductPrice').textContent = '₱' + p.price + ' each';

  const thumbEl = document.getElementById('editThumb');
  if (p.img) {
    thumbEl.innerHTML = `<img src="${p.img}" alt="${escapeHtml(p.name)}" onerror="this.parentElement.textContent='🦞'">`;
  } else {
    thumbEl.textContent = '🦞';
  }

  updateModalQty();
}

function renderSwapOptions(currentId) {
  // Remove any existing swap section first
  const existing = document.getElementById('swapSection');
  if (existing) existing.remove();

  const body = document.querySelector('.edit-modal-body');
  if (!body) return;

  const section = document.createElement('div');
  section.id = 'swapSection';
  section.style.cssText = 'margin-top: 18px;';

  section.innerHTML = `
    <div style="
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
      color: #999; margin-bottom: 10px; display: block;
    ">Switch to a different variety</div>
    <div id="swapGrid" style="
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 8px;
    "></div>
  `;

  body.appendChild(section);

  const swapGrid = section.querySelector('#swapGrid');

  products.forEach(p => {
    const isActive = (p.id == currentId || p.id == (editingSwapId || currentId));
    const btn = document.createElement('button');
    btn.dataset.pid = p.id;
    btn.style.cssText = `
      display: flex; align-items: center; gap: 8px;
      padding: 8px 10px; border-radius: 10px;
      border: 1.5px solid ${isActive ? 'var(--green, #1a3a1a)' : '#e0e0e0'};
      background: ${isActive ? '#f0f7f0' : '#fafafa'};
      cursor: pointer; text-align: left;
      transition: all 0.18s; width: 100%;
      font-family: 'Josefin Sans', sans-serif;
    `;

    const thumb = document.createElement('div');
    thumb.style.cssText = `
      width: 30px; height: 30px; border-radius: 6px;
      background: #e8e4dc; overflow: hidden; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px;
    `;
    if (p.img) {
      const img = document.createElement('img');
      img.src = p.img;
      img.alt = p.name;
      img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
      img.onerror = () => { img.remove(); thumb.textContent = '🦞'; };
      thumb.appendChild(img);
    } else {
      thumb.textContent = '🦞';
    }

    const info = document.createElement('div');
    info.innerHTML = `
      <div style="font-size:11px;font-weight:700;color:#1a1a1a;letter-spacing:0.3px;line-height:1.3;">${escapeHtml(p.name)}</div>
      <div style="font-size:10px;color:var(--green,#1a3a1a);font-weight:600;">₱${p.price}</div>
    `;

    btn.appendChild(thumb);
    btn.appendChild(info);
    swapGrid.appendChild(btn);

    btn.addEventListener('click', () => {
      editingSwapId = p.id;
      // Refresh product info at top of modal
      refreshEditModalContent(p);
      // Re-render swap options to update active state
      renderSwapOptions(p.id);
    });
  });
}

function closeModal() {
  document.getElementById('modalBackdrop').classList.remove('open');
  document.getElementById('editModal').classList.remove('open');
  editingId     = null;
  editingSwapId = null;
  editingQty    = 1;
}

function adjustEditQty(delta) {
  editingQty = Math.max(1, editingQty + delta);
  updateModalQty();
}

function updateModalQty() {
  const activeId = editingSwapId || editingId;
  const p = products.find(x => x.id == activeId);
  document.getElementById('editQtyNum').textContent   = editingQty;
  document.getElementById('editSubtotal').textContent = '₱' + (p ? (p.price * editingQty).toLocaleString() : 0);
}

function saveEdit() {
  if (editingId === null) return;

  const targetId = editingSwapId || editingId;

  // If swapping to a different product
  if (editingSwapId && editingSwapId != editingId) {
    // If that product is already in cart, add to its qty
    cart[targetId] = (cart[targetId] || 0) + editingQty;
    delete cart[editingId];
  } else {
    cart[editingId] = editingQty;
  }

  renderCart();
  closeModal();
}

// Called by "🗑️ Remove this item" button inside the edit modal
function openDeleteFromEdit() {
  openDeleteModal(editingId, true);
}

// ── Place Order ──
function placeOrder() {
  const name  = document.getElementById('guestName')?.value.trim()  || '';
  const phone = document.getElementById('guestPhone')?.value.trim() || '';
  const time  = document.getElementById('guestTime')?.value         || '';

  if (!name || !phone || !time) {
    alert('Please fill in your name, contact number, and preferred delivery time.');
    return;
  }

  const orderItems = Object.keys(cart).map(id => {
    const p = products.find(x => x.id == id);
    return p ? `${p.name} ×${cart[id]}` : '';
  }).filter(Boolean).join(', ');

  const successMsg = document.getElementById('successMsg');
  if (successMsg) {
    successMsg.innerHTML = `Thank you, ${escapeHtml(name)}! Your order (${escapeHtml(orderItems)}) will be delivered around your chosen time. We'll text you at ${escapeHtml(phone)}.`;
  }

  const box = document.getElementById('successBox');
  if (box) { box.style.display = 'block'; box.scrollIntoView({ behavior: 'smooth' }); }

  cart = {};
  renderCart();
  ['guestName','guestPhone','guestNotes'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  const sel = document.getElementById('guestTime'); if (sel) sel.value = '';

  setTimeout(() => { if (box) box.style.display = 'none'; }, 5000);
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
window.openModal         = openModal;
window.closeModal        = closeModal;
window.adjustEditQty     = adjustEditQty;
window.saveEdit          = saveEdit;
window.deleteFromCart    = deleteFromCart;
window.openDeleteModal   = openDeleteModal;
window.closeDeleteModal  = closeDeleteModal;
window.confirmDelete     = confirmDelete;
window.openDeleteFromEdit = openDeleteFromEdit;
window.placeOrder        = placeOrder;

// ── Init ──
renderProducts();
renderCart();