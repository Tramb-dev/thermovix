<?php 
session_start();
require_once('../src/globals.php');

// Renvoi les notes/interventions/livres correspondant au contact demandé lors d'un clic sur le contact
if(isset($_POST['note']))
{
	switch($_SESSION['champNote'])
	{
		case 'interventions':
			displayResume($_POST['note'], false);
			break;
			
		case 'livres':
			displayLivres($_POST['note']);
			break;
			
		case 'commentaires':
		default:
			$db = db_connect();
			$notes = $nota = '';
			$infos = array('rencontre', 'dateCommande', 'dateReception', 'metier', 'dispos', 'divers', 'distance', 'famille', 'dateMES', 'dateCours', 'filleuls');
			$sql = $db->query('SELECT fullName, notes, rencontre, dateCommande, dateReception, metier, dispos, divers, distance, duree, famille, dateMES, dateCours, filleuls FROM contact WHERE c_id="' . $_POST['note'] . '"');
			$result = $sql->fetch(PDO::FETCH_ASSOC);
			
			if(!empty($result['notes']))
				$notes = $result['notes'];
			
			if(!empty($result['rencontre']))
				$nota .= "Rencontre via : " . $result['rencontre'] . "\n";
			
			if(!empty($result['dateCommande']))
				$nota .= "Date commande : " . dateFr($result['dateCommande']) . "\n";
			
			if(!empty($result['dateReception']))
				$nota .= "Date réception : " . dateFr($result['dateReception']) . "\n";
			
			if(!empty($result['metier']))
				$nota .= "Métier : " . $result['metier'] . "\n";
			
			if(!empty($result['dispos']))
				$nota .= "Disponibilités : " . $result['dispos'] . "\n";
			
			if(!empty($result['divers']))
				$nota .= "Divers : " . $result['divers'] . "\n";
			
			if(!empty($result['distance']))
				$nota .= "Distance : " . $result['distance'] . " km\n";
			
			if(!empty($result['duree']))
				$nota .= "Durée trajet : " . substr($result['duree'], 0, 5) . "\n";
			
			if(!empty($result['famille']))
				$nota .= "Famille : " . $result['famille'] . "\n";
			
			if(!empty($result['dateMES']))
				$nota .= "Date de Mise en Service : " . dateFr($result['dateMES']) . "\n";
			
			if(!empty($result['dateCours']))
				$nota .= "Date cours de cuisine : " . dateFr($result['dateCours']) . "\n";
			
			if(!empty($result['filleuls']))
				$nota .= "Filleuls : " . $result['filleuls'] . "\n";
			
			if(!empty($nota))
				$notes .= "\n\n§§§§§ Informations complémentaires §§§§§\n" . $nota;
			
			echo nl2br('Notes de ' . $result['fullName'] . ' :<br />' . $notes);
			$db = null;
			break;
	}
}
// Si on demande les notes du contact en cliquant sur la zone nav
elseif(isset($_POST['commentaires']))
{
	$_SESSION['champNote'] = 'commentaires';
	if(isset($_POST['id']))
	{
		$db = db_connect();
		$notes = $nota = '';
		$infos = array('rencontre', 'dateCommande', 'dateReception', 'metier', 'dispos', 'divers', 'distance', 'famille', 'dateMES', 'dateCours', 'filleuls');
		$sql = $db->query('SELECT fullName, notes, rencontre, dateCommande, dateReception, metier, dispos, divers, distance, duree, famille, dateMES, dateCours, filleuls FROM contact WHERE c_id="' . $_POST['id'] . '"');
		$result = $sql->fetch(PDO::FETCH_ASSOC);
		
		if(!empty($result['notes']))
			$notes = $result['notes'];
		
		if(!empty($result['rencontre']))
			$nota .= "Rencontre via : " . $result['rencontre'] . "\n";
		
		if(!empty($result['dateCommande']))
			$nota .= "Date commande : " . dateFr($result['dateCommande']) . "\n";
		
		if(!empty($result['dateReception']))
			$nota .= "Date réception : " . dateFr($result['dateReception']) . "\n";
		
		if(!empty($result['metier']))
			$nota .= "Métier : " . $result['metier'] . "\n";
		
		if(!empty($result['dispos']))
			$nota .= "Disponibilités : " . $result['dispos'] . "\n";
		
		if(!empty($result['divers']))
			$nota .= "Divers : " . $result['divers'] . "\n";
		
		if(!empty($result['distance']))
			$nota .= "Distance : " . $result['distance'] . " km\n";
		
		if(!empty($result['duree']))
			$nota .= "Durée trajet : " . substr($result['duree'], 0, 5) . "\n";
		
		if(!empty($result['famille']))
			$nota .= "Famille : " . $result['famille'] . "\n";

		if(!empty($result['dateMES']))
			$nota .= "Date de Mise en Service : " . dateFr($result['dateMES']) . "\n";
			
		if(!empty($result['dateCours']))
			$nota .= "Date cours de cuisine : " . dateFr($result['dateCours']) . "\n";
		
		if(!empty($result['filleuls']))
			$nota .= "Filleuls : " . $result['filleuls'] . "\n";
		
		if(!empty($nota))
			$notes .= "\n\n§§§§§ Informations complémentaires §§§§§\n" . $nota;
		
		echo nl2br('Notes de ' . $result['fullName'] . ' :<br />' . $notes);
		$db = null;
	}
	else
	{
		echo 'Notes :<br />';
	}
}
// Si on demande les interventions d'un contact en cliquant sur la zone nav
elseif(isset($_POST['interventions']))
{
	$_SESSION['champNote'] = 'interventions';
	if(isset($_POST['id']))
		displayResume($_POST['id'], false);
}
// Si on demande la liste des livres d'un contact
elseif(isset($_POST['livres']))
{
	$_SESSION['champNote'] = 'livres';
	if(isset($_POST['id']))
		displayLivres($_POST['id']);
	else
		echo 'Liste des livres : <br />';
}
// Si on demande les commentaires ou résumé d'une intervention en cliquant sur une intervention
elseif(isset($_POST['noteInter']))
{
	switch($_SESSION['champInter'])
	{
		case 'resume':
			displayResume($_POST['noteInter'], true);
		break;
		
		case 'commentaires':
		default:
			$db = db_connect();
			$result = $db->query('SELECT noteInter FROM interventions WHERE i_id =' . $_POST['noteInter']);
			$note = $result->fetch(PDO::FETCH_ASSOC);
			echo 'Commentaires : <br>' . $note['noteInter'];
			$db = null;
			break;
	}
}
// Si on demande les commentaires d'une intervention en cliquant sur la zone nav
elseif(isset($_POST['commentairesInter']))
{
	$_SESSION['champInter'] = 'commentaires';
	if(isset($_POST['id']))
	{
		$db = db_connect();
		$result = $db->query('SELECT noteInter FROM interventions WHERE i_id =' . $_POST['id']);
		$note = $result->fetch(PDO::FETCH_ASSOC);
		echo 'Commentaires : <br>' . $note['noteInter'];
		$db = null;
	}
	else
		echo 'Commentaires :';
}
// Si on demande le résumé des interventions en cliquant sur la zone nav
elseif(isset($_POST['resume']))
{
	$_SESSION['champInter'] = 'resume';
	if(isset($_POST['id']))
		displayResume($_POST['id'], true);
	else
		echo 'Résumé des interventions :';
}
// Réessaie de géolocaliser l'adresse du contact
elseif(isset($_POST['reload']))
{
	require_once('../src/google.php');
	$db = db_connect();
	$adresse = $db->query('SELECT CONCAT(rue, " ", postcode, " ", ville) AS formatted FROM adresses WHERE a_id = ' . $_POST['reload'])->fetch(PDO::FETCH_ASSOC);
	$retour = geoCode($adresse['formatted']);
 	if($retour['status'] == 'OK')
	{
		$db->exec('UPDATE adresses SET lat = ' . $retour['results'][0]['geometry']['location']['lat'] . ', lng = ' . $retour['results'][0]['geometry']['location']['lng'] . ' WHERE a_id = ' . $_POST['reload']);
		echo true;
	}
	else
		echo $retour['status'];
	$db = null;
}

// Affiche l'ensemble des livres du contact
function displayLivres($id)
{
	echo 'Liste des livres : <br />';
	echo '<div>';
		$db = db_connect();
		$requete = $db->query('SELECT livre, label, categorie, numero FROM livres ORDER BY categorie DESC, numero');
		$liste = $requete->fetchAll(PDO::FETCH_ASSOC);
		$sql = $db->query('SELECT * FROM contact WHERE c_id="' . $id . '"');
		$contact = $sql->fetch(PDO::FETCH_ASSOC);
		$previous = '';
			
		 foreach($liste as $value)
		{
			if($contact[$value['label']])
			{
				if($value['categorie'] != $previous && $previous != '')
				{
					echo '</div><div class="familleLivres"><div>' . $value['categorie'] . '</div>';
				}
				elseif($previous == '')
				{
					echo '<div class="familleLivres"><div>' . $value['categorie'] . '</div>';
				}
				echo $value['numero'] . '. ' . $value['livre'] . '<br>';
				$previous = $value['categorie'];
			}
		}
	echo '</div>';	
	$db = null;
}

// Affiche le résumé des interventions
function displayResume($id, $i_id = false)
{
	$db = db_connect();
	if(!$i_id)
		$where = '"' . $id . '"';
	else
		$where = '(SELECT c_id FROM interventions WHERE i_id=' . $id . ')';
	
	$result = $db->query('SELECT i_id, done, priority, date, heure, typeInter, noteInter FROM interventions WHERE c_id = ' . $where . ' ORDER BY date, heure');
	echo 'Résumé des interventions :<br>';
	while($inter = $result->fetch(PDO::FETCH_ASSOC))
	{
		if($inter['done'])
			echo '<div class="inter_finie">';
		else
			echo '<div class="inter_non_finie">';
		
		echo '<span class="cursor" id="' . $inter['i_id'] . '">[ ' . dateFr($inter['date']) . ' ';
		if($inter['heure'] != '00:00:00')
			echo $inter['heure'] . ' ';
		echo '- ' . ucfirst($inter['typeInter']) . ' ]</span><br>' . nl2br(html_entity_decode($inter['noteInter'])) . '</div>';
	}
	$db = null;
}


?>