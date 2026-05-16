<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | West Farm Resort and Hotel</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

  <img id="bg-video" src="../assets/images/Villa.jpg" alt="Scenic view of West Farm Resort">
  <div class="video-overlay"></div>

  <div class="login-wrap">
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
          <h1>Welcome Back</h1>
          <div class="eyebrow">Sign in to your account</div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div style="color: #dc2626; background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 10px; margin-bottom: 20px; font-size: 14px; border-radius: 4px;">
                <?php 
                    if ($_GET['error'] == 'empty_fields') echo "Please fill in all fields.";
                    elseif ($_GET['error'] == 'invalid_email') echo "Email not found. Please check and try again.";
                    elseif ($_GET['error'] == 'invalid_password') echo "Incorrect password.";
                    elseif ($_GET['error'] == 'account_disabled') echo "Your account has been disabled or suspended.";
                    elseif ($_GET['error'] == 'system_error') echo "A system error occurred. Please try again.";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
            <div style="color: #15803d; background-color: #dcfce7; border-left: 4px solid #16a34a; padding: 10px; margin-bottom: 20px; font-size: 14px; border-radius: 4px;">
                Registration successful! You can now sign in.
            </div>
        <?php endif; ?>

        <form action="../logic/auth_process.php" method="POST">
            
            <div class="field">
              <label for="email">Email Address</label>
              <div class="input-wrap">
                <span class="icon"><i class="fas fa-envelope"></i></span>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" required>
              </div>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="input-wrap">
                <span class="icon"><i class="fas fa-lock"></i></span>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                
                <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Toggle password visibility">
                  <i class="fas fa-eye" id="pw-icon"></i>
                </button>
              </div>
            </div>

            <div class="form-row">
              <label class="remember">
                <input type="checkbox" name="remember"> Remember me
              </label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" name="login_btn" class="btn-primary" id="login-btn">
              <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            
        </form>

        <div class="divider"><span>or</span></div>

        <button type="button" class="btn-outline">
          <i class="fab fa-google g-icon"></i> Continue with Google
        </button>

        <p class="signup-text">
          Don't have an account? <a href="register.php">Create Account</a>
        </p>

      </div>
    </div>

    <p class="copyright">© 2026 West Farm Resort and Hotel &nbsp;·&nbsp; Basista, Pangasinan</p>
  </div>

<script src="login.js"></script>

<script>
  function togglePw() {
      const pwInput = document.getElementById('password');
      const pwIcon = document.getElementById('pw-icon');
      if (pwInput.type === 'password') {
          pwInput.type = 'text';
          pwIcon.classList.remove('fa-eye');
          pwIcon.classList.add('fa-eye-slash');
      } else {
          pwInput.type = 'password';
          pwIcon.classList.remove('fa-eye-slash');
          pwIcon.classList.add('fa-eye');
      }
  }

  // Clear fields based on error type
  window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const passwordInput = document.getElementById('password');
    const emailInput = document.getElementById('email');

    if (error === 'invalid_email') {
      // Email was wrong - clear both fields
      emailInput.value = '';
      passwordInput.value = '';
    } else if (error === 'invalid_password') {
      // Password was wrong - keep email, clear only password
      passwordInput.value = '';
    }
  });
</script>
</body>
</html>