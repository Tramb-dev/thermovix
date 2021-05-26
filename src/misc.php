<?php
/**************************************************************/
/* misc.php
/* Définit un tas de bordel
/**************************************************************/

$db = db_connect();
$requete = $db->query('SELECT nav FROM users WHERE u_id=' . $_SESSION['user']);
$nav = $requete->fetch(PDO::FETCH_ASSOC);

if($nav['nav'] != $_SERVER['HTTP_USER_AGENT'])
{
	$db->exec('UPDATE users SET nav="' . $_SERVER['HTTP_USER_AGENT'] . '" WHERE u_id=' . $_SESSION['user']);
	// Récupérer les infos en bdd pour remplacer les données en session
}

// Définition d'une variable session au démarrage
if(!isset($_SESSION['champNote']))
	$_SESSION['champNote'] = 'commentaires';
if(!isset($_SESSION['champInter']))
	$_SESSION['champInter'] = 'commentaires';

// S'il y a eu une modification de contact, on appelle ce fichier pour l'enregistrement
if(isset($_POST['id']) && $_POST['id'] != 'addContact' && isset($_POST['save']))
	modif_contact($_POST);
elseif(isset($_POST['id']) && $_POST['id'] != 'addContact' && isset($_POST['delete']))
	delete_contact($_POST['id']);
elseif(isset($_POST['id']) && $_POST['id'] == 'addContact')
	ajouter_contact($_POST);

// Gestion du POST pour les nouvelles interventions créées
if(isset($_POST['newIntervention']))
{
	ajout_intervention($_POST);
	if(isset($_POST['continuer']))
	{
		$script = '<script type="text/javascript">
		jQuery(function(){
		var selected = (' . $_POST['newIntervention'] . ');
		$.colorbox({
			href:"src/newIntervention.php?id=" + selected,
			onComplete:function(){
				$(".datepicker").datepicker({
					hourMin: 6,
					hourMax: 23
				});
				$(".timepicker").timepicker({
					hourMin: 6,
					hourMax: 23
				});
			}
		});
		$("#formIntervention").formValidation({
            alias       : "name",
            required    : "accept",
            err_list    : true
		});
		});</script>';
	}
}
elseif(isset($_POST['modifIntervention']))
{
	if(isset($_POST['enregistrer']))
		gestion_intervention($_POST, 'update');
	elseif(isset($_POST['continuer']))
	{
		gestion_intervention($_POST, 'update');
		$script = '<script type="text/javascript">
		jQuery(function(){
		var selected = (' . $_POST['interOwner'] . ');
		$.colorbox({
			href:"src/newIntervention.php?id=" + selected,
			onComplete:function(){
				$(".datepicker").datepicker({
					hourMin: 6,
					hourMax: 23
				});
				$(".timepicker").timepicker({
					hourMin: 6,
					hourMax: 23
				});
			}
		});
		$("#formIntervention").formValidation({
            alias       : "name",
            required    : "accept",
            err_list    : true
		});
		});</script>';
	}
	else
		gestion_intervention($_POST, 'delete');
}

if(!isset($_SESSION['base']))
	$_SESSION['base'] = 'base';

if(!isset($_SESSION['page']))
	$_SESSION['page'] = 'page';
$db = NULL;
?>