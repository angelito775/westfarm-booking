<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | West Farm Resort and Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

    <img id="bg-video" src="../assets/images/Villa.jpg" alt="Scenic view of West Farm Resort">
    <div class="video-overlay"></div>

    <div class="login-wrap register-wrap">
        <div class="login-card">

            <div class="card-header">
                <div class="logo-row">
                    <img class="logo-img" src="../assets/images/westfarmlogo.png" alt="West Farm Logo">
                    <div class="logo-text-col">
                        <span class="logo-name">WEST FARM</span>
                        <span class="logo-sub">Resort and Hotel</span>
                    </div>
                </div>
                <div class="header-divider"></div>
            </div>

            <div class="card-body">

                <div class="card-title">
                    <h1>Create Account</h1>
                    <div class="eyebrow">Join to start your journey</div>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div style="color: #dc2626; background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 10px; margin-bottom: 20px; font-size: 14px; border-radius: 4px;">
                        <?php 
                            if ($_GET['error'] == 'empty_fields') echo "Please fill in all required fields.";
                            elseif ($_GET['error'] == 'password_mismatch') echo "Your passwords do not match.";
                            elseif ($_GET['error'] == 'email_taken') echo "This email is already registered.";
                            elseif ($_GET['error'] == 'phone_taken') echo "This phone number is already in use.";
                            elseif ($_GET['error'] == 'email_phone_taken') echo "Both email and phone number are already in use.";
                            elseif ($_GET['error'] == 'email_phone_and_password_mismatch') echo "Both email and phone number are already in use and password didn't match.";
                            elseif ($_GET['error'] == 'system_error') echo "A system error occurred. Please try again later.";
                        ?>
                    </div>
                <?php endif; ?>

                <form action="../logic/register_process.php" method="POST" class="register-form">
                    
                    <div class="form-row-flex">
                        <div class="field">
                            <label for="first_name">First Name</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-user"></i></span>
                                <input type="text" id="first_name" name="first_name" placeholder="Juan" value="<?php echo isset($_GET['first_name']) ? htmlspecialchars($_GET['first_name']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label for="last_name">Last Name</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-user"></i></span>
                                <input type="text" id="last_name" name="last_name" placeholder="Dela Cruz" value="<?php echo isset($_GET['last_name']) ? htmlspecialchars($_GET['last_name']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-flex">
                        <div class="field">
                            <label for="phone_number">Phone Number</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-phone"></i></span>
                                <input type="text" id="phone_number" name="phone_number" placeholder="09123456789" value="<?php echo isset($_GET['phone_number']) ? htmlspecialchars($_GET['phone_number']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label for="email">Email Address</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-envelope"></i></span>
                                <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-flex">
                        <div class="field">
                            <label for="password">Password</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" placeholder="••••••••" value="<?php echo isset($_GET['password']) ? htmlspecialchars($_GET['password']) : ''; ?>" required minlength="6">
                            </div>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrap">
                                <span class="icon"><i class="fas fa-lock"></i></span>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••"
                                    value="<?php echo isset($_GET['confirm_password']) ? htmlspecialchars($_GET['confirm_password']) : ''; ?>"
                                    required minlength="6">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="register_btn" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <p class="signup-text">
                    Already have an account? <a href="login.php">Sign In</a>
                </p>

            </div>
        </div>

        <p class="copyright">© 2026 West Farm Resort and Hotel &nbsp;·&nbsp; Basista, Pangasinan</p>
    </div>

</body>
</html>