<?php
session_start();

// Security check: only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 1) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

// Get current admin info for topbar
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Administrator';

// Fetch all payment methods
$stmt = $pdo->query("SELECT payment_method_id, method_name, is_active FROM payment_methods ORDER BY method_name ASC");
$methods = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo-svg" viewBox="0 0 40 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 0L32 24H8L20 0Z" fill="#2F3D2E" />
                        <path d="M20 4L28 20H12L20 4Z" fill="#FAF8F4" />
                        <path d="M8 26H32V28H8V26Z" fill="#2F3D2E" />
                        <path d="M12 30H28V31H12V30Z" fill="#2F3D2E" />
                    </svg>
                    <div class="logo-text">
                        <h1>West Farm</h1>
                        <p>Resort and Hotel</p>
                    </div>
                </div>
                <span class="portal-badge">Admin Portal</span>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Categories</span>
                </a>
                <a href="payment_methods.php" class="nav-item active">
                    <i class="fas fa-money-check-alt"></i>
                    <span>Payment Methods</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="logout-btn" id="openLogoutModalBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Payment Methods & Availability</h2>
                </div>
                <div class="topbar-right">
                    <div class="user-section">
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <div class="user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success">
                        <?php
                        if ($_GET['success'] == 'method_added') echo "New payment method added successfully.";
                        if ($_GET['success'] == 'method_updated') echo "Payment method status updated.";
                        if ($_GET['success'] == 'method_deleted') echo "Payment method deleted securely.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php
                        if ($_GET['error'] == 'add_failed') echo "Error: Could not add payment method.";
                        if ($_GET['error'] == 'update_failed') echo "Error: Could not update status.";
                        if ($_GET['error'] == 'delete_failed') echo "Error: Cannot delete a method that has existing payments tied to it.";
                        if ($_GET['error'] == 'name_taken') echo "Error: A method with this name already exists.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Manage Payment Gateways</h3>
                        <button id="addMethodBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i> Add Method
                        </button>
                    </div>
                    <div class="section-body">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Payment Method Name</th>
                                        <th>Current Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($methods as $method): ?>
                                        <tr>
                                            <td style="color: rgba(47, 61, 46, 0.8);">#<?php echo $method['payment_method_id']; ?></td>
                                            <td style="font-weight: 600; color: #2F3D2E;"><?php echo htmlspecialchars($method['method_name']); ?></td>
                                            <td>
                                                <?php if ($method['is_active'] == 1): ?>
                                                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Active</span>
                                                <?php else: ?>
                                                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <button class="action-btn edit-btn" title="Edit Availability"
                                                    data-id="<?php echo $method['payment_method_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($method['method_name']); ?>"
                                                    data-status="<?php echo $method['is_active']; ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="action-btn delete-btn" title="Delete Method"
                                                    data-id="<?php echo $method['payment_method_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($method['method_name']); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <div class="dashboard-footer">
                © 2026 West Farm Resort and Hotel
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Sign Out</h3>
                <button class="modal-close" onclick="closeModal('logoutConfirmModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to sign out of your account?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('logoutConfirmModal')">Stay</button>
                <a href="../logic/logout.php" class="btn-danger">Sign Out</a>
            </div>
        </div>
    </div>

    <div id="methodModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 id="methodModalTitle" class="modal-title">Add Payment Method</h3>
                <button class="modal-close" onclick="closeModal('methodModal')">&times;</button>
            </div>
            <form id="methodForm" action="../logic/payment_method_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="methodAction" value="add_method">
                    <input type="hidden" name="payment_method_id" id="editMethodId">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Method Name</label>
                        <input type="text" id="method_name" name="method_name" required placeholder="e.g. GCash, Maya, Bank Transfer" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Availability Status</label>
                        <select id="is_active" name="is_active" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="1">Active (Visible to Customers)</option>
                            <option value="0">Disabled (Hidden from Checkout)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('methodModal')">Cancel</button>
                    <button type="submit" id="methodSubmitBtn" class="btn-primary">Save Method</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form action="../logic/payment_method_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_method">
                    <input type="hidden" name="payment_method_id" id="deleteMethodId">
                    <p>Are you sure you want to delete <strong id="deleteMethodName"></strong>?</p>
                    <p style="color: #ef4444; font-size: 13px; margin-top: 10px;">Best Practice: Instead of deleting, it is usually better to 'Disable' a payment method so historical payment records don't break.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.classList.contains('modal-overlay')) closeModal(event.target.id); }

        document.getElementById('addMethodBtn').addEventListener('click', function() {
            document.getElementById('methodForm').reset();
            document.getElementById('methodModalTitle').innerText = 'Add Payment Method';
            document.getElementById('methodAction').value = 'add_method';
            document.getElementById('editMethodId').value = '';
            document.getElementById('methodSubmitBtn').innerText = 'Add Method';
            document.getElementById('is_active').value = '1';
            openModal('methodModal');
        });

        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('methodModalTitle').innerText = 'Edit Availability';
                document.getElementById('methodAction').value = 'edit_method';
                document.getElementById('methodSubmitBtn').innerText = 'Save Changes';
                
                document.getElementById('editMethodId').value = this.dataset.id;
                document.getElementById('method_name').value = this.dataset.name;
                document.getElementById('is_active').value = this.dataset.status;
                
                openModal('methodModal');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('deleteMethodId').value = this.dataset.id;
                document.getElementById('deleteMethodName').innerText = this.dataset.name;
                openModal('deleteModal');
            });
        });

        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', function() { this.parentElement.style.display = 'none'; });
        });

        // Logout confirmation
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('logoutConfirmModal');
        });
    </script>
</body>
</html>