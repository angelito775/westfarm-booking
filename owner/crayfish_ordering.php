<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
require_once '../config/crayfish_settings.php';
$ownerNavActive = 'crayfish-ordering';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Owner';

$cs = getCrayfishSettings();

// ── Fetch order statuses ──
$statuses = $pdo->query("SELECT status_id, status_name, description FROM order_statuses ORDER BY status_id")->fetchAll();

// ── Fetch recent orders ──
$recent_orders = $pdo->query(
    "SELECT co.order_id, co.customer_id, co.quantity_kg, co.price_per_kg,
            co.total_amount, co.pickup_date, co.ordered_at, co.status_id,
            os.status_name
     FROM crayfish_orders co
     JOIN order_statuses os ON co.status_id = os.status_id
     ORDER BY co.ordered_at DESC LIMIT 5"
)->fetchAll();

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

$flash = '';
if (isset($_GET['success']) && $_GET['success'] === 'settings_updated') {
    $flash = 'Settings updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crayfish Ordering | West Farm Owner Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    .co-hero { background: linear-gradient(135deg, #0f2a0f 0%, #1a3a1a 50%, #2c552c 100%); padding: 36px 32px 30px; color: #fff; position: relative; overflow: hidden; }
    .co-hero::after { content: '🦞'; position: absolute; right: 40px; top: 50%; transform: translateY(-50%); font-size: 80px; opacity: 0.08; }
    .co-hero-eyebrow { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: rgba(126,216,126,0.6); margin-bottom: 6px; }
    .co-hero h1 { font-family: 'Dancing Script', cursive; font-size: 32px; font-weight: 700; margin-bottom: 6px; }
    .co-hero p { font-size: 13px; color: rgba(255,255,255,0.5); max-width: 500px; line-height: 1.6; }

    .co-stats { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; padding: 20px 28px 0; margin-bottom: 8px; }
    .co-stat { background: #fff; border: 1px solid #e8e4dc; border-radius: 12px; padding: 16px 14px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.03); }
    .co-stat-num { font-family: 'Josefin Sans', sans-serif; font-size: 24px; font-weight: 700; color: #2F3D2E; }
    .co-stat-label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #999; margin-top: 4px; }
    .co-stat.co-pending .co-stat-num { color: #d97706; }
    .co-stat.co-harvesting .co-stat-num { color: #7c3aed; }
    .co-stat.co-packed .co-stat-num { color: #2563eb; }
    .co-stat.co-completed .co-stat-num { color: #16a34a; }
    .co-stat.co-revenue .co-stat-num { color: #1a3a1a; }

    .co-content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px 28px 28px; }
    .co-card { background: #fff; border: 1px solid #e8e4dc; border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); overflow: hidden; }
    .co-card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 12px; border-bottom: 1px solid #f0ece4; }
    .co-card-header h3 { font-family: 'Josefin Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #2F3D2E; display: flex; align-items: center; gap: 8px; }
    .co-card-body { padding: 18px 20px; }

    .co-product-display { display: flex; gap: 18px; align-items: flex-start; margin-bottom: 20px; }
    .co-product-img { width: 100px; height: 100px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #e8e4dc, #ddd8ce); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 40px; }
    .co-product-img img { width: 100%; height: 100%; object-fit: cover; }
    .co-product-details h4 { font-family: 'Dancing Script', cursive; font-size: 22px; color: #1a3a1a; margin-bottom: 4px; }
    .co-product-details p { font-size: 12px; color: #888; line-height: 1.6; }
    .co-product-price { font-family: 'Josefin Sans', sans-serif; font-size: 28px; font-weight: 700; color: #1a3a1a; margin-top: 8px; }
    .co-product-price span { font-size: 13px; font-weight: 400; color: #999; letter-spacing: 1px; }

    /* Settings form */
    .co-settings-form { margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0ece4; }
    .co-settings-form h4 { font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #2F3D2E; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .co-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .co-field-grid .co-field-full { grid-column: 1 / -1; }
    .co-form-field { display: flex; flex-direction: column; gap: 5px; }
    .co-form-field label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #888; font-weight: 600; }
    .co-form-field input, .co-form-field select { font-family: 'Lora', serif; font-size: 14px; padding: 10px 13px; border-radius: 8px; border: 1.5px solid #e8e4dc; background: #fafaf8; color: #2F3D2E; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
    .co-form-field input:focus, .co-form-field select:focus { border-color: #1a3a1a; box-shadow: 0 0 0 3px rgba(26,58,26,0.08); background: #fff; }
    .co-save-btn { padding: 10px 28px; background: linear-gradient(135deg, #1a3a1a, #2c552c); color: #fff; border: none; border-radius: 50px; font-family: 'Josefin Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer; transition: all 0.25s; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 4px 14px rgba(26,58,26,0.2); margin-top: 6px; }
    .co-save-btn:hover { background: linear-gradient(135deg, #2c552c, #3a7a3a); box-shadow: 0 6px 20px rgba(26,58,26,0.3); transform: translateY(-1px); }

    .co-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
    .co-info-item { background: #f8faf5; border: 1px solid #e8f0e0; border-radius: 10px; padding: 12px 14px; }
    .co-info-item label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #888; display: block; margin-bottom: 4px; }
    .co-info-item .val { font-size: 14px; font-weight: 600; color: #2F3D2E; }

    .co-status-flow { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
    .co-status-step { display: flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; border: 1px solid #e8e4dc; background: #fff; color: #999; }
    .co-status-step.active { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
    .co-status-arrow { color: #ccc; font-size: 10px; }

    .co-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .co-badge-pending { background: #fef3c7; color: #92400e; }
    .co-badge-harvesting { background: #ede9fe; color: #5b21b6; }
    .co-badge-packed { background: #dbeafe; color: #1e40af; }
    .co-badge-completed { background: #dcfce7; color: #166534; }
    .co-badge-cancelled { background: #fee2e2; color: #991b1b; }

    .co-quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .co-quick-btn { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 20px 14px; border: 1.5px solid #e8e4dc; border-radius: 12px; background: #fff; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; }
    .co-quick-btn:hover { border-color: #1a3a1a; background: #f8faf5; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .co-quick-btn i { font-size: 22px; color: #1a3a1a; }
    .co-quick-btn span { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #555; font-weight: 600; }

    .co-order-row { display: flex; align-items: flex-start; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f5f5f5; }
    .co-order-row:last-child { border-bottom: none; }
    .co-order-id { font-family: 'Josefin Sans', sans-serif; font-weight: 700; color: #2F3D2E; font-size: 13px; min-width: 50px; }
    .co-order-meta { font-size: 12px; color: #888; line-height: 1.6; }
    .co-order-meta strong { color: #2F3D2E; }
    .co-order-amt { font-family: 'Josefin Sans', sans-serif; font-weight: 700; color: #16a34a; font-size: 14px; text-align: right; white-space: nowrap; }
    .co-order-time { font-size: 11px; color: #bbb; text-align: right; }

    .co-card-full { grid-column: 1 / -1; }

    .co-flash { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-family: 'Josefin Sans', sans-serif; font-size: 11px; letter-spacing: 0.5px; margin: 0 28px 16px; display: flex; align-items: center; gap: 8px; }

    @media (max-width: 768px) {
      .co-stats { grid-template-columns: repeat(3, 1fr); padding: 16px 16px 0; }
      .co-content { grid-template-columns: 1fr; padding: 16px; }
      .co-hero { padding: 24px 20px; }
      .co-hero::after { display: none; }
    }
    @media (max-width: 520px) {
      .co-stats { grid-template-columns: repeat(2, 1fr); }
      .co-quick-actions { grid-template-columns: 1fr; }
      .co-product-display { flex-direction: column; align-items: center; text-align: center; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
  <header class="topbar">
    <div class="topbar-left">
      <h2 class="topbar-title">Crayfish Ordering</h2>
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

    <?php if ($flash): ?>
    <div class="co-flash"><i class="fas fa-check-circle"></i> <?php echo $flash; ?></div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="co-hero">
      <div class="co-hero-eyebrow"><i class="fas fa-fish"></i> Crayfish Ordering System</div>
      <h1>West Cray Ordering</h1>
      <p>Manage your crayfish product pricing, track order statuses, and oversee all crayfish orders placed by guests and customers.</p>
    </div>

    <!-- Stats -->
    <div class="co-stats">
      <div class="co-stat">
        <div class="co-stat-num"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
        <div class="co-stat-label">Total Orders</div>
      </div>
      <div class="co-stat co-pending">
        <div class="co-stat-num"><?php echo number_format($stats['pending'] ?? 0); ?></div>
        <div class="co-stat-label">Pending</div>
      </div>
      <div class="co-stat co-harvesting">
        <div class="co-stat-num"><?php echo number_format($stats['harvesting'] ?? 0); ?></div>
        <div class="co-stat-label">Harvesting</div>
      </div>
      <div class="co-stat co-packed">
        <div class="co-stat-num"><?php echo number_format($stats['packed'] ?? 0); ?></div>
        <div class="co-stat-label">Packed</div>
      </div>
      <div class="co-stat co-completed">
        <div class="co-stat-num"><?php echo number_format($stats['completed'] ?? 0); ?></div>
        <div class="co-stat-label">Completed</div>
      </div>
      <div class="co-stat co-revenue">
        <div class="co-stat-num">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
        <div class="co-stat-label">Revenue</div>
      </div>
    </div>

    <!-- Content grid -->
    <div class="co-content">

      <!-- Product Settings Card -->
      <div class="co-card">
        <div class="co-card-header">
          <h3><i class="fas fa-cog"></i> Product Settings</h3>
          <span class="co-badge co-badge-packed">Active</span>
        </div>
        <div class="co-card-body">
          <form method="POST" action="../logic/crayfish_settings_process.php" class="co-settings-form">
            <input type="hidden" name="action" value="update_settings">

            <div class="co-field-grid">
              <div class="co-form-field">
                <label>Product Name</label>
                <input type="text" name="product_name" value="<?php echo htmlspecialchars($cs['product_name']); ?>">
              </div>
              <div class="co-form-field">
                <label>Price per Kg (₱)</label>
                <input type="number" name="price_per_kg" value="<?php echo htmlspecialchars($cs['price_per_kg']); ?>" min="1" step="0.01">
              </div>
              <div class="co-form-field">
                <label>Min Order (kg)</label>
                <input type="number" name="min_order_kg" value="<?php echo htmlspecialchars($cs['min_order_kg']); ?>" min="0.1" step="0.1">
              </div>
              <div class="co-form-field">
                <label>Max Order (kg)</label>
                <input type="number" name="max_order_kg" value="<?php echo htmlspecialchars($cs['max_order_kg']); ?>" min="1" step="1">
              </div>
            </div>

            <button type="submit" class="co-save-btn"><i class="fas fa-save"></i> Save Settings</button>
          </form>

          <div style="margin-top:20px; padding:14px; background:#f8faf5; border:1px solid #e8f0e0; border-radius:10px;">
            <label style="font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#888;display:block;margin-bottom:8px;">
              <i class="fas fa-info-circle"></i> Current Public-Facing Info
            </label>
            <div class="co-info-grid">
              <div class="co-info-item"><label>Product</label><div class="val"><?php echo htmlspecialchars($cs['product_name']); ?></div></div>
              <div class="co-info-item"><label>Price</label><div class="val">₱<?php echo number_format($cs['price_per_kg'], 2); ?>/kg</div></div>
              <div class="co-info-item"><label>Min Order</label><div class="val"><?php echo number_format($cs['min_order_kg'], 1); ?> kg</div></div>
              <div class="co-info-item"><label>Max Order</label><div class="val"><?php echo number_format($cs['max_order_kg'], 0); ?> kg</div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Status Flow -->
      <div class="co-card">
        <div class="co-card-header">
          <h3><i class="fas fa-stream"></i> Order Status Flow</h3>
        </div>
        <div class="co-card-body">
          <p style="font-size:12px; color:#888; margin-bottom:14px; line-height:1.6;">
            Crayfish orders follow this lifecycle. Update statuses as you process each order.
          </p>
          <div class="co-status-flow">
            <?php
            $statusIcons = ['Pending Order' => 'fa-clock', 'Harvesting & Purging' => 'fa-water', 'Live & Packed' => 'fa-box', 'Completed' => 'fa-check-circle', 'Cancelled' => 'fa-times-circle'];
            $badgeClasses = ['Pending Order' => 'co-badge-pending', 'Harvesting & Purging' => 'co-badge-harvesting', 'Live & Packed' => 'co-badge-packed', 'Completed' => 'co-badge-completed', 'Cancelled' => 'co-badge-cancelled'];
            foreach ($statuses as $i => $s):
                $icon = $statusIcons[$s['status_name']] ?? 'fa-circle';
                $badge = $badgeClasses[$s['status_name']] ?? 'co-badge-pending';
            ?>
              <div class="co-status-step <?php echo $i === 0 ? 'active' : ''; ?>">
                <i class="fas <?php echo $icon; ?>"></i>
                <?php echo htmlspecialchars($s['status_name']); ?>
              </div>
              <?php if ($i < count($statuses) - 1): ?>
                <i class="fas fa-chevron-right co-status-arrow"></i>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:16px;">
            <label style="font-family:'Josefin Sans',sans-serif; font-size:9px; letter-spacing:1.5px; text-transform:uppercase; color:#888; display:block; margin-bottom:8px;">Status Descriptions</label>
            <?php foreach ($statuses as $s): ?>
              <div style="display:flex; align-items:flex-start; gap:10px; padding:8px 0; border-bottom:1px solid #f5f5f5;">
                <span class="co-badge <?php echo $badgeClasses[$s['status_name']] ?? 'co-badge-pending'; ?>" style="margin-top:2px; flex-shrink:0;"><?php echo htmlspecialchars($s['status_name']); ?></span>
                <span style="font-size:12px; color:#888; line-height:1.5;"><?php echo htmlspecialchars($s['description']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="co-card">
        <div class="co-card-header">
          <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="co-card-body">
          <div class="co-quick-actions">
            <a href="crayfish_orders.php" class="co-quick-btn"><i class="fas fa-clipboard-list"></i><span>View All Orders</span></a>
            <a href="crayfish_orders.php?status=Pending+Order" class="co-quick-btn"><i class="fas fa-hourglass-half"></i><span>Pending Orders</span></a>
            <a href="../public/westcrays.php" class="co-quick-btn" target="_blank"><i class="fas fa-external-link-alt"></i><span>Customer View</span></a>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="co-card">
        <div class="co-card-header">
          <h3><i class="fas fa-history"></i> Recent Orders</h3>
          <a href="crayfish_orders.php" style="font-family:'Josefin Sans',sans-serif; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:#1a3a1a; text-decoration:none; font-weight:600;">View All →</a>
        </div>
        <div class="co-card-body">
          <?php if (empty($recent_orders)): ?>
            <div style="text-align:center; padding:2.5rem 1rem; color:#ccc;"><i class="fas fa-inbox" style="font-size:36px; margin-bottom:10px; color:#e0e0e0;"></i><p style="font-size:13px;">No crayfish orders yet.</p></div>
          <?php else: ?>
            <?php foreach ($recent_orders as $o):
                $badgeMap = ['Pending Order' => 'co-badge-pending', 'Harvesting & Purging' => 'co-badge-harvesting', 'Live & Packed' => 'co-badge-packed', 'Completed' => 'co-badge-completed', 'Cancelled' => 'co-badge-cancelled'];
                $badge = $badgeMap[$o['status_name']] ?? 'co-badge-pending';
            ?>
              <div class="co-order-row">
                <div>
                  <div class="co-order-id">#<?php echo $o['order_id']; ?></div>
                  <span class="co-badge <?php echo $badge; ?>" style="margin-top:4px;"><?php echo htmlspecialchars($o['status_name']); ?></span>
                </div>
                <div style="flex:1;">
                  <div class="co-order-meta">
                    <strong><?php echo number_format($o['quantity_kg'], 1); ?> kg</strong> × ₱<?php echo number_format($o['price_per_kg'], 0); ?>/kg
                    <?php if ($o['customer_id']): ?><br><i class="fas fa-user"></i> Customer #<?php echo $o['customer_id']; ?>
                    <?php else: ?><br><i class="fas fa-user"></i> Guest order<?php endif; ?>
                    <?php if ($o['pickup_date']): ?><br><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($o['pickup_date']); ?><?php endif; ?>
                  </div>
                </div>
                <div>
                  <div class="co-order-amt">₱<?php echo number_format($o['total_amount'], 0); ?></div>
                  <div class="co-order-time"><?php echo date('M d, g:ia', strtotime($o['ordered_at'])); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </main>

  <div class="dashboard-footer">© 2026 West Farm Resort and Hotel · Basista, Pangasinan</div>
</div>

</body>
</html>
