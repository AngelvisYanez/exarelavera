<?php

header("Content-type: application/msword; name='word'");
header("Content-Disposition: filename = ficheroWord.doc");
//header("Pragma: no-cache");
//header("Expires: 0");

echo $_POST['datos_a_enviar'];
?>