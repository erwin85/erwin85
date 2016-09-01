<html>
<head>
</head>
<body>
<?php
$files = scandir('./img');
foreach($files as $f) {
    if (substr($f, -4) == '.png') {
        echo '<img src="./img/' . $f . '">';
    }
}
?>
</body>
</html>
