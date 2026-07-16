<?php
$c = new mysqli("localhost", "root", "", "exa");
$q = $c->query("DESCRIBE compras");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
