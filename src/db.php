<?php
/**************************************************************/
/* db.php
/* Contient les fonctions relatives aux connexions à la BDD
/**************************************************************/

/* Connection à la base de données */
function db_connect()
{
	try
	{
		$db = new PDO('mysql:host=' . SQL_DB . ';dbname=' . SQL_NAME, SQL_LOGIN, SQL_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	}
	catch (PDOException $e)
	{
		print "Erreur !: " . $e->getMessage() . "<br/>";
		die();
	}
	return $db;
}

/************/
/* Envoie le mot de pass en base
/************/
function pass_to_db($login, $password, $adresse)
{
	require_once('google.php');
	$db = db_connect();
	$requete = $db->query('SELECT login, pass FROM users WHERE login="' . $login . '"');
	$liste = $requete->fetch(PDO::FETCH_ASSOC);
	if(empty($liste))
		return 'Mauvaise adresse email';
	elseif($liste['pass'] != '')
		return 'Vous n\'avez pas l\'autorisation de générer un mot de passe';
	else
	{	
		$pass = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);
		if($pass != false)
		{
			$latlng = geoCode($adresse);
			if($latlng['status'] == 'OK')
			{
				$update_pass = $db->exec('
					UPDATE users
					SET pass = "' . $pass . '", lat = "' . $latlng['results'][0]['geometry']['location']['lat'] . '", lng = "' . $latlng['results'][0]['geometry']['location']['lng'] . '"
					WHERE login = "' . $login . '"
				');
				return true;
			}
			else
				return 'Impossible de trouver une adresse. Veuillez contacter <a href="mailto:contact@thermovix.fr">contact@thermovix.fr</a>.';
		}
		else
			return 'Impossible de générer un hash à partir de ce mot de passe. Veuillez réessayer avec un mot de passe différent.</div>';
	}
	$db = null;
}

/************/
/* Vérifie que le mot de passe fourni correspond bien à celui en base.
/************/
function verify_login($login, $pass)
{
	$db = db_connect();
	$requete = $db->query('SELECT u_id, login, pass FROM users WHERE login="' . $login . '"');
	$liste = $requete->fetch(PDO::FETCH_ASSOC);

	if(empty($liste))
		return $retour = array('verif'	=>	false, 'info'	=>	'Mauvaise adresse email');
	else
	{
		if(password_verify($pass, $liste['pass']) == true)
			return $retour = array('verif'	=>	true, 'info'	=>	$liste['u_id']);
		else
			return $retour = array('verif'	=>	false, 'info'	=>	'Mauvais mot de passe');
	}
	$db = null;
}

/************/
/* Récupère toutes les infos des contacts
/* $id : $id du contact s'il y en a qu'un seul à récupérer, sinon on récupère tout
/* $google : savoir si c'est pour envoyer à google ou non, car certains paramètres ne sont pas à prendre en compte
/*************/
function read_contacts($id = false, $google = false)
{
	$db = db_connect();
	$read_adresse = $db->prepare('SELECT a_id, label, formatted, rue, ville, postcode, is_label, lat, lng FROM adresses WHERE c_id=:c_id');
	$read_email = $db->prepare('SELECT e_id, label, email, principal, is_label FROM emails WHERE c_id=:c_id');
	$read_tel = $db->prepare('SELECT t_id, label, tel, principal, is_label FROM tel WHERE c_id=:c_id');
	$read_groupe = $db->prepare('SELECT gr_id FROM link_groupes_contacts WHERE c_id=:c_id');
	$requete = $db->query('SELECT label FROM livres ORDER BY categorie DESC, numero');
	$liste = $requete->fetchAll(PDO::FETCH_ASSOC);
	$tableau = array();
	$cpt = 0;
	
	if(!$id)
		$read = $db->query('SELECT * FROM contact WHERE u_id=' . $_SESSION['user'] . ' ORDER BY fullName');
	else
		$read = $db->query('SELECT * FROM contact WHERE c_id="' . $id . '"');

	while($value = $read->fetch(PDO::FETCH_ASSOC))
	{
		$read_adresse->bindParam(':c_id', $value['c_id'], PDO::PARAM_INT);
		$read_email->bindParam(':c_id', $value['c_id'], PDO::PARAM_INT);
		$read_tel->bindParam(':c_id', $value['c_id'], PDO::PARAM_INT);
		$read_groupe->bindParam(':c_id', $value['c_id'], PDO::PARAM_INT);
		
		$tableau[$cpt] = array(
			'id'			=>	($google) ? array('$t'	=>	$value['g_id']) : $value['c_id'],
			'updated'	=>	array('$t'	=>	$value['last_updated']),
			'gd$name'	=>	array(
				'gd$fullName'				=>	array('$t'	=>	$value['fullName']),
				'gd$namePrefix'			=>	array('$t'	=>	$value['prefixe']),
				'gd$givenName'			=>	array('$t'	=>	$value['prenom']),
				'gd$additionalName'	=>	array('$t'	=>	$value['additional']),
				'gd$familyName'		=>	array('$t'	=>	$value['nom']),
				'gd$nameSuffix'			=>	array('$t'	=>	$value['suffixe'])
			),
			'infos'		=>	array(
				'rencontre'				=>	$value['rencontre'],
				'dateCommande'	=>	$value['dateCommande'],
				'dateReception'		=>	$value['dateReception'],
				'metier'					=>	$value['metier'],
				'dispos'					=>	$value['dispos'],
				'divers'					=>	$value['divers'],
				'distance'				=>	$value['distance'],
				'duree'					=>	$value['duree'],
				'famille'					=>	$value['famille'],
				'dateMES'				=>	$value['dateMES'],
				'dateCours'			=>	$value['dateCours'],
				'filleuls'					=>	$value['filleuls'],
			),
		);

		if(!empty($value['notes']))
			$tableau[$cpt]['content']['$t'] = $value['notes'];
		
		foreach($liste as $livre)
		{
			$tableau[$cpt]['livres'][$livre['label']] = $value[$livre['label']];
		}
		
		$read_email->execute();
		foreach($read_email as $key => $email)
		{
			$tableau[$cpt]['gd$email'][$key]['address'] = $email['email'];
			if($email['principal'])
				$tableau[$cpt]['gd$email'][$key]['primary'] = true;
			
			if($email['is_label'])
				$tableau[$cpt]['gd$email'][$key]['label'] = $email['label'];
			else
				$tableau[$cpt]['gd$email'][$key]['rel'] = $email['label'];
			
			if(!$google)
				$tableau[$cpt]['gd$email'][$key]['e_id'] = $email['e_id'];
		}
		
		$read_tel->execute();
		foreach($read_tel as $key => $tel)
		{
			$tableau[$cpt]['gd$phoneNumber'][$key]['$t'] = $tel['tel'];
			if($tel['principal'])
				$tableau[$cpt]['gd$phoneNumber'][$key]['primary'] = true;
			
			if($tel['is_label'])
				$tableau[$cpt]['gd$phoneNumber'][$key]['label'] = $tel['label'];
			else
				$tableau[$cpt]['gd$phoneNumber'][$key]['rel'] = $tel['label'];
			
			if(!$google)
				$tableau[$cpt]['gd$phoneNumber'][$key]['t_id'] = $tel['t_id'];
		}
		
		$read_adresse->execute();
		foreach($read_adresse as $key => $adresse)
		{
			$tableau[$cpt]['gd$structuredPostalAddress'][$key] = array(
				'gd$formattedAddress'	=>	array('$t'	=>	$adresse['formatted']),
				'gd$street'						=>	array('$t'	=>	$adresse['rue']),
				'gd$city'						=>	array('$t'	=>	$adresse['ville']),
				'gd$postcode'				=>	array('$t'	=>	$adresse['postcode'])
			);
			
			if($adresse['is_label'])
				$tableau[$cpt]['gd$structuredPostalAddress'][$key]['label'] = $adresse['label'];
			else
				$tableau[$cpt]['gd$structuredPostalAddress'][$key]['rel'] = $adresse['label'];
			
			if(!$google)
			{
				$tableau[$cpt]['gd$structuredPostalAddress'][$key]['lat'] = $adresse['lat'];
				$tableau[$cpt]['gd$structuredPostalAddress'][$key]['lng'] = $adresse['lng'];
				$tableau[$cpt]['gd$structuredPostalAddress'][$key]['a_id'] = $adresse['a_id'];
			}
		}
		
		$read_groupe->execute();
		foreach($read_groupe as $groupe)
		{
			$tableau[$cpt]['gContact$groupMembershipInfo'][] = array(
				'deleted'	=>	false,
				'href'			=>	$groupe['gr_id']
			);
		}
		$cpt++;
	}
	$db = null;
	if($id != false)
		$tableau = $tableau[0];
	return $tableau;
}

/************/
/* Récupère toutes les infos des groupes et les place en session
/*************/
function read_groups()
{
	$db = db_connect();
	$_SESSION['groups']['feed']['entry'] = array();
	$cpt = 0;
	
	$read = $db->query('SELECT gr_id, nom, last_updated FROM groupes WHERE u_id=' . $_SESSION['user'] . ' ORDER BY nom');
	while($value = $read->fetch(PDO::FETCH_ASSOC))
	{
		$_SESSION['groups']['feed']['entry'][$cpt]['id']['$t'] = $value['gr_id'];
		$_SESSION['groups']['feed']['entry'][$cpt]['title']['$t'] = $value['nom'];
		$_SESSION['groups']['feed']['entry'][$cpt]['updated']['$t'] = $value['last_updated'];
		$cpt++;
	}
}

/************/
/* Recueille toutes les interventions d'un contact
/* $id : l'id du contact dont on veut sortir les interventions
/*************/
function read_interventions($id)
{
	$db = db_connect();
	$i = 0;
	$resume = false;

	$result = $db->query('SELECT i_id, done, priority, date, heure, typeInter, noteInter FROM interventions WHERE c_id = "' . $id . '" ORDER BY date, heure');
	while($inter = $result->fetch(PDO::FETCH_ASSOC))
	{
		if($i == 0)
			$resume =  "Résumé des interventions :\n";
		
		if($inter['done'])
			$resume .= '*';
		
		$resume .=  '[ ' . dateFr($inter['date']) . ' ';
		if($inter['heure'] != '00:00:00')
			$resume .= $inter['heure'] . ' ';
		$resume .= '- ' . ucfirst($inter['typeInter']) . " ]\n";
		
		if(!empty($inter['typeInter']))
			$resume .= html_entity_decode($inter['noteInter']) . "\n";
		$resume .= "\n";
		$i++;
	}
	$db = null;
	return $resume;
}

/************/
/* Met à jour la base sur la date de dernière modification
/* $champ : champ de la colonne label à modifier
/* $date : met la date courante si elle n'est pas passée en paramètre
/*************/
function update_last_update($champ, $date = false)
{
	$db = db_connect();
	
	if(!$date)
		$date = date('c');
	$update_date = $db->exec('UPDATE users SET ' . $champ . ' = "' . $date . '" WHERE u_id = ' . $_SESSION['user']);
	
	$db = null;
}

/************/
/* Sauvegarde les contacts et groupes en base s'il y a eu des modifications chez Google
/* $contacts : le tableau contacts venant de la session
/* $groupes : le tableau groupes venant de la session
/*************/
function save_in_db($contacts, $groupes)
{
	$db = db_connect();
	$gr_retour = $g_retour = '';
	$query = $db->query('SELECT last_update_contacts, last_update_groupes FROM users WHERE u_id=' . $_SESSION['user']);
	$data = $query->fetch(PDO::FETCH_ASSOC);

/* 	// On enregistre les données seulement s'il y a eu une mise à jour depuis
  	if((strtotime(parse_date($groupes['feed']['updated']['$t'])) > strtotime($data['last_update_groupes'])))
	{
 */		save_groupes_db($groupes);
		update_last_update('last_update_groupes');
/* 	}
	
 	if((strtotime(parse_date($contacts['feed']['updated']['$t'])) > strtotime($data['last_update_contacts'])))
	{
 */  		save_contacts_db($contacts);
		update_last_update('last_update_contacts');
// 	}
 
	$db = null;
}

/************/
/* Sauvegarde les contacts passés en paramètre dans la base de données ou les mets à jour s'ils existent, seulement si la date de mise à jour est différente
/* $contacts : le tableau contacts venant de la session
/*************/
function save_contacts_db($contacts)
{
	$db = db_connect();
	try
	{
		$db->beginTransaction();
		$insert = $db->prepare("INSERT INTO contact (u_id, g_id, fullName, prefixe, prenom, additional, nom, suffixe, notes, last_updated) VALUES (:u_id, :g_id, :fullName, :prefixe, :prenom, :additional, :nom, :suffixe, :notes, :last_updated)");
		$insert_email = $db->prepare("INSERT INTO emails (c_id, label, email, principal, is_label) VALUES (:c_id, :label, :email, :principal, :is_label)");
		$insert_tel = $db->prepare("INSERT INTO tel (c_id, label, tel, principal, is_label) VALUES (:c_id, :label, :tel, :principal, :is_label)");
		$insert_adresse = $db->prepare("INSERT INTO adresses (c_id, label, formatted, rue, ville, postcode, is_label, lat, lng) VALUES (:c_id, :label, :formatted, :rue, :ville, :postcode, :is_label, :lat, :lng)");
		$insert_groupes = $db->prepare("INSERT INTO link_groupes_contacts (gr_id, c_id, u_id) VALUES (:gr_id, :c_id, :u_id)");
		$update = $db->prepare("UPDATE contact SET fullName=:fullName, prefixe=:prefixe, prenom=:prenom, additional=:additional, nom=:nom, suffixe=:suffixe, notes=:notes, last_updated=:last_updated WHERE u_id = :u_id AND g_id=:g_id");

		$insert->bindParam(':u_id', $_SESSION['user'], PDO::PARAM_INT);
		$insert->bindParam(':g_id', $g_id, PDO::PARAM_STR);
		$insert->bindParam(':fullName', $fullName, PDO::PARAM_STR);
		$insert->bindParam(':prefixe', $prefixe, PDO::PARAM_STR);
		$insert->bindParam(':prenom', $prenom, PDO::PARAM_STR);
		$insert->bindParam(':additional', $additional, PDO::PARAM_STR);
		$insert->bindParam(':nom', $nom, PDO::PARAM_STR);
		$insert->bindParam(':suffixe', $suffixe, PDO::PARAM_STR);
		$insert->bindParam(':notes', $notes, PDO::PARAM_STR);
		$insert->bindParam(':last_updated', $last_updated, PDO::PARAM_STR);
		$insert_email->bindParam(':c_id', $c_id, PDO::PARAM_INT);
		$insert_email->bindParam(':label', $label_email, PDO::PARAM_STR);
		$insert_email->bindParam(':email', $email, PDO::PARAM_STR);
		$insert_email->bindParam(':principal', $principal_email, PDO::PARAM_INT);
		$insert_email->bindParam(':is_label', $is_label_email, PDO::PARAM_INT);
		$insert_tel->bindParam(':c_id', $c_id, PDO::PARAM_INT);
		$insert_tel->bindParam(':label', $label_tel, PDO::PARAM_STR);
		$insert_tel->bindParam(':tel', $tel, PDO::PARAM_STR);
		$insert_tel->bindParam(':principal', $principal_tel, PDO::PARAM_INT);
		$insert_tel->bindParam(':is_label', $is_label_tel, PDO::PARAM_INT);
		$insert_adresse->bindParam(':c_id', $c_id, PDO::PARAM_INT);
		$insert_adresse->bindParam(':label', $label_adresse, PDO::PARAM_STR);
		$insert_adresse->bindParam(':formatted', $formatted, PDO::PARAM_STR);
		$insert_adresse->bindParam(':rue', $rue, PDO::PARAM_STR);
		$insert_adresse->bindParam(':ville', $ville, PDO::PARAM_STR);
		$insert_adresse->bindParam(':postcode', $postcode, PDO::PARAM_INT);
		$insert_adresse->bindParam(':is_label', $is_label_adresse, PDO::PARAM_INT);
		$insert_adresse->bindParam(':lat', $lat, PDO::PARAM_STR);
		$insert_adresse->bindParam(':lng', $lng, PDO::PARAM_STR);
		$insert_groupes->bindParam(':gr_id', $gr_id, PDO::PARAM_STR);
		$insert_groupes->bindParam(':c_id', $c_id, PDO::PARAM_INT);
		$insert_groupes->bindParam(':u_id', $_SESSION['user'], PDO::PARAM_INT);
		$update->bindParam(':g_id', $g_id, PDO::PARAM_STR);
		$update->bindParam(':fullName', $fullName, PDO::PARAM_STR);
		$update->bindParam(':prefixe', $prefixe, PDO::PARAM_STR);
		$update->bindParam(':prenom', $prenom, PDO::PARAM_STR);
		$update->bindParam(':additional', $additional, PDO::PARAM_STR);
		$update->bindParam(':nom', $nom, PDO::PARAM_STR);
		$update->bindParam(':suffixe', $suffixe, PDO::PARAM_STR);
		$update->bindParam(':notes', $notes, PDO::PARAM_STR);
		$update->bindParam(':last_updated', $last_updated, PDO::PARAM_STR);
		$update->bindParam(':u_id', $_SESSION['user'], PDO::PARAM_INT);

		$g_donnees = $nom_contact = array();
		$cpt_insert = $cpt_update = $max_c_id = 0;
		$log = '';
		
		$last_row = $db->query('SELECT c_id FROM contact ORDER BY c_id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
		$max_c_id = $last_row['c_id'];
		
		$reponse = $db->query('SELECT c_id, g_id, last_updated FROM contact WHERE u_id=' . $_SESSION['user']);
		while ($donnees = $reponse->fetch(PDO::FETCH_ASSOC))
		{
			$g_donnees[$donnees['g_id']] =  $donnees['last_updated'];
		}
		$reponse->closeCursor();
		
		foreach($contacts['feed']['entry'] as $key => $contact)
		{
			$g_id = $contact['id']['$t'];
			if(isset($contact['content']))
				$notes = extraction_notes($contact['content']['$t']);
			else
				$notes = NULL;
			
			$fullName = (isset($contact['gd$name']['gd$fullName'])) ? $contact['gd$name']['gd$fullName']['$t'] : $contact['title']['$t'];
			
			if(isset($contact['gd$name']))
			{
				$prefixe = (isset($contact['gd$name']['gd$namePrefix'])) ? $contact['gd$name']['gd$namePrefix']['$t'] : NULL;
				$prenom = (isset($contact['gd$name']['gd$givenName'])) ? $contact['gd$name']['gd$givenName']['$t'] : NULL;
				$additional = (isset($contact['gd$name']['gd$additionalName'])) ? $contact['gd$name']['gd$additionalName']['$t'] : NULL;
				$nom = (isset($contact['gd$name']['gd$familyName'])) ? $contact['gd$name']['gd$familyName']['$t'] : NULL;
				$suffixe = (isset($contact['gd$name']['gd$nameSuffix'])) ? $contact['gd$name']['gd$nameSuffix']['$t'] : NULL;
				$last_updated = parse_date($contact['updated']['$t']);
			}

			// Si le contact n'existe pas dans la base
			if((empty($g_donnees) || !array_key_exists($g_id, $g_donnees)) && isset($contact['gContact$groupMembershipInfo']))
			{
				$insert->execute();
				$nom_contact[$cpt_update + $cpt_insert]['insert'] = $fullName;
				$cpt_insert++;			
				$c_id = ++$max_c_id;
			}
			// Sinon on modifie le contact s'il a reçu une mise à jour en supprimant tout ce qui existe déjà
			elseif(isset($contact['gContact$groupMembershipInfo']) && $g_donnees[$g_id] < parse_date($contact['updated']))
			{
				$update->execute();
				$nom_contact[$cpt_update + $cpt_insert]['update'] = $fullName;
				$cpt_update++;
				
				$row = $db->query('SELECT c_id FROM contact WHERE g_id="' . $g_id . '" AND u_id=' . $_SESSION['user'], PDO::FETCH_ASSOC)->fetch();
				$c_id = $row['c_id'] . ' - ';
				
				$delete = 'DELETE FROM link_groupes_contacts WHERE c_id = "' .  $c_id . '";
								DELETE FROM emails WHERE c_id = "' .  $c_id . '";
								DELETE FROM tel WHERE c_id = "' .  $c_id . '";
								DELETE FROM adresses WHERE c_id = "' .  $c_id . '";';
				$db->exec($delete);
			}
			
			
			// On insère son lien aux groupes, ses mails, tel et adresses
			if(isset($contact['gContact$groupMembershipInfo']))
			{
				foreach($contact['gContact$groupMembershipInfo'] as $groupe)
				{
					$gr_id = $groupe['href'];
					try {
						$insert_groupes->execute();
					}
					catch (PDOException $e) {
						print "Erreur !: " . $e->getMessage() . "<br/>";
						die();
					}
				}
				
				if(isset($contact['gd$email']))
				{
					foreach($contact['gd$email'] as $email)
					{
						if(isset($email['label']))
						{
							$label_email = $email['label'];
							$is_label_email = 1;
						}
						else
						{
							$label_email = $email['rel'];
							$is_label_email = 0;
						}
						
						$principal_email = (isset($email['primary'])) ? 1 : 0;
						$email = $email['address'];
						try {
							$insert_email->execute();
						}
						catch (PDOException $e) {
							print "Erreur !: " . $e->getMessage() . "<br/>";
							die();
						}
					}
				}
				if(isset($contact['gd$phoneNumber']))
				{
					foreach($contact['gd$phoneNumber'] as $tel)
					{
						if(isset($tel['label']))
						{
							$label_tel = $tel['label'];
							$is_label_tel = 1;
						}
						else
						{
							$label_tel = $tel['rel'];
							$is_label_tel = 0;
						}
						
						$principal_tel = (isset($tel['primary'])) ? 1 : 0;
						$tel = parse_field_tel($tel['$t']);
						try {
							$insert_tel->execute();
						}
						catch (PDOException $e) {
							print "Erreur !: " . $e->getMessage() . "<br/>";
							die();
						}
					}
				}
				if(isset($contact['gd$structuredPostalAddress']))
				{
					foreach($contact['gd$structuredPostalAddress'] as $adresse)
					{
						if(isset($adresse['label']))
						{
							$label_adresse = $adresse['label'];
							$is_label_adresse = 1;
						}
						else
						{
							$label_adresse = $adresse['rel'];
							$is_label_adresse = 0;
						}
						
						$rue = (isset($adresse['gd$street'])) ? $adresse['gd$street']['$t'] : NULL;
						$ville = (isset($adresse['gd$city'])) ? $adresse['gd$city']['$t'] : NULL;
						$postcode = (isset($adresse['gd$postcode'])) ? intval($adresse['gd$postcode']['$t'], 10) : NULL;
						$formatted = (isset($adresse['gd$formattedAddress'])) ? $adresse['gd$formattedAddress']['$t'] : NULL;
						$lat = (!empty($adresse['lat'])) ? $adresse['lat'] : NULL;
						$lng = (!empty($adresse['lng'])) ? $adresse['lng'] : NULL;
						try {
							$insert_adresse->execute();
						}
						catch (PDOException $e) {
							print "Erreur !: " . $e->getMessage() . "<br/>";
							die();
						}
					}
				}
			}
		}
		
		if($cpt_insert > 0)
		{
			$_SESSION['message'] = $cpt_insert . ' nouveaux contacts insérés.';
			foreach($nom_contact as $value)
			{
				if($value == 'insert')
					$log .= $value['insert'] . ' inséré.<br/>';
				elseif($valule == 'update')
					$log .= $value['update'] . ' mis à jour.<br/>';
			}
			insert_log(10, $cpt_insert . ' nouveaux contacts insérés.<br/>' . $log);
		}
		else
		{
			$_SESSION['message'] =  'Aucune modification sur les contacts.';
			insert_log(10, 'Aucune modification sur les contacts.');
		}
		
		$db->commit();
	}
	catch(PDOException $ex)
	{
		$db->rollBack();
		echo $ex->getMessage();
	}
	$db = null;
}

/************/
/* Sauvegarde les groupes dans la base */
/* $groupes : le tableau groupes venant de la session
/*************/
function save_groupes_db($groupes)
{
	$db = db_connect();
	
	$insert = $db->prepare("INSERT INTO groupes (gr_id, u_id, nom, last_updated) VALUES (:gr_id, :u_id, :nom, :last_updated)");
	$update = $db->prepare("UPDATE groupes SET nom=:nom, last_updated=:last_updated WHERE gr_id=:gr_id AND u_id=:u_id");
	$insert->bindParam(':gr_id', $gr_id);
	$insert->bindParam(':u_id', $_SESSION['user']);
	$insert->bindParam(':nom', $nom);
	$insert->bindParam(':last_updated', $last_updated);
	$update->bindParam(':gr_id', $gr_id);
	$update->bindParam(':nom', $nom);
	$update->bindParam(':last_updated', $last_updated);
	$update->bindParam(':u_id', $_SESSION['user']);
	$gr_donnees = array();
	$cpt_insert = $cpt_update = 0;
	
	$reponse = $db->query('SELECT gr_id, last_updated FROM groupes WHERE u_id=' . $_SESSION['user']);
	while ($donnees = $reponse->fetch(PDO::FETCH_ASSOC))
	{
		$gr_donnees['gr_id'][] = $donnees['gr_id'];
		$gr_donnees['last_updated'][] = $donnees['last_updated'];
	}
	$reponse->closeCursor();
	
	foreach($groupes['feed']['entry'] as $groupe)
	{
		$gr_id = $groupe['id']['$t'];
		switch($groupe['id']['$t'])
		{
			case 6:
				$nom = 'Mes contacts';
				break;
				
			case 'd':
				$nom = 'Amis';
				break;
				
			case 'e':
				$nom = 'Famille';
				break;
			
			case 'f':
				$nom = 'Collègues';
				break;
			
			default:
				$nom = $groupe['title']['$t'];
				break;
		}
		$last_updated = parse_date($groupe['updated']['$t']);
		
		// Si le groupe n'existe pas dans la base
		if(empty($gr_donnees) || !in_array($groupe['id']['$t'], $gr_donnees['gr_id']))
		{
			try {
				$insert->execute();
			} 
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
		// Sinon on modifie
		elseif(!in_array(parse_date($groupe['updated']['$t']), $gr_donnees['last_updated']))
		{
			try {
				$update->execute();
			}
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}

	$db = null;
}

/************/
/* Ajout d'un nouveau contact unique */
/* $contact : le tableau retourné par Google
/*************/
function new_contact_db($contact)
{
	$db = db_connect();
	$requete = $db->query('SELECT label FROM livres ORDER BY categorie DESC, numero');
	$liste = $requete->fetchAll(PDO::FETCH_ASSOC);

	$insert = $db->prepare("INSERT INTO contact (u_id, g_id, fullName, prefixe, prenom, additional, nom, suffixe, notes, last_updated, rencontre, dateCommande, dateReception, metier, dispos, divers, distance, duree, famille, dateMES, dateCours, filleuls) VALUES (:u_id, :g_id, :fullName, :prefixe, :prenom, :additional, :nom, :suffixe, :notes, NOW(), :rencontre, STR_TO_DATE(:dateCommande, '%d/%m/%Y'), STR_TO_DATE(:dateReception, '%d/%m/%Y'), :metier, :dispos, :divers, :distance, :duree, :famille, STR_TO_DATE(:dateCours, '%d/%m/%Y'), STR_TO_DATE(:dateMES, '%d/%m/%Y'), :filleuls)");
	$insert_email = $db->prepare("INSERT INTO emails (c_id, label, email, principal, is_label) VALUES (:c_id, :label, :email, :principal, :is_label)");
	$insert_tel = $db->prepare("INSERT INTO tel (c_id, label, tel, principal, is_label) VALUES (:c_id, :label, :tel, :principal, :is_label)");
	$insert_adresse = $db->prepare("INSERT INTO adresses (c_id, label, formatted, rue, ville, postcode, is_label, lat, lng) VALUES (:c_id, :label, :formatted, :rue, :ville, :postcode, :is_label, :lat, :lng)");
	$insert_groupes = $db->prepare("INSERT INTO link_groupes_contacts (gr_id, c_id, u_id) VALUES (:gr_id, :c_id, :u_id)");

	$insert->bindParam(':u_id', $_SESSION['user']);
	$insert->bindParam(':g_id', $g_id);
	$insert->bindParam(':fullName', $fullName);
	$insert->bindParam(':prefixe', $prefixe);
	$insert->bindParam(':prenom', $prenom);
	$insert->bindParam(':additional', $additional);
	$insert->bindParam(':nom', $nom);
	$insert->bindParam(':suffixe', $suffixe);
	$insert->bindParam(':notes', $notes);
	$insert->bindParam(':rencontre', $rencontre);
	$insert->bindParam(':dateCommande', $dateCommande);
	$insert->bindParam(':dateReception', $dateReception);
	$insert->bindParam(':metier', $metier);
	$insert->bindParam(':dispos', $dispos);
	$insert->bindParam(':divers', $divers);
	$insert->bindParam(':distance', $distance);
	$insert->bindParam(':duree', $duree);
	$insert->bindParam(':famille', $famille);
	$insert->bindParam(':dateMES', $dateMES);
	$insert->bindParam(':dateCours', $dateCours);
	$insert->bindParam(':filleuls', $filleuls);
	$insert_email->bindParam(':c_id', $c_id);
	$insert_email->bindParam(':label', $label_email);
	$insert_email->bindParam(':email', $email);
	$insert_email->bindParam(':principal', $principal_email);
	$insert_email->bindParam(':is_label', $is_label_email);
	$insert_tel->bindParam(':c_id', $c_id);
	$insert_tel->bindParam(':label', $label_tel);
	$insert_tel->bindParam(':tel', $tel);
	$insert_tel->bindParam(':principal', $principal_tel);
	$insert_tel->bindParam(':is_label', $is_label_tel);
	$insert_adresse->bindParam(':c_id', $c_id);
	$insert_adresse->bindParam(':label', $label_adresse);
	$insert_adresse->bindParam(':formatted', $formatted);
	$insert_adresse->bindParam(':rue', $rue);
	$insert_adresse->bindParam(':ville', $ville);
	$insert_adresse->bindParam(':postcode', $postcode);
	$insert_adresse->bindParam(':is_label', $is_label_adresse);
	$insert_adresse->bindParam(':lat', $lat);
	$insert_adresse->bindParam(':lng', $lng);
	$insert_groupes->bindParam(':gr_id', $gr_id);
	$insert_groupes->bindParam(':c_id', $c_id);
	$insert_groupes->bindParam(':u_id', $_SESSION['user']);

	$g_id = $contact['id']['$t'];
		
	$prefixe = (!empty($contact['gd$name']['gd$namePrefix'])) ? $contact['gd$name']['gd$namePrefix']['$t'] : NULL;
	$prenom = (!empty($contact['gd$name']['gd$givenName'])) ? $contact['gd$name']['gd$givenName']['$t'] : NULL;
	$additional = (!empty($contact['gd$name']['gd$additionalName'])) ? $contact['gd$name']['gd$additionalName']['$t'] : NULL;
	$nom = (!empty($contact['gd$name']['gd$familyName'])) ? $contact['gd$name']['gd$familyName']['$t'] : NULL;
	$suffixe = (!empty($contact['gd$name']['gd$nameSuffix'])) ? $contact['gd$name']['gd$nameSuffix']['$t'] : NULL;
	$fullName = (!empty($contact['gd$name']['gd$fullName'])) ? $contact['gd$name']['gd$fullName']['$t'] : NULL;
	$rencontre = (!empty($contact['infos']['rencontre'])) ? $contact['infos']['rencontre'] : NULL;
	$dateCommande = (!empty($contact['infos']['dateCommande'])) ? $contact['infos']['dateCommande'] : NULL;
	$dateReception = (!empty($contact['infos']['dateReception'])) ? $contact['infos']['dateReception'] : NULL;
	$metier = (!empty($contact['infos']['metier'])) ? $contact['infos']['metier'] : NULL;
	$dispos = (!empty($contact['infos']['dispos'])) ? $contact['infos']['dispos'] : NULL;
	$divers = (!empty($contact['infos']['divers'])) ? $contact['infos']['divers'] : NULL;
	$distance = (!empty($contact['infos']['distance'])) ? $contact['infos']['distance'] : NULL;
	$duree = (!empty($contact['infos']['duree'])) ? $contact['infos']['duree'] : NULL;
	$famille = (!empty($contact['infos']['famille'])) ? $contact['infos']['famille'] : NULL;
	$dateMES = (!empty($contact['infos']['dateMES'])) ? $contact['infos']['dateMES'] : NULL;
	$dateCours = (!empty($contact['infos']['dateCours'])) ? $contact['infos']['dateCours'] : NULL;
	$filleuls = (!empty($contact['infos']['filleuls'])) ? $contact['infos']['filleuls'] : NULL;
	$notes =(!empty($contact['content'])) ? $contact['content']['$t'] : NULL;
	
	$insert->execute();
	
	$last_row = $db->query('SELECT MAX(c_id) AS lastId FROM contact')->fetch(PDO::FETCH_ASSOC);
	$c_id = $last_row['lastId'];
	//$c_id = lastInsertId();

	// On insère son lien aux groupes
	if(isset($contact['gContact$groupMembershipInfo']))
	{
		foreach($contact['gContact$groupMembershipInfo'] as $groupe)
		{
			$gr_id = $groupe['href'];
			try {
				$insert_groupes->execute();
			}
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}
	
	if(isset($contact['gd$email']))
	{
		foreach($contact['gd$email'] as $email)
		{
			if(isset($email['label']))
			{
				$label_email = $email['label'];
				$is_label_email = 1;
			}
			else
			{
				$label_email = $email['rel'];
				$is_label_email = 0;
			}
			
			$principal_email = (isset($email['primary'])) ? 1 : 0;
			$email = $email['address'];
			try {
				$insert_email->execute();
			}
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}
	if(isset($contact['gd$phoneNumber']))
	{
		foreach($contact['gd$phoneNumber'] as $tel)
		{
			if(isset($tel['label']))
			{
				$label_tel = $tel['label'];
				$is_label_tel = 1;
			}
			else
			{
				$label_tel = $tel['rel'];
				$is_label_tel = 0;
			}
			
			$principal_tel = (isset($tel['primary'])) ? 1 : 0;
			$tel = parse_field_tel($tel['$t']);
			try {
				$insert_tel->execute();
			}
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}
	if(isset($contact['gd$structuredPostalAddress']))
	{
		foreach($contact['gd$structuredPostalAddress'] as $adresse)
		{
			if(isset($adresse['label']))
			{
				$label_adresse = $adresse['label'];
				$is_label_adresse = 1;
			}
			else
			{
				$label_adresse = $adresse['rel'];
				$is_label_adresse = 0;
			}
			
			$rue = (isset($adresse['gd$street'])) ? $adresse['gd$street']['$t'] : NULL;
			$ville = (isset($adresse['gd$city'])) ? $adresse['gd$city']['$t'] : NULL;
			$postcode = (isset($adresse['gd$postcode'])) ? $adresse['gd$postcode']['$t'] : NULL;
			$formatted = (isset($adresse['gd$formattedAddress'])) ? $adresse['gd$formattedAddress']['$t'] : NULL;
			$lat = (!empty($adresse['lat'])) ? $adresse['lat'] : NULL;
			$lng = (!empty($adresse['lng'])) ? $adresse['lng'] : NULL;
			try {
				$insert_adresse->execute();
			}
			catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}
	
	foreach($liste as $livre)
	{
		if(array_key_exists($livre['label'], $contact['livres']) && $contact['livres'][$livre['label']] == 1)
			$db->exec('UPDATE contact SET ' . $livre['label'] . '=1 WHERE g_id="' . $g_id . '"; ');
	}	
	$db = null;
}

/************/
/* Ajoute un nouveau groupe */
/* $groupe : le nom du groupe à ajouter
/*************/
function ajout_groupe($groupe)
{
	$arr['entry'] = array(
		'category'	=>	array(
									'term'		=>	'http://schemas.google.com/contact/2008#contact',
									'scheme'	=>	'http://schemas.google.com/g/2005#kind'
								),
		'title'			=>	array('$t'	=>	$groupe),
		'version' 	=> '1.0',
		'encoding' 	=> 'UTF-8'
	);
	$retour = newGroup($arr);
	if($retour['code'] == 201)
	{
		$new = json_decode($retour['new'], true);
		$id = search_id($new['entry']);
		$db = db_connect();
		$db->exec('INSERT INTO groupes(u_id, gr_id, nom, last_updated) VALUES("' . $_SESSION['user'] . '", "' . $id['id']['$t'] . '", "' . $groupe . '", "' . $new['entry']['updated']['$t'] . '")');
		$db = null;
		$_SESSION['message'] = 'Groupe ' . $groupe . ' créé avec succès';
		insert_log(4, 'Groupe <strong>' . $groupe . '</strong> créé avec succès');
	}
	else
	{
		$_SESSION['message'] = 'Erreur lors de la création du groupe : erreur ' . $retour['code'];
		insert_log(4, 'Erreur lors de la création du groupe <strong>' . $groupe . '</strong>. Erreur ' . $retour['code']);
	}
}

/************/
/* Modifie le nom d'un groupe */
/* $gr_id : l'id du groupe à modifier
/* $nom : le nouveau nom du groupe
/*************/
function modification_groupe($gr_id, $nom)
{
	$arr = getGroup($gr_id);
	if($arr)
	{
		$ancien_nom = $arr['entry']['title']['$t'];
		$arr['entry']['title']['$t'] = $arr['entry']['content']['$t'] = $nom;
		$retour = updateGroup($gr_id, $arr);

		if($retour == 200)
		{
			$db = db_connect();
			$db->exec('UPDATE groupes SET nom="' . $nom . '" WHERE gr_id="' . $gr_id . '" AND u_id=' . $_SESSION['user']);
			$_SESSION['message'] = 'Changement de nom effectué';
			insert_log(5, 'Changement du nom du groupe <strong>' . $ancien_nom . '</strong> en <strong>' . $nom . '</strong>');
			$db = null;
		}
		else
		{
			$_SESSION['message'] = 'Le groupe n\'a pas pu être modifié : erreur ' . $retour;
			insert_log(5, 'Le groupe <strong>' . $ancien_nom . '</strong> n\'a pas pu être modifié : erreur ' . $retour);
		}
	}
}

/************/
/* Supprime le téléphone de la bdd */
/* $t_id : id du téléphone à supprimer
/*************/
function supprime_tel($t_id)
{
	$db = db_connect();
	$db->exec('DELETE FROM tel WHERE t_id="' . $t_id . '"');
	$db = null;
}

/************/
/* Supprime le courriel  de la bdd */
/* $e_id : id du courriel à supprimer
/*************/
function supprime_email($e_id)
{
	$db = db_connect();
	$db->exec('DELETE FROM emails WHERE e_id="' . $e_id . '"');
	$db = null;
}

/************/
/* Supprime l'adresse de la bdd */
/* $a_id : id de l'adresse à supprimer
/*************/
function supprime_adresse($a_id)
{
	$db = db_connect();
	$db->exec('DELETE FROM adresses WHERE a_id="' . $a_id . '"');
	$db = null;
}

/************/
/* Retire le contact d'un groupe de la bdd */
/* $gr_id : id du groupe à supprimer
/* $c_id : le contact à retirer
/*************/
function supprime_groupe($gr_id, $c_id)
{
	$db = db_connect();
	$db->exec('DELETE FROM link_groupes_contacts WHERE gr_id="' . $gr_id . '" AND c_id="' . $c_id . '"');
	$db = null;
}

/************/
/* Supprime le contact  de la bdd */
/* $id : id du contact à supprimer
/*************/
function delete_contact($id)
{
	$db = db_connect();
	$g_id = $db->query('SELECT g_id, fullName FROM contact WHERE c_id = "' . $id . '"')->fetch(PDO::FETCH_ASSOC);
	$code = deleteContact($g_id['g_id']);
	if($code == 200 || $code == 404)
	{
		$db->exec('DELETE FROM tel WHERE c_id="' . $id . '"; DELETE FROM adresses WHERE c_id="' . $id . '"; DELETE FROM emails WHERE c_id="' . $id . '"; DELETE FROM link_groupes_contacts WHERE c_id="' . $id . '"; DELETE FROM interventions WHERE c_id="' . $id . '"; DELETE FROM contact WHERE c_id="' . $id . '";');
		$_SESSION['message'] = $g_id['fullName'] . ' supprimé(e)';
		insert_log(3, '<strong>' . $g_id['fullName'] . '</strong> supprimé(e)');
	}
	else
	{
		$_SESSION['message'] = 'Erreur lors de la suppression du contact ' . $g_id['fullName'] . ' : erreur ' . $code;
		insert_log(3, 'Erreur lors de la suppression du contact <strong>' . $g_id['fullName'] . '</strong> : erreur ' . $code);
	}
	$db = null;
}

/************/
/* Supprime le groupe  de la bdd */
/* $gr_id : id du groupe à supprimer
/*************/
function delete_groupe($gr_id)
{
	$db = db_connect();
	$sql = $db->query('SELECT nom FROM groupes WHERE gr_id = "' . $gr_id . '"')->fetch(PDO::FETCH_ASSOC);
	$code = deleteGroup($gr_id);
	if($code == '200' || $code == '404')
	{
		$db->exec('DELETE FROM groupes WHERE gr_id="' . $gr_id . '" AND u_id = ' . $_SESSION['user'] . '; DELETE FROM link_groupes_contacts WHERE gr_id="' . $gr_id . '" AND u_id = ' . $_SESSION['user'] . ';');
		$_SESSION['message'] = 'Groupe supprimé';
		insert_log(6, 'Groupe <strong>' . $sql['nom'] . '</strong> supprimé');
	}
	else
	{
		$_SESSION['message'] = 'Erreur lors de la suppression du groupe : erreur ' . $code;
		insert_log(6, 'Echec de la suppression du groupe <strong>' . $sql['nom'] . '</strong>. Erreur : ' . $code);
	}
	$db = null;
}

/************/
/* Crée, modifie ou supprime une intervention */
/* $inter : l'intervention
/* $methode : la méthode à utilier : insert, update ou delete
/*************/
function gestion_intervention($inter, $methode)
{
	$db = db_connect();
	$done = false;
	$priority = $heure = $noteInter = '';
	
	if(!isset($inter['done']))
	{
		$inter['done'] = false;
	}
		
	switch($methode)
	{
		case 'insert':
			$sql = $db->query('SELECT fullname FROM contact WHERE c_id = ' . $inter['c_id'])->fetch(PDO::FETCH_ASSOC);
			$fullname = $sql['fullname'];
			$insert = $db->prepare("INSERT INTO interventions (c_id, done, priority, date, heure, typeInter, noteInter) VALUES (:c_id, :done, :priority, :date, :heure, :typeInter, :noteInter)");
			$insert->bindParam(':c_id', $c_id);
			$insert->bindParam(':done', $done);
			$insert->bindParam(':priority', $priority);
			$insert->bindParam(':date', $date);
			$insert->bindParam(':heure', $hour);
			$insert->bindParam(':typeInter', $typeInter);
			$insert->bindParam(':noteInter', $noteInter);
			
			foreach($inter as $key => $value)
			{
				if($key == 'date')
					$date = date('Y-m-d', strtotime(str_replace('/', '-', $value)));
				elseif($key == 'noteInter')
					$noteInter = htmlentities($value);
				else
					$$key = $value;
			}
			$insert->execute();
			$db->exec('UPDATE contact SET last_updated=NOW() WHERE c_id="' . $c_id . '"');
			$_SESSION['message'] = 'Nouvelle intervention créée';
			insert_log(7, 'Nouvelle intervention créée pour ' . $fullname);
			break;
			
		case 'update':
			$id = $inter['modifIntervention'];
			$sql = $db->query('SELECT fullname FROM contact WHERE c_id = ' . $inter['interOwner'])->fetch(PDO::FETCH_ASSOC);
			$fullname = $sql['fullname'];
			if(isset($inter['done']) && $inter['done'] == true)
				$db->exec('UPDATE interventions SET done=1 WHERE i_id = ' . $id);
			else
				$db->exec('UPDATE interventions SET done=0 WHERE i_id = ' . $id);
			
			foreach($inter as $key => $value)
			{
				switch($key)
				{
					case 'date':
						$db->exec('UPDATE interventions SET date=STR_TO_DATE("' . $value . '", "%d/%m/%Y") WHERE i_id = ' . $id);
						break;
						
					case 'noteInter':
					case 'priority':
					case 'typeInter':
					case 'heure':
						$db->exec('UPDATE interventions SET ' . $key . '="' . $value . '" WHERE i_id = ' . $id);
						break;
				}
			}
			$db->exec('UPDATE contact SET last_updated=NOW() WHERE g_id="' . $inter['interOwner'] . '"');
			$_SESSION['message'] = 'Intervention modifiée';
			insert_log(8, 'Intervention modifiée pour ' . $fullname);
			break;
			
		case 'delete':
			$sql = $db->query('SELECT fullname FROM contact WHERE c_id = ' . $inter['interOwner'])->fetch(PDO::FETCH_ASSOC);
			$fullname = $sql['fullname'];
			$db->exec('UPDATE contact SET last_updated=NOW() WHERE g_id="' . $inter['interOwner'] . '"');
			$db->exec('DELETE FROM interventions WHERE i_id=' . $inter['modifIntervention']);
			$_SESSION['message'] = 'Intervention supprimée';
			insert_log(9, 'Intervention supprimée pour ' . $fullname);
			break;
	}
	$db = null;
}

/************/
/* Insert un log en base */
/* $action : l'action qui a été menée
/* $message : le message qui en a découlé
/*************/
function insert_log($action, $message)
{
	$db = db_connect();
	$insert = $db->prepare('INSERT INTO logs (u_id, action, message, date) VALUES (:u_id, :action, :message, NOW())');
	$insert->bindParam(':u_id', $_SESSION['user'], PDO::PARAM_INT);
	$insert->bindParam(':action', $action, PDO::PARAM_INT);
	$insert->bindParam(':message', $message, PDO::PARAM_STR);
	$insert->execute();
	$db = null;
}

?>