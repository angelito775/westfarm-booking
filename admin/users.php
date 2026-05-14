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
// Handle case where admin profile might not exist
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Administrator';


// Fetch all users with their details, including IDs for roles and statuses
$stmt = $pdo->query("
    SELECT 
        u.user_id,
        u.email,
        u.created_at,
        u.user_type_id,
        u.user_status_id,
        up.first_name,
        up.last_name,
        up.phone_number,
        ut.type_name,
        us.status_name
    FROM users u
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    JOIN user_types ut ON u.user_type_id = ut.user_type_id
    JOIN user_status us ON u.user_status_id = us.user_status_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Fetch roles and statuses for modal dropdowns
$user_types = $pdo->query("SELECT user_type_id, type_name FROM user_types ORDER BY type_name")->fetchAll();
$user_statuses = $pdo->query("SELECT user_status_id, status_name FROM user_status ORDER BY status_name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
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
                <a href="users.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Categories</span>
                </a>
                <a href="payment_methods.php" class="nav-item">
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

        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">User Management</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearchInput" placeholder="Search users by name or email...">
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
                        if ($_GET['success'] == 'user_added') echo "New user has been added successfully.";
                        if ($_GET['success'] == 'user_updated') echo "User details have been updated successfully.";
                        if ($_GET['success'] == 'user_deleted') echo "User has been deleted successfully.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php
                        if ($_GET['error'] == 'add_failed') echo "Error: Could not add the new user.";
                        if ($_GET['error'] == 'update_failed') echo "Error: Could not update user details.";
                        if ($_GET['error'] == 'delete_failed') echo "Error: Could not delete the user.";
                        if ($_GET['error'] == 'cannot_delete_self') echo "Error: You cannot delete your own account.";
                        if ($_GET['error'] == 'cannot_demote_self') echo "Error: You cannot change your own role from Administrator.";
                        if ($_GET['error'] == 'email_taken_modal') echo "Error: That email address is already in use.";
                        if ($_GET['error'] == 'password_mismatch_modal') echo "Error: Passwords did not match.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">All Users (<?php echo count($users); ?>)</h3>
                        <button id="addUserBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="section-body">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined Date</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="userTableBody">
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td style="font-weight: 500; color: #2F3D2E;"><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: 'N/A'; ?></td>
                                            <td style="color: rgba(47, 61, 46, 0.8);"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="status-pill <?php echo strtolower($user['type_name']); ?>"><?php echo htmlspecialchars($user['type_name']); ?></span></td>
                                            <td><span class="status-pill <?php echo strtolower($user['status_name']); ?>"><?php echo htmlspecialchars($user['status_name']); ?></span></td>
                                            <td style="font-size: 13px; color: rgba(47, 61, 46, 0.7);"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <button class="action-btn edit-btn" title="Edit User"
                                                    data-user-id="<?php echo $user['user_id']; ?>"
                                                    data-first-name="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                                    data-last-name="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-phone-number="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"
                                                    data-user-type-id="<?php echo $user['user_type_id']; ?>"
                                                    data-user-status-id="<?php echo $user['user_status_id']; ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button class="action-btn delete-btn" title="Delete User"
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        data-user-name="<?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: htmlspecialchars($user['email']); ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
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
                © 2026 West Farm Resort and Hotel · Basista, Pangasinan
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <div id="userModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3 id="userModalTitle" class="modal-title">Add New User</h3>
                <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <form id="userForm" action="../logic/user_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="userAction" value="add_user">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="Optional">
                    </div>
                    <div id="password-fields">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" placeholder="Leave blank to keep unchanged">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="user_type_id">Role</label>
                            <select name="user_type_id" id="user_type_id" required>
                                <?php foreach ($user_types as $type): ?>
                                    <option value="<?php echo $type['user_type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="user_status_id">Status</label>
                            <select name="user_status_id" id="user_status_id" required>
                                <?php foreach ($user_statuses as $status): ?>
                                    <option value="<?php echo $status['user_status_id']; ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('userModal')">Cancel</button>
                    <button type="submit" id="userSubmitBtn" class="btn-primary">Add User</button>
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
            <form action="../logic/user_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <p>Are you sure you want to permanently delete the user <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete User</button>
                </div>
            </form>
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

        document.getElementById('addUserBtn').addEventListener('click', function() {
            document.getElementById('userForm').reset();
            document.getElementById('userModalTitle').innerText = 'Add New User';
            document.getElementById('userAction').value = 'add_user';
            document.getElementById('editUserId').value = '';
            document.getElementById('userSubmitBtn').innerText = 'Add User';
            document.getElementById('password').setAttribute('required', '');
            document.getElementById('confirm_password').setAttribute('required', '');
            document.getElementById('password').placeholder = "Required for new user";
            openModal('userModal');
        });

        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const firstName = this.dataset.firstName;
                const lastName = this.dataset.lastName;
                const email = this.dataset.email;
                const phoneNumber = this.dataset.phoneNumber;
                const userTypeId = this.dataset.userTypeId;
                const userStatusId = this.dataset.userStatusId;

                document.getElementById('userModalTitle').innerText = 'Edit User';
                document.getElementById('userAction').value = 'edit_user';
                document.getElementById('userSubmitBtn').innerText = 'Save Changes';
                document.getElementById('editUserId').value = userId;
                document.getElementById('first_name').value = firstName;
                document.getElementById('last_name').value = lastName;
                document.getElementById('email').value = email;
                document.getElementById('phone_number').value = phoneNumber;
                document.getElementById('user_type_id').value = userTypeId;
                document.getElementById('user_status_id').value = userStatusId;

                document.getElementById('password').removeAttribute('required');
                document.getElementById('confirm_password').removeAttribute('required');
                document.getElementById('password').placeholder = "Leave blank to keep unchanged";
                openModal('userModal');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteUserName').innerText = userName;
                openModal('deleteModal');
            });
        });

        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            if (password.value !== '' && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert("Passwords do not match.");
                confirmPassword.focus();
            }
        });

        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Live search functionality
        const searchInput = document.getElementById('userSearchInput');
        const tableBody = document.getElementById('userTableBody');
        const tableRows = tableBody.getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < tableRows.length; i++) {
                const row = tableRows[i];
                const nameText = row.cells[0].textContent || row.cells[0].innerText;
                const emailText = row.cells[1].textContent || row.cells[1].innerText;

                if (nameText.toLowerCase().includes(searchTerm) || emailText.toLowerCase().includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Logout confirmation
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('logoutConfirmModal');
        });
    </script>
</body>

</html>