<?php
include "db_config.php";

$sql = "SELECT * FROM kkk";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    echo $row['text'] ;
}
?>