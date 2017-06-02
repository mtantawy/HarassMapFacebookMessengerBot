<?php
    $lat = $_GET['lat'];
    $lng = $_GET['lng'];
    echo file_get_contents('https://maps.harassmap.org/api?task=incidents&locationlatitude=' . $lat . '&locationlongitude=' . $lng);
?>
