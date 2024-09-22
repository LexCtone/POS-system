<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brandName = $_POST['brand-name'];

    $sql = "INSERT INTO brands (Brand) VALUES ('$brandName')";
    if (mysqli_query($conn, $sql)) {
        echo "Brand added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
