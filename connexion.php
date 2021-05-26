<?php
/**************************************************************/
/* connexion.php
/* Page de connexion
/**************************************************************/
if(!isset($_SESSION))
	session_start();
require_once('src/config.php');
include('src/globals.php');
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

if(isset($_POST['login']) && $_POST['login'] != '' && isset($_POST['pass']) && $_POST['pass'] != '')
{
	$login = htmlspecialchars($_POST['login']);
	$verify = verify_login($login, htmlspecialchars($_POST['pass']));
	if($verify['verif'] == true)
	{
		$_SESSION['user'] = $verify['info'];
		$_SESSION['user_email'] = $login;
		header('Location: index.php?page=contact');
		die();
	}
}

header( 'content-type: text/html; charset=utf-8' );

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>ThermoVix</title>
	<link rel="icon" type="image/png" href="img/favicon.png" />
    <link rel="stylesheet" href="css/login.css" />
</head>

<body>
<div id="container">
		<div id="intro">Bonjour et bienvenu sur l'application ThermoVix</div>
		<form method="post" action="connexion.php">
			<label>Connexion</label><br/>
			<input type="email" name="login" placeholder="Adresse email" autofocus autocomplete="on" required><br/>
			<input type="password" name="pass" placeholder="Mot de passe" autocomplete="on" required><br/>
			<input type="submit" name="save" value="Se connecter">
		</form>
	<?php
	if(isset($_GET['generate']))
	{
		if($_GET['generate'] == 1)
			echo '<div id="message">Le mot de passe a bien été enregistré. Veuillez vous connecter.</div>';
		else
			echo '<div id="message">' . $_GET['generate'] . '</div>';
	}
	elseif(isset($verify))
	{
		echo '<div id="message">' . $verify['info'] . '</div>';
	}
	?>
</div>
</body>
</html>