<html>
<head>
<title>Database Test</title>
</head>
<body>
<p>Server: <?php echo substr($_SERVER['SERVER_ADDR'], 10); ?></p>
<pre><?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli('db1', 'puremed_test', 'PureMed1!', 'puremed_test');
$mysqli->set_charset('utf8mb4');

$res = $mysqli->query('SELECT * FROM puremed_data');
while ($row = $res->fetch_array(MYSQLI_ASSOC)) {
  print_r($row);
}
$res->free();

$mysqli->close();
?></pre>
</body>
</html>

