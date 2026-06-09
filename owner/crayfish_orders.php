<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
$ownerNavActive = 'crayfish-orders';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Owner';

// ── Handle status update (inline dropdown) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status_name = $_POST['new_status'] ?? '';
    if ($order_id > 0 && !empty($new_status_name)) {
        $stmt_s = $pdo->prepare("SELECT status_id FROM order_statuses WHERE status_name = ? LIMIT 1");
        $stmt_s->execute([$new_status_name]);
        $row_s = $stmt_s->fetch();
        if ($row_s) {
            $stmt_u = $pdo->prepare("UPDATE crayfish_orders SET status_id = ? WHERE order_id = ?");
            $stmt_u->execute([$row_s['status_id'], $order_id]);
        }
    }
    header("Location: crayfish_orders.php" . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

// ── Handle edit order (status + payment) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_order') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status_name = $_POST['order_status'] ?? '';
    $new_pay_status = $_POST['payment_status'] ?? '';
    $pay_method_name = $_POST['payment_method'] ?? '';
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);

    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();

            // Resolve status_id
            $status_id = null;
            if (!empty($new_status_name)) {
                $stmt_s = $pdo->prepare("SELECT status_id FROM order_statuses WHERE status_name = ? LIMIT 1");
                $stmt_s->execute([$new_status_name]);
                $row = $stmt_s->fetch();
                if ($row) $status_id = $row['status_id'];
            }

            // Resolve payment_status_id
            $pay_status_id = null;
            if (!empty($new_pay_status)) {
                $stmt_ps = $pdo->prepare("SELECT payment_status_id FROM payment_statuses WHERE status_name = ? LIMIT 1");
                $stmt_ps->execute([$new_pay_status]);
                $row = $stmt_ps->fetch();
                if ($row) $pay_status_id = $row['payment_status_id'];
            }

            // Resolve payment_method_id
            $pay_method_id = null;
            if (!empty($pay_method_name)) {
                $stmt_pm = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_name = ? AND is_active = 1 LIMIT 1");
                $stmt_pm->execute([$pay_method_name]);
                $row = $stmt_pm->fetch();
                if ($row) $pay_method_id = $row['payment_method_id'];
            }

            // Build dynamic update
            $fields = [];
            $params = [];
            if ($status_id) { $fields[] = "status_id = ?"; $params[] = $status_id; }
            if ($pay_status_id) { $fields[] = "payment_status_id = ?"; $params[] = $pay_status_id; }
            if ($pay_method_id) { $fields[] = "payment_method_id = ?"; $params[] = $pay_method_id; }
            $fields[] = "amount_paid = ?"; $params[] = $amount_paid;

            if (!empty($fields)) {
                $params[] = $order_id;
                $sql = "UPDATE crayfish_orders SET " . implode(', ', $fields) . " WHERE order_id = ?";
                $stmt_u = $pdo->prepare($sql);
                $stmt_u->execute($params);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('crayfish_orders edit error: ' . $e->getMessage());
        }
    }
    header("Location: crayfish_orders.php" . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

// ── Fetch all status options for dropdown ──
$status_options = $pdo->query("SELECT status_id, status_name FROM order_statuses ORDER BY status_id")->fetchAll();
$pay_status_options = $pdo->query("SELECT payment_status_id, status_name FROM payment_statuses ORDER BY payment_status_id")->fetchAll();
$pay_method_options = $pdo->query("SELECT payment_method_id, method_name FROM payment_methods WHERE is_active = 1 ORDER BY payment_method_id")->fetchAll();

// ── Fetch orders with status + payment info ──
$status_filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($status_filter, ['Pending Order', 'Harvesting & Purging', 'Live & Packed', 'Completed', 'Cancelled'])) {
    $where = "WHERE os.status_name = ?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare(
    "SELECT co.order_id, co.customer_id, co.quantity_kg, co.price_per_kg,
            co.total_amount, co.amount_paid, co.pickup_date, co.ordered_at,
            co.status_id, co.payment_status_id, co.payment_method_id,
            os.status_name,
            COALESCE(ps.status_name, 'Unpaid') AS payment_status_name,
            COALESCE(pm.method_name, '-') AS payment_method_name
     FROM crayfish_orders co
     JOIN order_statuses os ON co.status_id = os.status_id
     LEFT JOIN payment_statuses ps ON co.payment_status_id = ps.payment_status_id
     LEFT JOIN payment_methods pm ON co.payment_method_id = pm.payment_method_id
     $where
     ORDER BY co.ordered_at DESC"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// ── Stats ──
$stats = $pdo->query("SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN os.status_name = 'Pending Order' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN os.status_name = 'Harvesting & Purging' THEN 1 ELSE 0 END) as harvesting,
    SUM(CASE WHEN os.status_name = 'Live & Packed' THEN 1 ELSE 0 END) as packed,
    SUM(CASE WHEN os.status_name = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN os.status_name = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
    COALESCE(SUM(CASE WHEN os.status_name != 'Cancelled' THEN co.total_amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(co.quantity_kg), 0) as total_kg
    FROM crayfish_orders co
    JOIN order_statuses os ON co.status_id = os.status_id"
)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crayfish Orders | West Farm Owner Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    .co-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 1.5rem; }
    .co-stat-card { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e8e4dc; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.03); }
    .co-stat-card .co-num { font-family: 'Josefin Sans', sans-serif; font-size: 26px; font-weight: 700; color: #2F3D2E; }
    .co-stat-card .co-lbl { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #999; margin-top: 4px; }
    .co-stat-card.co-pending .co-num { color: #d97706; }
    .co-stat-card.co-harvesting .co-num { color: #7c3aed; }
    .co-stat-card.co-packed .co-num { color: #2563eb; }
    .co-stat-card.co-completed .co-num { color: #16a34a; }
    .co-stat-card.co-revenue .co-num { color: #1a3a1a; }

    .co-filter-tabs { display: flex; gap: 6px; margin-bottom: 1rem; flex-wrap: wrap; }
    .co-filter-tab { padding: 6px 16px; border-radius: 20px; border: 1px solid #e8e4dc; background: #fff; font-family: 'Josefin Sans', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #888; text-decoration: none; transition: all 0.2s; }
    .co-filter-tab:hover { border-color: #2F3D2E; color: #2F3D2E; }
    .co-filter-tab.active { background: #2F3D2E; color: #fff; border-color: #2F3D2E; }

    .co-table-wrap { background: #fff; border-radius: 14px; border: 1px solid #e8e4dc; overflow-x: auto; box-shadow: 0 1px 4px rgba(0,0,0,0.03); }
    .co-table { width: 100%; border-collapse: collapse; }
    .co-table th { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #999; padding: 12px 14px; border-bottom: 2px solid #e8e4dc; text-align: left; white-space: nowrap; }
    .co-table td { padding: 14px; border-bottom: 1px solid #f5f5f5; font-size: 13px; vertical-align: middle; }
    .co-table tr:hover td { background: #fafafa; }

    .co-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .co-badge-pending { background: #fef3c7; color: #92400e; }
    .co-badge-harvesting { background: #ede9fe; color: #5b21b6; }
    .co-badge-packed { background: #dbeafe; color: #1e40af; }
    .co-badge-completed { background: #dcfce7; color: #166534; }
    .co-badge-cancelled { background: #fee2e2; color: #991b1b; }

    .co-order-id { font-family: 'Josefin Sans', sans-serif; font-weight: 700; color: #2F3D2E; }
    .co-cell-pickup { max-width: 220px; color: #666; font-size: 12px; line-height: 1.5; word-break: break-word; }
    .co-cell-actions { white-space: nowrap; }

    .co-empty { text-align: center; padding: 3rem; color: #ccc; }
    .co-empty i { font-size: 40px; margin-bottom: 10px; color: #e0e0e0; }

    /* Edit modal */
    .co-modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px; }
    .co-modal { background:#fff; border-radius:10px; width:100%; max-width:480px; box-shadow:0 10px 40px rgba(0,0,0,0.15); overflow:hidden; }
    .co-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #eee; background:linear-gradient(135deg,#0f2a0f,#1a3a1a); }
    .co-modal-header h3 { margin:0; font-family:'Josefin Sans',sans-serif; font-size:15px; font-weight:700; color:#fff; }
    .co-modal-x { background:none; border:none; font-size:20px; color:rgba(255,255,255,0.7); cursor:pointer; line-height:1; }
    .co-modal-x:hover { color:#fff; }
    .co-modal-body { padding:20px; }
    .co-modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #eee; background:#fafafa; }
    .co-field { margin-bottom:12px; }
    .co-field label { display:block; font-family:'Josefin Sans',sans-serif; font-size:9px; letter-spacing:1.5px; text-transform:uppercase; color:#888; font-weight:600; margin-bottom:5px; }
    .co-field select,
    .co-field input { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; font-family:'Lora',serif; font-size:13px; outline:none; transition:border-color .2s; }
    .co-field select:focus,
    .co-field input:focus { border-color:#1a3a1a; }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
  <header class="topbar">
    <div class="topbar-left">
      <h2 class="topbar-title">Crayfish Orders</h2>
    </div>
    <div class="topbar-right">
      <button class="notification-btn"><i class="fas fa-bell"></i><span class="notification-dot"></span></button>
      <div class="user-section">
        <div class="user-info">
          <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
          <p class="user-role">Owner</p>
        </div>
        <div class="user-avatar"><i class="fas fa-user"></i></div>
      </div>
    </div>
  </header>

  <main class="main-content">

    <!-- Stats -->
    <div class="co-stats">
      <div class="co-stat-card">
        <div class="co-num"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
        <div class="co-lbl">Total Orders</div>
      </div>
      <div class="co-stat-card co-pending">
        <div class="co-num"><?php echo number_format($stats['pending'] ?? 0); ?></div>
        <div class="co-lbl">Pending</div>
      </div>
      <div class="co-stat-card co-harvesting">
        <div class="co-num"><?php echo number_format($stats['harvesting'] ?? 0); ?></div>
        <div class="co-lbl">Harvesting</div>
      </div>
      <div class="co-stat-card co-packed">
        <div class="co-num"><?php echo number_format($stats['packed'] ?? 0); ?></div>
        <div class="co-lbl">Packed</div>
      </div>
      <div class="co-stat-card co-completed">
        <div class="co-num"><?php echo number_format($stats['completed'] ?? 0); ?></div>
        <div class="co-lbl">Completed</div>
      </div>
      <div class="co-stat-card co-revenue">
        <div class="co-num">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
        <div class="co-lbl">Revenue</div>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="co-filter-tabs">
      <a href="crayfish_orders.php" class="co-filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
      <?php foreach ($status_options as $so): ?>
        <a href="crayfish_orders.php?status=<?php echo urlencode($so['status_name']); ?>"
           class="co-filter-tab <?php echo $status_filter === $so['status_name'] ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($so['status_name']); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Orders table -->
    <div class="co-table-wrap">
      <?php if (empty($orders)): ?>
        <div class="co-empty"><i class="fas fa-inbox"></i><p>No orders found.</p></div>
      <?php else: ?>
        <table class="co-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Weight</th>
              <th>Price/kg</th>
              <th>Total</th>
              <th>Paid</th>
              <th>Pickup Date</th>
              <th>Ordered</th>
              <th>Status</th>
              <th>Payment</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o):
              $badgeMap = ['Pending Order' => 'co-badge-pending', 'Harvesting & Purging' => 'co-badge-harvesting', 'Live & Packed' => 'co-badge-packed', 'Completed' => 'co-badge-completed', 'Cancelled' => 'co-badge-cancelled'];
              $badge = $badgeMap[$o['status_name']] ?? 'co-badge-pending';
              $o_remaining = floatval($o['total_amount']) - floatval($o['amount_paid']);
          ?>
            <tr data-order-id="<?php echo $o['order_id']; ?>"
                data-status="<?php echo htmlspecialchars($o['status_name']); ?>"
                data-pay-status="<?php echo htmlspecialchars($o['payment_status_name']); ?>"
                data-pay-method="<?php echo htmlspecialchars($o['payment_method_name']); ?>"
                data-amount-paid="<?php echo number_format(floatval($o['amount_paid']), 2); ?>">
              <td><span class="co-order-id">#<?php echo $o['order_id']; ?></span></td>
              <td>
                <?php if ($o['customer_id']): ?>
                  <strong>Customer #<?php echo $o['customer_id']; ?></strong><br>
                <?php else: ?>
                  <span style="color:#999;font-style:italic;">Guest</span><br>
                <?php endif; ?>
                <span class="co-badge <?php echo $badge; ?>" style="margin-top:4px;"><?php echo htmlspecialchars($o['status_name']); ?></span>
              </td>
              <td><strong><?php echo number_format($o['quantity_kg'], 1); ?> kg</strong></td>
              <td>₱<?php echo number_format($o['price_per_kg'], 0); ?></td>
              <td><strong style="color:#16a34a;">₱<?php echo number_format($o['total_amount'], 0); ?></strong></td>
              <td>
                <strong style="color:<?php echo floatval($o['amount_paid']) > 0 ? 'var(--red)' : '#999'; ?>;">
                  ₱<?php echo number_format(floatval($o['amount_paid']), 0); ?>
                </strong>
                <?php if ($o_remaining > 0 && $o['status_name'] !== 'Cancelled'): ?>
                  <br><span style="font-size:10px;color:var(--red);">Bal: ₱<?php echo number_format($o_remaining, 0); ?></span>
                <?php endif; ?>
              </td>
              <td class="co-cell-pickup"><?php echo $o['pickup_date'] ? htmlspecialchars(date('M d, Y h:i A', strtotime($o['pickup_date']))) : '<span style="color:#bbb;">—</span>'; ?></td>
              <td style="font-size:11px;color:#999;white-space:nowrap;"><?php echo date('M d, Y g:ia', strtotime($o['ordered_at'])); ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                  <select name="new_status" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid #ddd;font-size:11px;cursor:pointer;font-family:'Josefin Sans',sans-serif;outline:none;">
                    <?php foreach ($status_options as $so): ?>
                      <option value="<?php echo htmlspecialchars($so['status_name']); ?>" <?php echo $o['status_id'] == $so['status_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($so['status_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td>
                <span style="font-size:12px;font-weight:600;color:<?php echo $o['payment_status_name'] === 'Paid' ? '#16a34a' : ($o['payment_status_name'] === 'Partial' ? '#d97706' : '#c0392b'); ?>">
                  <?php echo htmlspecialchars($o['payment_status_name']); ?>
                </span>
                <?php if ($o['payment_method_name'] && $o['payment_method_name'] !== '-'): ?>
                  <br><span style="font-size:11px;color:#999;"><?php echo htmlspecialchars($o['payment_method_name']); ?></span>
                <?php endif; ?>
              </td>
              <td class="co-cell-actions" style="text-align:right;">
                <a href="../pages/crayfish_receipt.php?order_id=<?php echo $o['order_id']; ?>" class="action-btn" title="View Receipt" style="color:var(--forest);border:1px solid var(--border);background:#f8faf5;padding:4px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;margin-right:4px;text-decoration:none;">
                  <i class="fas fa-receipt"></i>
                </a>
                <button type="button" class="action-btn" title="Edit Order" onclick="openEditModal(this)" style="color:#3b82f6;border:1px solid #bfdbfe;background:#eff6ff;padding:4px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
                  <i class="fas fa-pencil-alt"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>

  <div class="dashboard-footer">© 2026 West Farm Resort and Hotel · Basista, Pangasinan</div>
</div>

<!-- Edit Order Modal -->
<div id="coEditModalBg" class="co-modal-bg" onclick="if(event.target===this) closeEditModal();">
  <div class="co-modal">
    <div class="co-modal-header">
      <h3><i class="fas fa-edit"></i> Edit Order</h3>
      <button class="co-modal-x" onclick="closeEditModal()">&times;</button>
    </div>
    <form method="POST" id="coEditForm">
      <input type="hidden" name="action" value="edit_order">
      <input type="hidden" name="order_id" id="co_edit_order_id" value="">
      <div class="co-modal-body">
        <!-- Order summary -->
        <div style="background:#f9fafb;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #e5e7eb;">
          <p style="margin:0 0 4px 0;font-size:13px;color:#6b7280;">Order Summary</p>
          <p style="margin:0;font-weight:600;color:#2F3D2E;" id="co_edit_summary"></p>
        </div>

        <h4 style="margin:0 0 10px 0;color:#2F3D2E;border-bottom:1px solid #eee;padding-bottom:5px;">Order Status</h4>
        <div class="co-field">
          <label>Order Status</label>
          <select name="order_status" id="co_edit_status">
            <?php foreach ($status_options as $so): ?>
              <option value="<?php echo htmlspecialchars($so['status_name']); ?>"><?php echo htmlspecialchars($so['status_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <h4 style="margin:16px 0 10px 0;color:#2F3D2E;border-bottom:1px solid #eee;padding-bottom:5px;">Payment Details</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="co-field">
            <label>Payment Method</label>
            <select name="payment_method" id="co_edit_pay_method">
              <option value="">— Not set —</option>
              <?php foreach ($pay_method_options as $pm): ?>
                <option value="<?php echo htmlspecialchars($pm['method_name']); ?>"><?php echo htmlspecialchars($pm['method_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="co-field">
            <label>Payment Status</label>
            <select name="payment_status" id="co_edit_pay_status">
              <?php foreach ($pay_status_options as $ps): ?>
                <option value="<?php echo htmlspecialchars($ps['status_name']); ?>"><?php echo htmlspecialchars($ps['status_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="co-field">
          <label>Amount Paid (₱)</label>
          <input type="number" name="amount_paid" id="co_edit_amount_paid" min="0" step="0.01" value="0">
        </div>
      </div>
      <div class="co-modal-footer">
        <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(btn) {
    var row = btn.closest('tr');
    var orderId = row.dataset.orderId;
    var status = row.dataset.status;
    var payStatus = row.dataset.payStatus;
    var payMethod = row.dataset.payMethod;
    var amountPaid = row.dataset.amountPaid;

    document.getElementById('co_edit_order_id').value = orderId;
    document.getElementById('co_edit_summary').textContent = 'Order #' + orderId;

    // Set status
    var statusSelect = document.getElementById('co_edit_status');
    for (var i = 0; i < statusSelect.options.length; i++) {
        if (statusSelect.options[i].value === status) {
            statusSelect.selectedIndex = i;
            break;
        }
    }

    // Set payment status
    var payStatusSelect = document.getElementById('co_edit_pay_status');
    for (var i = 0; i < payStatusSelect.options.length; i++) {
        if (payStatusSelect.options[i].value === payStatus) {
            payStatusSelect.selectedIndex = i;
            break;
        }
    }

    // Set payment method
    var payMethodSelect = document.getElementById('co_edit_pay_method');
    for (var i = 0; i < payMethodSelect.options.length; i++) {
        if (payMethodSelect.options[i].value === payMethod) {
            payMethodSelect.selectedIndex = i;
            break;
        }
    }

    // Set amount paid (strip commas)
    document.getElementById('co_edit_amount_paid').value = parseFloat(amountPaid.replace(/,/g, '')) || 0;

    document.getElementById('coEditModalBg').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('coEditModalBg').style.display = 'none';
}
</script>

<div id="logoutConfirmModal" class="modal-overlay" style="display:none;">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h3 class="modal-title">Confirm Sign Out</h3>
      <button class="modal-close" onclick="document.getElementById('logoutConfirmModal').style.display='none'">&times;</button>
    </div>
    <div class="modal-body"><p>Are you sure you want to sign out?</p></div>
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="document.getElementById('logoutConfirmModal').style.display='none'">Stay</button>
      <a href="../logic/logout.php" class="btn-danger">Sign Out</a>
    </div>
  </div>
</div>

</body>
</html>
