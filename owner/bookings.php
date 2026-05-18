<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}
require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Owner';
$ownerNavActive = 'bookings-reservations';

// Load owner facilities
$stmt = $pdo->query("SELECT facility_id, name FROM facilities ORDER BY name ASC");
$owner_facilities = $stmt->fetchAll();
$facility_ids = array_column($owner_facilities, 'facility_id');

$bookings = [];
$pending_count = $confirmed_count = $checked_in_count = $completed_count = 0;

if (!empty($facility_ids)) {
    $placeholders = implode(',', array_fill(0, count($facility_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, up.first_name, up.last_name, up.phone_number,
                f.name AS facility_name, bi.check_in_date, bi.check_out_date,
                b.total_amount, b.booking_status_id, bs.status_name,
                ps.status_name AS payment_status_name, b.created_at
         FROM bookings b
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         JOIN facilities f ON bi.facility_id = f.facility_id
         JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
         JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
         JOIN users u ON b.customer_id = u.user_id
         LEFT JOIN user_profiles up ON u.user_id = up.user_id
         WHERE bi.facility_id IN ($placeholders)
         ORDER BY b.created_at DESC
         LIMIT 50"
    );
    $stmt->execute($facility_ids);
    $bookings = $stmt->fetchAll();

    $statusCounts = $pdo->prepare(
        "SELECT b.booking_status_id, COUNT(DISTINCT b.booking_id) AS count
         FROM bookings b
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         WHERE bi.facility_id IN ($placeholders)
         GROUP BY b.booking_status_id"
    );
    $statusCounts->execute($facility_ids);
    $counts = $statusCounts->fetchAll(PDO::FETCH_KEY_PAIR);
    $pending_count = $counts[1] ?? 0;
    $confirmed_count = $counts[2] ?? 0;
    $checked_in_count = $counts[3] ?? 0;
    $completed_count = $counts[4] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings & Reservations | Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Bookings & Reservations</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bookingSearchInput" placeholder="Search bookings, guests, or facilities...">
                    </div>
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
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success">
                        <?php
                        if ($_GET['success'] === 'booking_created') echo "Manual booking created successfully.";
                        elseif ($_GET['success'] === 'status_updated') echo "Booking updated successfully.";
                        elseif ($_GET['success'] === 'booking_deleted') echo "Booking deleted successfully.";
                        ?>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php
                        if ($_GET['error'] === 'empty_guest_name') echo "Guest name is required.";
                        elseif ($_GET['error'] === 'invalid_facility') echo "Please select a valid facility.";
                        elseif ($_GET['error'] === 'empty_dates') echo "Check-in and check-out dates are required.";
                        elseif ($_GET['error'] === 'invalid_dates') echo "Check-out date must be on or after check-in date.";
                        elseif ($_GET['error'] === 'facility_unavailable') echo "This facility is no longer available for the selected dates.";
                        elseif ($_GET['error'] === 'booking_failed') echo "Unable to create booking. Please try again.";
                        elseif ($_GET['error'] === 'invalid_payment_status') echo "Invalid payment status for the selected booking status.";
                        elseif ($_GET['error'] === 'booking_not_found') echo "Booking not found.";
                        elseif ($_GET['error'] === 'update_failed') echo "Unable to update booking. Please try again.";
                        elseif ($_GET['error'] === 'delete_failed') echo "Unable to delete booking. Please try again.";
                        else echo "An error occurred. Please check your inputs.";
                        ?>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Current Booking Activity</h3>
                        <a href="facilities.php" class="section-action" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                            <i class="fas fa-calendar-plus"></i> Manual Booking
                        </a>
                    </div>
                    <div class="section-body">
                        <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px;">
                            <div class="status-mini-card">
                                <div class="status-icon pending"><i class="fas fa-clock"></i></div>
                                <div class="status-content"><p>Pending</p><p><?php echo $pending_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon confirmed"><i class="fas fa-check-circle"></i></div>
                                <div class="status-content"><p>Confirmed</p><p><?php echo $confirmed_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon pending"><i class="fas fa-door-open"></i></div>
                                <div class="status-content"><p>Checked In</p><p><?php echo $checked_in_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon completed"><i class="fas fa-star"></i></div>
                                <div class="status-content"><p>Completed</p><p><?php echo $completed_count; ?></p></div>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Booking</th>
                                        <th>Guest</th>
                                        <th>Facility</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="bookingTableBody">
                                    <?php if (empty($bookings)): ?>
                                        <tr><td colspan="7" style="text-align:center; color: rgba(47, 61, 46, 0.6); padding: 32px 0;">No bookings found for your facilities yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr data-booking-id="<?php echo $booking['booking_id']; ?>"
                                                data-guest-name="<?php echo htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')); ?>"
                                                data-guest-phone="<?php echo htmlspecialchars($booking['phone_number'] ?? ''); ?>"
                                                data-facility="<?php echo htmlspecialchars($booking['facility_name']); ?>"
                                                data-check-in="<?php echo date('M d', strtotime($booking['check_in_date'])); ?>"
                                                data-check-out="<?php echo date('M d', strtotime($booking['check_out_date'])); ?>"
                                                data-check-out-raw="<?php echo date('Y-m-d', strtotime($booking['check_out_date'])); ?>"
                                                data-amount="<?php echo number_format($booking['total_amount'], 0); ?>"
                                                data-booking-status="<?php echo htmlspecialchars($booking['status_name']); ?>"
                                                data-payment-status="<?php echo htmlspecialchars($booking['payment_status_name']); ?>">
                                                <td><span class="booking-id">#<?php echo htmlspecialchars($booking['booking_id']); ?></span></td>
                                                <td><?php echo htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                                <td><?php echo date('M d', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d', strtotime($booking['check_out_date'])); ?></td>
                                                <td>₱ <?php echo number_format($booking['total_amount'], 0); ?></td>
                                                <td><span class="status-pill payment-<?php echo strtolower($booking['payment_status_name']); ?>"><?php echo htmlspecialchars($booking['payment_status_name']); ?></span></td>
                                                <td style="text-align: right;">
                                                    <span class="status-pill <?php echo strtolower(str_replace(' ', '-', $booking['status_name'])); ?>" style="margin-right: 8px;"><?php echo htmlspecialchars($booking['status_name']); ?></span>
                                                    <button class="action-btn edit-booking-btn" title="Edit Booking" style="color: #3b82f6; border: 1px solid #bfdbfe; background: #eff6ff; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-right: 4px;">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <button class="action-btn delete-booking-btn" title="Delete Booking" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <div class="dashboard-footer">© 2026 West Farm Resort and Hotel · Basista, Pangasinan</div>
        </div>
    </div>
    <!-- Edit Booking Modal -->
    <div id="editBookingModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Edit Booking</h3>
                <button class="modal-close" onclick="closeModal('editBookingModal')">&times;</button>
            </div>
            <form id="editBookingForm" action="../logic/booking_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="owner_update_booking">
                    <input type="hidden" name="booking_id" id="edit_booking_id" value="">

                    <div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #e5e7eb;">
                        <p style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Booking Summary</p>
                        <p style="margin: 0; font-weight: 600; color: #2F3D2E;" id="edit_booking_summary"></p>
                        <p style="margin: 2px 0 0 0; font-size: 13px; color: #6b7280;" id="edit_booking_dates"></p>
                    </div>

                    <h4 style="margin: 0 0 10px 0; color: #2F3D2E; border-bottom: 1px solid #eee; padding-bottom: 5px;">Guest Details</h4>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Guest Full Name</label>
                        <input type="text" id="edit_guest_name" name="guest_name" placeholder="e.g. Juan Dela Cruz" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Contact Number</label>
                        <input type="text" id="edit_guest_phone" name="guest_phone" placeholder="09XX..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <h4 style="margin: 20px 0 10px 0; color: #2F3D2E; border-bottom: 1px solid #eee; padding-bottom: 5px;">Status Settings</h4>
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Booking Status</label>
                            <select id="edit_booking_status" name="booking_status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="Confirmed">Confirmed (Walk-in / Approved)</option>
                                <option value="Pending">Pending (Waiting for payment)</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Payment Status</label>
                            <select id="edit_payment_status" name="payment_status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="Paid">Paid (Cash / Transferred)</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Partial">Downpayment Only</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editBookingModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Booking Modal -->
    <div id="deleteBookingModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 420px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteBookingModal')">&times;</button>
            </div>
            <form action="../logic/booking_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="owner_delete_booking">
                    <input type="hidden" name="booking_id" id="delete_booking_id" value="">
                    <p>Are you sure you want to permanently delete <strong id="delete_booking_label"></strong>?</p>
                    <p style="color: #dc2626; font-size: 13px; margin-top: 8px;">This will also remove all associated payment records and cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteBookingModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.classList.contains('modal-overlay')) closeModal(event.target.id); }

        // Live Search
        const bookingSearchInput = document.getElementById('bookingSearchInput');
        bookingSearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#bookingTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        // ── Edit Booking Status → Payment Status linkage ──────────
        const editBookingStatus = document.getElementById('edit_booking_status');
        const editPaymentStatus = document.getElementById('edit_payment_status');

        function updateEditPaymentOptions() {
            const bStatus = editBookingStatus.value;
            const currentPayment = editPaymentStatus.value;
            editPaymentStatus.innerHTML = '';

            if (bStatus === 'Confirmed' || bStatus === 'Completed') {
                const opt = document.createElement('option');
                opt.value = 'Paid';
                opt.textContent = 'Paid (Cash / Transferred)';
                editPaymentStatus.appendChild(opt);
                editPaymentStatus.value = 'Paid';
            } else {
                // Pending or Cancelled → Unpaid or Partial
                const optUnpaid = document.createElement('option');
                optUnpaid.value = 'Unpaid';
                optUnpaid.textContent = 'Unpaid';
                editPaymentStatus.appendChild(optUnpaid);
                const optPartial = document.createElement('option');
                optPartial.value = 'Partial';
                optPartial.textContent = 'Downpayment Only';
                editPaymentStatus.appendChild(optPartial);
                if (currentPayment === 'Unpaid' || currentPayment === 'Partial') {
                    editPaymentStatus.value = currentPayment;
                } else {
                    editPaymentStatus.value = 'Unpaid';
                }
            }
        }

        editBookingStatus.addEventListener('change', updateEditPaymentOptions);

        // ── Edit Booking buttons ───────────────────────────────────
        document.querySelectorAll('.edit-booking-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const d = row.dataset;

                document.getElementById('edit_booking_id').value = d.bookingId;
                document.getElementById('edit_guest_name').value = d.guestName;
                document.getElementById('edit_guest_phone').value = d.guestPhone;
                document.getElementById('edit_booking_summary').textContent = 'Booking #' + d.bookingId + ' — ' + d.facility + ' (₱' + d.amount + ')';
                document.getElementById('edit_booking_dates').textContent = d.checkIn + ' to ' + d.checkOut;

                // Auto-complete: if check-out date is in the past, force Completed + Paid
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const checkoutDate = new Date(d.checkOutRaw + 'T00:00:00');

                if (checkoutDate < today) {
                    // Booking stay has ended — auto-set to Completed + Paid
                    editBookingStatus.value = 'Completed';
                    updateEditPaymentOptions();
                    editPaymentStatus.value = 'Paid';
                } else {
                    editBookingStatus.value = d.bookingStatus;
                    updateEditPaymentOptions();
                    editPaymentStatus.value = d.paymentStatus;
                }

                openModal('editBookingModal');
            });
        });

        // ── Delete Booking buttons ─────────────────────────────────
        document.querySelectorAll('.delete-booking-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const d = row.dataset;

                document.getElementById('delete_booking_id').value = d.bookingId;
                document.getElementById('delete_booking_label').textContent = 'Booking #' + d.bookingId + ' (' + d.guestName + ' — ' + d.facility + ')';
                openModal('deleteBookingModal');
            });
        });

        // Logout modal
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('logoutConfirmModal').style.display = 'flex';
        });
    </script>
    <div id="logoutConfirmModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Sign Out</h3>
                <button class="modal-close" onclick="closeModal('logoutConfirmModal')">&times;</button>
            </div>
            <div class="modal-body"><p>Are you sure you want to sign out of your account?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('logoutConfirmModal')">Stay</button>
                <a href="../logic/logout.php" class="btn-danger">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>
