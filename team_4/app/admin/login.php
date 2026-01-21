<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="/admin/css/adminstyle.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    }

    body {
      background: #f8fafc;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-container {
      width: 100%;
      max-width: 400px;
    }

    .login-card {
      background: white;
      border-radius: 12px;
      padding: 40px 35px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid #e2e8f0;
    }

    h2 {
      color: #2d3748;
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
      text-align: center;
    }

    .login-subtitle {
      color: #718096;
      font-size: 14px;
      text-align: center;
      margin-bottom: 30px;
    }

    input {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.2s;
      background: #f8fafc;
      color: #2d3748;
      margin-bottom: 20px;
    }

    input:focus {
      outline: none;
      border-color: #4299e1;
      background: white;
      box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    input::placeholder {
      color: #a0aec0;
    }

    button {
      width: 100%;
      padding: 14px;
      background: #4299e1;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 10px;
    }

    button:hover {
      background: #3182ce;
    }

    .error-text {
      color: #e53e3e;
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      padding: 12px;
      background: #fff5f5;
      border-radius: 6px;
      border-left: 3px solid #fc8181;
    }

    .form-group {
      position: relative;
    }

    .form-group:focus-within input {
      padding-left: 46px;
    }

    .form-group::before {
      content: '';
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      background-size: contain;
      background-repeat: no-repeat;
      opacity: 0.5;
      transition: opacity 0.2s;
    }

    .form-group:focus-within::before {
      opacity: 0.8;
    }

    @media (max-width: 480px) {
      .login-card {
        padding: 30px 25px;
      }
      
      h2 {
        font-size: 22px;
      }
      
      input {
        padding: 13px 15px;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card">
      <h2>Admin Login</h2>
      <p class="login-subtitle">Please sign in to continue</p>

      <form method="POST" action="auth.php">
        <div class="form-group username-group">
          <input type="text" name="username" placeholder="Username" required autocomplete="username">
        </div>
        
        <div class="form-group password-group">
          <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        </div>
        
        <button type="submit">Sign In</button>
      </form>

      <?php if (isset($_GET['error'])): ?>
        <p class="error-text">Invalid username or password</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>