<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 2) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];

// Load profile
$stmt = $pdo->prepare(
    "SELECT u.email, u.created_at, up.first_name, up.last_name, up.phone_number
     FROM users u
     JOIN user_profiles up ON u.user_id = up.user_id
     WHERE u.user_id = ?"
);
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    session_destroy();
    header("Location: ../pages/login.php");
    exit();
}

// Booking stats
$stmt = $pdo->prepare(
    "SELECT bs.status_name, COUNT(*) AS cnt
     FROM bookings b
     JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
     WHERE b.customer_id = ?
     GROUP BY bs.status_name"
);
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$total_bookings = array_sum($stats);
$pending_count = $stats['Pending'] ?? 0;
$confirmed_count = $stats['Confirmed'] ?? 0;
$completed_count = $stats['Completed'] ?? 0;
$cancelled_count = $stats['Cancelled'] ?? 0;

// Handle profile update
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';

    if (empty($first_name) || empty($last_name)) {
        $error_msg = 'First name and last name are required.';
    } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
        $error_msg = 'Names must be at least 2 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s'-]+$/", $last_name)) {
        $error_msg = 'Names can only contain letters, spaces, and hyphens.';
    } elseif (!empty($phone) && !preg_match('/^09\d{9}$/', $phone)) {
        $error_msg = 'Phone number must be 11 digits starting with 09.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'A valid email address is required.';
    } else {
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_msg = 'This email is already registered to another account.';
        } else {
            // Handle password change
            $password_updated = false;
            if (!empty($new_password) || !empty($current_password)) {
                if (empty($current_password)) {
                    $error_msg = 'Current password is required to change password.';
                } elseif (strlen($new_password) < 6) {
                    $error_msg = 'New password must be at least 6 characters.';
                } elseif ($new_password !== $confirm_password) {
                    $error_msg = 'New passwords do not match.';
                } else {
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    if (!password_verify($current_password, $user['password'])) {
                        $error_msg = 'Current password is incorrect.';
                    } else {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt->execute([$hashed, $user_id]);
                        $password_updated = true;
                    }
                }
            }

            if (empty($error_msg)) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                    $stmt->execute([$email, $user_id]);

                    $stmt = $pdo->prepare(
                        "UPDATE user_profiles SET first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?"
                    );
                    $stmt->execute([$first_name, $last_name, $phone ?: null, $user_id]);

                    $pdo->commit();

                    $success_msg = $password_updated
                        ? 'Profile and password updated successfully!'
                        : 'Profile updated successfully!';

                    // Refresh
                    $profile['first_name'] = $first_name;
                    $profile['last_name'] = $last_name;
                    $profile['phone_number'] = $phone;
                    $profile['email'] = $email;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('Profile update error: ' . $e->getMessage());
                    $error_msg = 'An error occurred. Please try again.';
                }
            }
        }
    }
}

$display_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(mb_substr($profile['first_name'], 0, 1) . mb_substr($profile['last_name'], 0, 1));
$member_since = date('F Y', strtotime($profile['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | West Farm Resort and Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body>

<!-- NAV -->
<nav>
    <a class="nav-logo" href="../public/index.php">
        <img src="../assets/images/westfarmlogo.png" alt="West Farm logo">
        <div class="nav-logo-text">
            <span class="name">WEST FARM</span>
            <span class="sub">Resort and Hotel</span>
        </div>
    </a>
    <ul class="nav-links">
        <li><a href="../public/index.php">HOME</a></li>
        <li><a href="../public/about.php">ABOUT</a></li>
        <li><a href="../public/booking.php">BOOK NOW</a></li>
        <li><a href="profile.php" class="active">MY PROFILE</a></li>
        <li><a href="payment_booking.php">PAYMENTS</a></li>
        <li><a href="../logic/logout_customer.php" class="nav-book-btn" style="background: transparent; border: 1px solid rgba(255,255,255,0.4);">
            <i class="fas fa-sign-out-alt"></i> SIGN OUT
        </a></li>
    </ul>
</nav>

<!-- PAGE HERO -->
<div class="page-hero">
    <h1>My Profile</h1>
    <p>Manage your account information and preferences</p>
</div>

<!-- MAIN CONTENT -->
<div class="cust-container">

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
    <?php elseif ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Booking Stats -->
    <div class="cust-card" style="margin-bottom: 1.5rem;">
        <div class="cust-card-header">
            <h2><i class="fas fa-chart-pie"></i> Booking Summary</h2>
            <span style="font-size: 0.85rem; color: var(--muted);">Total: <?php echo $total_bookings; ?> booking(s)</span>
        </div>
        <div class="cust-card-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; text-align: center;">
                <div style="padding: 1rem; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #92400e;"><?php echo $pending_count; ?></div>
                    <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: #92400e; margin-top: 4px;">Pending</div>
                </div>
                <div style="padding: 1rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #166534;"><?php echo $confirmed_count; ?></div>
                    <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: #166534; margin-top: 4px;">Confirmed</div>
                </div>
                <div style="padding: 1rem; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #1e40af;"><?php echo $completed_count; ?></div>
                    <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: #1e40af; margin-top: 4px;">Completed</div>
                </div>
                <div style="padding: 1rem; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #991b1b;"><?php echo $cancelled_count; ?></div>
                    <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: #991b1b; margin-top: 4px;">Cancelled</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Info & Edit -->
    <div class="cust-card">
        <div class="cust-card-header">
            <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
            <button type="button" class="btn btn-sm btn-outline" id="toggleEditBtn" onclick="toggleEdit()">
                <i class="fas fa-pencil-alt"></i> Edit Profile
            </button>
        </div>
        <div class="cust-card-body">

            <!-- View Mode -->
            <div id="profileView">
                <div class="profile-grid">
                    <div class="profile-avatar">
                        <div class="avatar-circle"><?php echo htmlspecialchars($initials); ?></div>
                        <div class="avatar-name"><?php echo htmlspecialchars($display_name); ?></div>
                        <div class="avatar-email"><?php echo htmlspecialchars($profile['email']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--muted); margin-top: 8px;">
                            <i class="fas fa-calendar-alt"></i> Member since <?php echo $member_since; ?>
                        </div>
                    </div>
                    <div class="profile-details">
                        <div class="profile-detail">
                            <label>First Name</label>
                            <div class="value"><?php echo htmlspecialchars($profile['first_name']); ?></div>
                        </div>
                        <div class="profile-detail">
                            <label>Last Name</label>
                            <div class="value"><?php echo htmlspecialchars($profile['last_name']); ?></div>
                        </div>
                        <div class="profile-detail">
                            <label>Email Address</label>
                            <div class="value"><?php echo htmlspecialchars($profile['email']); ?></div>
                        </div>
                        <div class="profile-detail">
                            <label>Phone Number</label>
                            <div class="value"><?php echo $profile['phone_number'] ? htmlspecialchars($profile['phone_number']) : '<span style="color:var(--muted); font-style:italic;">Not set</span>'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode (hidden by default) -->
            <form id="profileEdit" method="POST" style="display:none;">
                <div class="cust-form-grid">
                    <div class="cust-field">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required minlength="2">
                    </div>
                    <div class="cust-field">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required minlength="2">
                    </div>
                    <div class="cust-field">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                    </div>
                    <div class="cust-field">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" placeholder="09XXXXXXXXX" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/[^\d]/g,'').slice(0,11)">
                        <div style="font-size: 0.75rem; color: var(--muted); margin-top: 4px;">Philippine mobile: 11 digits starting with 09</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <h3 style="font-family: 'Josefin Sans', sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--forest); margin-bottom: 1rem;">
                        <i class="fas fa-lock"></i> Change Password <span style="font-weight: 400; color: var(--muted); text-transform: none; letter-spacing: 0;">(leave blank to keep current)</span>
                    </h3>
                    <div class="cust-form-grid">
                        <div class="cust-field">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Enter current password">
                        </div>
                        <div class="cust-field" style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                            <div>
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Min 6 characters" minlength="6">
                            </div>
                            <div>
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_new_password" placeholder="Re-enter new password">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="toggleEdit()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<!-- FOOTER -->
<footer>
    <div class="footer-image">
        <img src="../assets/images/westfarm1.jpg" alt="WestFarm">
    </div>
    <div class="footer-col">
        <h4>Call Us</h4>
        <div class="footer-phones">
            <a href="tel:09107305969">0910-730-5969</a>
            <a href="tel:09630113868">0963-011-3868</a>
        </div>
        <div class="footer-hours">
            Monday to Friday &nbsp;·&nbsp; 9am – 10pm<br>
            Weekend &nbsp;·&nbsp; 8am – 10pm
        </div>
        <div class="footer-social">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-tiktok"></i></a>
        </div>
    </div>
    <div class="footer-col footer-nav">
        <h4>Navigation</h4>
        <a href="../public/index.php">Home</a>
        <a href="../public/about.php">About</a>
        <a href="../public/booking.php">Book Now</a>
        <a href="profile.php">My Profile</a>
    </div>
    <div class="footer-col footer-contact">
        <h4>Contact Info</h4>
        <p>📍 Dumpay West, Basista,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pangasinan, Philippines</p>
        <p>✉️ <a href="mailto:westfarmresort@gmail.com">westfarmresort@gmail.com</a></p>
    </div>
    <div class="footer-bottom">
        <div>
            <a href="#">Terms &amp; Conditions</a>
            <a href="#">Privacy Policy</a>
        </div>
        <div>© 2026. Angelito, Hazel, Relynne, Raymund All rights reserved.</div>
    </div>
</footer>

<script>
function toggleEdit() {
    const view = document.getElementById('profileView');
    const edit = document.getElementById('profileEdit');
    const btn = document.getElementById('toggleEditBtn');
    if (edit.style.display === 'none') {
        view.style.display = 'none';
        edit.style.display = 'block';
        btn.style.display = 'none';
    } else {
        view.style.display = 'block';
        edit.style.display = 'none';
        btn.style.display = 'inline-flex';
    }
}
</script>

</body>
</html>
