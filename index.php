<?php 
/**************************************************************/
/* index.php
/* Gère les différents appels d'affichage de la page, et rafraichit dynamiquement en fonction des besoins
/**************************************************************/
error_reporting(E_ALL);
if(!isset($_SESSION))
	session_start();

if(!isset($_SESSION['user']) || $_SESSION['user'] == '')
{
	header('Location: connexion.php');
	die();
}

require_once('src/config.php');
include('src/globals.php');
require_once('src/google.php');
require_once('src/misc.php');
mb_internal_encoding('UTF-8');
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>ThermoVix</title>
	<link rel="icon" type="image/png" href="img/favicon.png" />
    <link rel="stylesheet" href="css/main.css" />
	<link rel="stylesheet" href="css/colorbox.css"> 
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> 
	<link rel="stylesheet" href="css/jquery-ui.structure.css"> 
	<link rel="stylesheet" href="css/jquery-ui.theme.css">
	<script
			  src="https://code.jquery.com/jquery-2.2.4.min.js"
			  integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
			  crossorigin="anonymous"></script>
	<script
			  src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
			  integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU="
			  crossorigin="anonymous"></script>
	<script src="lib/jquery-ui-timepicker-addon.js"></script>
	<script src="lib/jquery.colorbox-min.js"></script>
	<script src="lib/jquery.formvalidation.js"></script>
	<script src="lib/jquery.scrollTo.min.js"></script>
	<script src="js/main.js"></script>
	<?php 
	
	if(isset($_POST['id']))
	{
		echo '<script type="text/javascript">
			window.onload = function(){
				self.location.hash="#' . $_POST['id'] . '";
			};
		</script>';
	}
	elseif(isset($_POST['newIntervention']))
	{
		echo '<script type="text/javascript">
			window.onload = function(){
				self.location.hash="#' . $_POST['newIntervention'] . '";
			};
		</script>';
	}
	elseif(isset($_REQUEST['logout']))
	{
		echo '<script type="text/javascript">document.location.replace("connexion.php");</script>';
	}
?>
</head>

<body>
<div id="container">
	<div id="outer">
		<header>
			<?php include('src/header.php'); ?>   
		</header>

		<div id="wrapper">
		<?php 
		if(isset($_GET['page']))
			$page = htmlspecialchars($_GET['page']);
		else
			$page = $_SESSION['page'];
		
		
		  switch($page)
		  {
			case 'interventions':	
				include('src/interventions.php');
				break;
				  
			  case 'contact':
			  default:
				?>	<div id="caption">Contacts</div>

					<nav>
						<?php include('src/liste_groupes.php'); ?>
					</nav>
					
					<section>
						<div class="contenu">
							<?php 
							try{
								listContacts();
							} catch(Exception $e){
								echo $e->getMessage();
							}
							//var_dump($connections);
							include('src/liste_contacts.php'); ?>
						</div>
						<div class="notes">
							<div class="right-panel"><pre>
							<?php 		var_dump($_SESSION); ?>
							</pre></div>
						</div>
					</section>
				   <?php break;
		  }?>

		</div>
	</div>

	<footer>
		<?php include('src/footer.php'); ?>
	</footer>
	<div id="popup"></div>
</div>
</body>
</html>
