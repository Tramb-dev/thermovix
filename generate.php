<?php
/**************************************************************/
/* generate.php
/* Page de modification de mot de passe
/**************************************************************/
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

require_once('src/config.php');
include('src/globals.php');

if(isset($_POST['login']) && $_POST['login'] != '' && isset($_POST['pass']) && $_POST['pass'] != '')
{
	$login = htmlspecialchars($_POST['login']);
	$pass = htmlspecialchars($_POST['pass']);
	$pass_verif = htmlspecialchars($_POST['pass_verif']);
	if($pass != $pass_verif)
		$message = 'Les mots de passe ne correspondent pas.';
	else
	{
		$adresse = htmlspecialchars($_POST['rue']) . ' ' . htmlspecialchars($_POST['cp']) . ' ' . htmlspecialchars($_POST['ville']);
		$retour = pass_to_db($login, $pass, $adresse);
		if($retour == true)
			header('Location: connexion.php?generate=1');
		else
			header('Location: connexion.php?generate=' . $retour);
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
		<span id="intro">Cr&eacute;ation de mot de passe</span>
		<form method="post" action="generate.php">
			<input type="email" name="login" placeholder="Adresse gmail"><br/>
			<input type="password" name="pass" placeholder="Nouveau mot de passe"><br/>
			<input type="password" name="pass_verif" placeholder="Confirmer mot de passe"><br/>
			<input type="text" name="rue" placeholder="Rue"><br/>
			<input type="text" name="cp" placeholder="Code postal"> <input type="text" name="ville" placeholder="Ville"><br/>
			<input type="submit" name="save" value="Cr&eacute;er son mot de passe">
		</form>
		<?php echo (isset($message)) ? '<div id="message">' . $verify['info'] . '</div>' : ''; ?>
	</div>
</body>
</html>