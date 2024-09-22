<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brandId = $_POST['brand-id'];
    $brandName = $_POST['brand-name'];

    $sql = "UPDATE brands SET Brand='$brandName' WHERE id=$brandId";
    if (mysqli_query($conn, $sql)) {
        echo "Brand updated successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
