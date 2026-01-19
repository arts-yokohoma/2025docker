<?php
include 'db_conn.php';

$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        echo $row[0] . "<br>";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
