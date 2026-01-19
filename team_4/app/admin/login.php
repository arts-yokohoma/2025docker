<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/adminstyle.css">
</head>
<body>

<div class="container" style="max-width:400px;margin-top:100px;">
  <h2>Admin Login</h2>

  <form method="POST" action="auth.php">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>

  <?php if (isset($_GET['error'])): ?>
    <p style="color:red;">Invalid username or password</p>
  <?php endif; ?>
</div>

</body>
</html>
