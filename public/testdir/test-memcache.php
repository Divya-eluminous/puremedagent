<html>
<head>
<title>Memcached Test</title>
</head>
<body>
<p>Server: <?php echo substr($_SERVER['SERVER_ADDR'], 10); ?></p>
<p>Cache Value (lifetime 30s): <?php
$m = new Memcached('db1');
if (!$m->getServerList()) {
    $m->addServer('db1', 11211);
}

$val = $m->get('MyKey');
if (!is_int($val)) {
    $val = 1;
} else {
    ++$val;
}

$m->set('MyKey', $val, 30);

echo $val;
?></p>
</body>
</html>
