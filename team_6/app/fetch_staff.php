<?php
include 'db/connect.php';

if(isset($_GET['user_id'])){
    $user_id = $_GET['user_id'];

    $stmt = $db->prepare("SELECT name, post FROM staff WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if($staff){
        echo json_encode($staff);
    } else {
        echo json_encode(['name'=>'','post'=>'']);
    }
}
