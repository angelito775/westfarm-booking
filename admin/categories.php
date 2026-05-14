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

// Fetch all categories
$stmt = $pdo->query("SELECT category_id, name, description, created_at FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | Admin Dashboard</title>
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
                <a href="categories.php" class="nav-item active">
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
                <a href="../logic/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Category Management</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="categorySearchInput" placeholder="Search categories by name...">
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
                        if ($_GET['success'] == 'category_added') echo "New category has been added successfully.";
                        if ($_GET['success'] == 'category_updated') echo "Category details have been updated successfully.";
                        if ($_GET['success'] == 'category_deleted') echo "Category has been deleted successfully.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php
                        if ($_GET['error'] == 'add_failed') echo "Error: Could not add the new category.";
                        if ($_GET['error'] == 'update_failed') echo "Error: Could not update category details.";
                        if ($_GET['error'] == 'delete_failed') echo "Error: Could not delete the category. It might be linked to existing facilities.";
                        if ($_GET['error'] == 'name_taken') echo "Error: A category with that name already exists.";
                        ?>
                        <button class="alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">All Categories (<?php echo count($categories); ?>)</h3>
                        <button id="addCategoryBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                    <div class="section-body">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Created Date</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryTableBody">
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: #2F3D2E;"><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td style="color: rgba(47, 61, 46, 0.8); max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($cat['description']); ?>">
                                                <?php echo htmlspecialchars($cat['description']); ?>
                                            </td>
                                            <td style="font-size: 13px; color: rgba(47, 61, 46, 0.7);"><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <button class="action-btn edit-btn" title="Edit Category"
                                                    data-id="<?php echo $cat['category_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                                    data-desc="<?php echo htmlspecialchars($cat['description']); ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="action-btn delete-btn" title="Delete Category"
                                                    data-id="<?php echo $cat['category_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($cat['name']); ?>">
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
                © 2026 West Farm Resort and Hotel · Basista, Pangasinan
            </div>
        </div>
    </div>

    <div id="categoryModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="categoryModalTitle" class="modal-title">Add New Category</h3>
                <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <form id="categoryForm" action="../logic/category_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="categoryAction" value="add_category">
                    <input type="hidden" name="category_id" id="editCategoryId">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="name" style="display: block; margin-bottom: 5px; font-weight: 500;">Category Name</label>
                        <input type="text" id="name" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="description" style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                        <textarea id="description" name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('categoryModal')">Cancel</button>
                    <button type="submit" id="categorySubmitBtn" class="btn-primary">Add Category</button>
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
            <form action="../logic/category_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <p>Are you sure you want to delete the category <strong id="deleteCategoryName"></strong>?</p>
                    <p style="color: #ef4444; font-size: 13px; margin-top: 10px;">Warning: This may fail if there are active facilities currently using this category.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Category</button>
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

        // Add Category Button Logic
        document.getElementById('addCategoryBtn').addEventListener('click', function() {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModalTitle').innerText = 'Add New Category';
            document.getElementById('categoryAction').value = 'add_category';
            document.getElementById('editCategoryId').value = '';
            document.getElementById('categorySubmitBtn').innerText = 'Add Category';
            openModal('categoryModal');
        });

        // Edit Category Button Logic
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('categoryModalTitle').innerText = 'Edit Category';
                document.getElementById('categoryAction').value = 'edit_category';
                document.getElementById('categorySubmitBtn').innerText = 'Save Changes';
                
                document.getElementById('editCategoryId').value = this.dataset.id;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('description').value = this.dataset.desc;
                
                openModal('categoryModal');
            });
        });

        // Delete Category Button Logic
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('deleteCategoryId').value = this.dataset.id;
                document.getElementById('deleteCategoryName').innerText = this.dataset.name;
                openModal('deleteModal');
            });
        });

        // Close Alerts
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Live Search Logic
        const searchInput = document.getElementById('categorySearchInput');
        const tableBody = document.getElementById('categoryTableBody');
        const tableRows = tableBody.getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < tableRows.length; i++) {
                const row = tableRows[i];
                const nameText = row.cells[0].textContent || row.cells[0].innerText;

                if (nameText.toLowerCase().includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>
</body>

</html>