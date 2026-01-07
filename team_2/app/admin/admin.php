<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin page</title>
        </head>
        <body>
            <h1> Admin Panel</h1>
            <p>Welcome to the admin panel of pizza mach</p>
            <?php
            echo "<p>Current date and time: " . date("Y-m-d ") . "</p>";
            echo "<p>today is a great day to manage pizzas!</p>";
            echo "<p>Input today's kitchen staff amount</p>";
            echo "<form action='submit_staff.php' method='post'>
                    <input type='number' name='staff_amount' min='1' max='100' required>
                    <input type='submit' value='Submit'>
                  </form>";
            echo "<p>Input today's driver amount</p>";
            echo "<form action='submit_drivers.php' method='post'>
                    <input type='number' name='driver_amount' min='1' max='100' required>
                    <input type='submit' value='Submit'>
                  </form>";
            ?>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </nav>
        </body>
</html>
=======
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin page</title>
        </head>
        <body>
            <h1>Team 2 - Admin Panel</h1>
            <p>Welcome to the admin panel of Team 2!</p>
            </body>
            </html>
<?php
echo "<h1>Team 2 - Admin Panel</h1>";
echo "<p>Welcome to the admin panel of Team 2!</p>";
?>
>>>>>>> 3093e2e (kin)
