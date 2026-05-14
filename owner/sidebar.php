<?php
// Shared owner sidebar navigation
$ownerNavActive = $ownerNavActive ?? 'business-overview';
?>
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
        <span class="portal-badge">Owner Portal</span>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item <?php echo $ownerNavActive === 'business-overview' ? 'active' : ''; ?>" href="index.php">
            <i class="fas fa-chart-line"></i>
            <span>Business Overview</span>
        </a>
        <a class="nav-item <?php echo $ownerNavActive === 'bookings-reservations' ? 'active' : ''; ?>" href="bookings.php">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings & Reservations</span>
        </a>
        <a class="nav-item <?php echo $ownerNavActive === 'facilities-rooms' ? 'active' : ''; ?>" href="facilities.php">
            <i class="fas fa-building"></i>
            <span>Facilities & Rooms</span>
        </a>
        <a class="nav-item <?php echo $ownerNavActive === 'gallery-management' ? 'active' : ''; ?>" href="gallery.php">
            <i class="fas fa-image"></i>
            <span>Gallery Management</span>
        </a>
        <a class="nav-item <?php echo $ownerNavActive === 'income-ledger' ? 'active' : ''; ?>" href="ledger.php">
            <i class="fas fa-book"></i>
            <span>Income & Ledger</span>
        </a>
        <a class="nav-item <?php echo $ownerNavActive === 'guest-reviews' ? 'active' : ''; ?>" href="reviews.php">
            <i class="fas fa-comments"></i>
            <span>Guest Reviews</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="#" class="logout-btn" id="openLogoutModalBtn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
