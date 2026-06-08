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

// ── Handle status update ──
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

// ── Fetch all status options for dropdown ──
$status_options = $pdo->query("SELECT status_id, status_name FROM order_statuses ORDER BY status_id")->fetchAll();

// ── Fetch orders with status name ──
$status_filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($status_filter, ['Pending Order', 'Harvesting & Purging', 'Live & Packed', 'Completed', 'Cancelled'])) {
    $where = "WHERE os.status_name = ?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare(
    "SELECT co.order_id, co.customer_id, co.quantity_kg, co.price_per_kg,
            co.total_amount, co.pickup_date, co.ordered_at, co.status_id,
            os.status_name
     FROM crayfish_orders co
     JOIN order_statuses os ON co.status_id = os.status_id
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
    COALESCE(SUM(co.total_amount), 0) as total_revenue,
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
    .co-table td { padding: 14px; border-bottom: 1px solid #f5f5f5; font-size: 13px; vertical-align: top; }
    .co-table tr:hover td { background: #fafafa; }

    .co-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .co-badge-pending { background: #fef3c7; color: #92400e; }
    .co-badge-harvesting { background: #ede9fe; color: #5b21b6; }
    .co-badge-packed { background: #dbeafe; color: #1e40af; }
    .co-badge-completed { background: #dcfce7; color: #166534; }
    .co-badge-cancelled { background: #fee2e2; color: #991b1b; }

    .co-status-select { padding: 4px 8px; border-radius: 6px; border: 1px solid #ddd; font-size: 11px; cursor: pointer; font-family: 'Josefin Sans', sans-serif; }
    .co-status-select:focus { border-color: #2F3D2E; outline: none; }

    .co-order-id { font-family: 'Josefin Sans', sans-serif; font-weight: 700; color: #2F3D2E; }
    .co-cell-pickup { max-width: 220px; color: #666; font-size: 12px; line-height: 1.5; word-break: break-word; }

    .co-empty { text-align: center; padding: 3rem; color: #ccc; }
    .co-empty i { font-size: 40px; margin-bottom: 10px; color: #e0e0e0; }
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
              <th>Pickup Date</th>
              <th>Ordered</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o):
              $badgeMap = ['Pending Order' => 'co-badge-pending', 'Harvesting & Purging' => 'co-badge-harvesting', 'Live & Packed' => 'co-badge-packed', 'Completed' => 'co-badge-completed', 'Cancelled' => 'co-badge-cancelled'];
              $badge = $badgeMap[$o['status_name']] ?? 'co-badge-pending';
          ?>
            <tr>
              <td><span class="co-order-id">#<?php echo $o['order_id']; ?></span></td>
              <td>
                <?php if ($o['customer_id']): ?>
                  <strong>Customer #<?php echo $o['customer_id']; ?></strong>
                <?php else: ?>
                  <span style="color:#999;font-style:italic;">Guest</span>
                <?php endif; ?>
              </td>
              <td><strong><?php echo number_format($o['quantity_kg'], 1); ?> kg</strong></td>
              <td>₱<?php echo number_format($o['price_per_kg'], 0); ?></td>
              <td><strong style="color:#16a34a;">₱<?php echo number_format($o['total_amount'], 0); ?></strong></td>
              <td class="co-cell-pickup"><?php echo $o['pickup_date'] ? htmlspecialchars($o['pickup_date']) : '<span style="color:#bbb;">—</span>'; ?></td>
              <td style="font-size:11px;color:#999;white-space:nowrap;"><?php echo date('M d, Y g:ia', strtotime($o['ordered_at'])); ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                  <select name="new_status" class="co-status-select" onchange="this.form.submit()">
                    <?php foreach ($status_options as $so): ?>
                      <option value="<?php echo htmlspecialchars($so['status_name']); ?>" <?php echo $o['status_id'] == $so['status_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($so['status_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
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

</body>
</html>
