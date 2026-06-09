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

// Fetch all payment records with related booking, customer, and method data
$stmt = $pdo->query("
    SELECT 
        p.payment_id,
        p.booking_id,
        p.amount_paid,
        p.transaction_id,
        p.payment_date,
        pm.method_name,
        up.first_name,
        up.last_name,
        ps.status_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN user_profiles up ON b.customer_id = up.user_id
    JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
    JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
    ORDER BY p.payment_date DESC
");
$payments = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Ledger | Admin Dashboard</title>
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
                    <i class="fas fa-wallet"></i>
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
                    <h2 class="topbar-title">System Payments Ledger</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="paymentSearchInput" placeholder="Search by customer, Ref ID, or method...">
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
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
                        if ($_GET['success'] == 'transaction_updated') echo "Transaction details have been updated successfully.";
                        if ($_GET['success'] == 'payment_deleted') echo "Payment record has been removed securely.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php
                        if ($_GET['error'] == 'update_failed') echo "Error: Could not update the transaction details.";
                        if ($_GET['error'] == 'delete_failed') echo "Error: Could not delete the payment record.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">All Payment Transactions (<?php echo count($payments); ?>)</h3>
                        </div>
                    <div class="section-body">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Booking Ref</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Trans. ID / Ref No.</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentTableBody">
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td style="font-size: 13px; color: rgba(47, 61, 46, 0.7);"><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                                            <td style="font-weight: 500; color: #2F3D2E;">
                                                <?php echo htmlspecialchars(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?: 'Unknown'; ?>
                                            </td>
                                            <td style="color: rgba(47, 61, 46, 0.8);">#<?php echo str_pad($payment['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><span class="status-pill <?php echo strtolower($payment['method_name']); ?>"><?php echo htmlspecialchars($payment['method_name']); ?></span></td>
                                            <td style="font-weight: 600; color: #16a34a;">₱<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                            <td style="color: rgba(47, 61, 46, 0.8); font-family: monospace; font-size: 14px;">
                                                <?php echo htmlspecialchars($payment['transaction_id']) ?: '<span style="color:#9ca3af;font-style:italic;">N/A (Cash)</span>'; ?>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="../pages/receipt.php?payment_id=<?php echo $payment['payment_id']; ?>" class="action-btn" title="View Receipt" style="color:var(--forest);border:1px solid var(--border);background:#f8faf5;padding:4px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;margin-right:4px;text-decoration:none;">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                                <button class="action-btn edit-btn" title="Edit Transaction ID"
                                                    data-id="<?php echo $payment['payment_id']; ?>"
                                                    data-trans="<?php echo htmlspecialchars($payment['transaction_id']); ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="action-btn delete-btn" title="Delete Payment Record"
                                                    data-id="<?php echo $payment['payment_id']; ?>"
                                                    data-amount="<?php echo number_format($payment['amount_paid'], 2); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($payments) === 0): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 30px; color: #6b7280;">No payment records found in the system.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <div class="dashboard-footer">
                © 2026 West Farm Resort and Hotel · Basista, Pangasinan
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

    <div id="editPaymentModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Edit Transaction Details</h3>
                <button class="modal-close" onclick="closeModal('editPaymentModal')">&times;</button>
            </div>
            <form action="../logic/payment_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_transaction">
                    <input type="hidden" name="payment_id" id="editPaymentId">

                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">Use this to correct typos in GCash or Maya Reference Numbers provided by the customer.</p>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="transaction_id" style="display: block; margin-bottom: 5px; font-weight: 500;">Transaction ID / Reference No.</label>
                        <input type="text" id="editTransactionId" name="transaction_id" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editPaymentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
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
            <form action="../logic/payment_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="payment_id" id="deletePaymentId">
                    <p>Are you sure you want to delete this payment record of <strong id="deletePaymentAmount" style="color: #16a34a;"></strong>?</p>
                    <p style="color: #ef4444; font-size: 13px; margin-top: 10px;">Warning: This action alters financial records and cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                closeModal(event.target.id);
            }
        }

        // Edit Payment Button Logic
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editPaymentId').value = this.dataset.id;
                document.getElementById('editTransactionId').value = this.dataset.trans;
                openModal('editPaymentModal');
            });
        });

        // Delete Payment Button Logic
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('deletePaymentId').value = this.dataset.id;
                document.getElementById('deletePaymentAmount').innerText = '₱' + this.dataset.amount;
                openModal('deleteModal');
            });
        });

        // Close Alerts
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Live Search Logic (Searches Customer Name, Transaction ID, and Method)
        const searchInput = document.getElementById('paymentSearchInput');
        const tableBody = document.getElementById('paymentTableBody');
        const tableRows = tableBody.getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < tableRows.length; i++) {
                const row = tableRows[i];
                // Skip the "No records found" row if it exists
                if (row.cells.length === 1) continue; 

                const customerText = row.cells[1].textContent || row.cells[1].innerText;
                const methodText = row.cells[3].textContent || row.cells[3].innerText;
                const transText = row.cells[5].textContent || row.cells[5].innerText;

                if (
                    customerText.toLowerCase().includes(searchTerm) || 
                    transText.toLowerCase().includes(searchTerm) ||
                    methodText.toLowerCase().includes(searchTerm)
                ) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Logout Modal Logic
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('logoutConfirmModal');
        });
    </script>
</body>

</html>