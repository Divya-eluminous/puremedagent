<html>
<head>
<title>Session Test</title>
</head>
<body>
<p>Server: <?php echo substr($_SERVER['SERVER_ADDR'], 10); ?></p>
<p>Session count: <?php
session_start();
if (!array_key_exists('counter', $_SESSION)) $_SESSION['counter'] = 0;
echo ++$_SESSION['counter'];
?></p>
</body>
</html>
