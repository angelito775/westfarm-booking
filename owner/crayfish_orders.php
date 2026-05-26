<?php
session_start();

// Only owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
$ownerNavActive = 'crayfish-orders';

// ── Handle status update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $allowed = ['pending', 'confirmed', 'delivered', 'cancelled'];
    if ($order_id > 0 && in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE crayfish_orders SET order_status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
    }
    header("Location: crayfish_orders.php");
    exit();
}

// ── Fetch orders ──
$status_filter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($status_filter, ['pending', 'confirmed', 'delivered', 'cancelled'])) {
    $where = "WHERE order_status = ?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare("SELECT * FROM crayfish_orders $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// ── Stats ──
$stats = $pdo->query("SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    COALESCE(SUM(total_amount), 0) as total_revenue,
    COALESCE(SUM(weight_kg), 0) as total_kg
    FROM crayfish_orders")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crayfish Orders | West Farm Owner Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/owner_dashboard.css">
  <style>
    .crayfish-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; margin-bottom: 1.5rem; }
    .stat-card { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb; text-align: center; }
    .stat-card .num { font-family: 'Josefin Sans', sans-serif; font-size: 28px; font-weight: 700; color: #2F3D2E; }
    .stat-card .lbl { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
    .stat-card.pending .num { color: #d97706; }
    .stat-card.confirmed .num { color: #2563eb; }
    .stat-card.delivered .num { color: #16a34a; }
    .stat-card.revenue .num { color: #7c3aed; }
    .filter-tabs { display: flex; gap: 6px; margin-bottom: 1rem; flex-wrap: wrap; }
    .filter-tab { padding: 6px 16px; border-radius: 20px; border: 1px solid #e5e7eb; background: #fff; font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #888; text-decoration: none; transition: all 0.2s; }
    .filter-tab:hover { border-color: #2F3D2E; color: #2F3D2E; }
    .filter-tab.active { background: #2F3D2E; color: #fff; border-color: #2F3D2E; }
    .orders-table { width: 100%; border-collapse: collapse; }
    .orders-table th { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #888; padding: 10px 12px; border-bottom: 2px solid #e5e7eb; text-align: left; }
    .orders-table td { padding: 12px; border-bottom: 1px solid #f0f0f0; font-size: 13px; vertical-align: top; }
    .orders-table tr:hover td { background: #fafafa; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-delivered { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .action-select { padding: 4px 8px; border-radius: 6px; border: 1px solid #ddd; font-size: 12px; cursor: pointer; }
    .action-select:focus { border-color: #2F3D2E; outline: none; }
    .order-id { font-family: 'Josefin Sans', sans-serif; font-weight: 700; color: #2F3D2E; }
    .order-address { max-width: 200px; color: #666; font-size: 12px; line-height: 1.5; }
    .order-notes { max-width: 180px; color: #999; font-size: 11px; font-style: italic; }
    .empty-state { text-align: center; padding: 3rem; color: #bbb; }
    .empty-state i { font-size: 40px; margin-bottom: 10px; }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
  <div class="page-header">
    <h1><i class="fas fa-utensils"></i> Crayfish Orders</h1>
    <p>Manage incoming crayfish orders from guests</p>
  </div>

  <!-- Stats -->
  <div class="crayfish-stats">
    <div class="stat-card">
      <div class="num"><?php echo number_format($stats['total_orders']); ?></div>
      <div class="lbl">Total Orders</div>
    </div>
    <div class="stat-card pending">
      <div class="num"><?php echo number_format($stats['pending']); ?></div>
      <div class="lbl">Pending</div>
    </div>
    <div class="stat-card confirmed">
      <div class="num"><?php echo number_format($stats['confirmed']); ?></div>
      <div class="lbl">Confirmed</div>
    </div>
    <div class="stat-card delivered">
      <div class="num"><?php echo number_format($stats['delivered']); ?></div>
      <div class="lbl">Delivered</div>
    </div>
    <div class="stat-card revenue">
      <div class="num">₱<?php echo number_format($stats['total_revenue'], 0); ?></div>
      <div class="lbl">Total Revenue</div>
    </div>
    <div class="stat-card">
      <div class="num"><?php echo number_format($stats['total_kg'], 1); ?> kg</div>
      <div class="lbl">Total Sold</div>
    </div>
  </div>

  <!-- Filter tabs -->
  <div class="filter-tabs">
    <a href="crayfish_orders.php?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
    <a href="crayfish_orders.php?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
    <a href="crayfish_orders.php?status=confirmed" class="filter-tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
    <a href="crayfish_orders.php?status=delivered" class="filter-tab <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">Delivered</a>
    <a href="crayfish_orders.php?status=cancelled" class="filter-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
  </div>

  <!-- Orders table -->
  <div class="table-card" style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow-x:auto;">
    <?php if (empty($orders)): ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No orders found.</p>
      </div>
    <?php else: ?>
      <table class="orders-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Weight</th>
            <th>Total</th>
            <th>Delivery Time</th>
            <th>Prep</th>
            <th>Notes</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="order-id">#<?php echo $o['order_id']; ?></td>
            <td><strong><?php echo htmlspecialchars($o['guest_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($o['guest_phone']); ?></td>
            <td class="order-address"><?php echo nl2br(htmlspecialchars($o['delivery_address'])); ?></td>
            <td><strong><?php echo number_format($o['weight_kg'], 1); ?> kg</strong></td>
            <td><strong style="color:#16a34a;">₱<?php echo number_format($o['total_amount'], 0); ?></strong></td>
            <td><?php echo htmlspecialchars($o['delivery_time']); ?></td>
            <td><?php echo $o['preparation'] ? htmlspecialchars($o['preparation']) : '—'; ?></td>
            <td class="order-notes"><?php echo $o['notes'] ? htmlspecialchars($o['notes']) : '—'; ?></td>
            <td style="font-size:12px;color:#888;"><?php echo date('M d, Y g:ia', strtotime($o['created_at'])); ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                <select name="new_status" class="action-select status-<?php echo $o['order_status']; ?>" onchange="this.form.submit()">
                  <option value="pending" <?php echo $o['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo $o['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="delivered" <?php echo $o['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                  <option value="cancelled" <?php echo $o['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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

</body>
</html>
