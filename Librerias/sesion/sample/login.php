<?php
  session_start();
  session_unset();
//  session_start();
  require_once '../securesession.class.php';
  $error = '';
  if (isset($_POST['btingresar']))
  {
    $uname = $_POST['uname'];
    $passwd = $_POST['passwd'];
    if ($uname == 'estef' && $passwd == '123')
    {
      $ss = new SecureSession();
      $ss->check_browser = true;
      $ss->check_ip_blocks = 2;
      $ss->secure_word = 'SALT_';
      $ss->regenerate_id = true;
      $ss->Open();
      $_SESSION['logged_in'] = true;
	  
      header('Location: index.php');
      die();
    }
    else
    {
      $error = 'Usuario o contraseña incorrecta';
    }
  }
?>

<html>
<head>
<title>Sesión Segura</title>
</head>
<body>
<?php
  if (!empty($error))
  {
    echo $error;
  }
?>
<form method="post" action="<?php echo $_SERVER['../../sesion_segura/sample/PHP_SELF']; ?>">
Usuario: 
  <input type="text" name="uname" />
Contraseña: 
<input type="password" name="passwd"  />
<input name="btingresar" type="submit" id="btingresar" value="Ingresar" />
</form>
</body>
</html>