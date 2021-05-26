<?php
/**************************************************************/
/* globals.php
/* Contient les fonctions d'execution courantes sur la bdd et de formatage des chaines.
/**************************************************************/

require_once('config.php');
require_once('db.php');

/* Formate le champ téléphonique pour l'uniformiser et afficher le label correspondant */
function parse_field_tel($tel)
{
		$tel = str_replace('+33', '0', $tel);
		if(strpos($tel, ' ') == false)
			$tel = preg_replace('#(\d{2})#', '$1 ', $tel);
			
		return $tel;
}

/* Formate le champ date pour y enlever le T et le Z du champ Google */
function parse_date($date)
{
	$date = str_replace('T', ' ', $date);
	$date = str_replace('Z', '', $date);
	return $date;
}

/* Retourne le champ label de manière compréhensible et en français */
function parse_label($label)
{
	$temp = explode('#', $label);
	$label = array_pop($temp);
	
	switch($label)
	{
		case 'home':
			$label = 'Maison';
			break;
		case 'mobile':
			$label = 'Portable';
			break;
		case 'work':
			$label = 'Travail';
			break;
		case 'other':
			$label = 'Autre';
			break;
	}

	return $label;
}

/************/
/* Cherche une valeur dans un tableau récurssivement */
/* $needle : la valeur à chercher
/* $haystack : le tableau dans lequel chercher
/*************/
function recursive_array_search($needle,$haystack) {
    foreach($haystack as $key=>$value) {
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

/************/
/* Cherche une clé dans un tableau récurssivement */
/* $needle : la clé à chercher
/* $tab : le tableau dans lequel chercher
/*************/
function recursive_key_search($needle, $tab)
{
    foreach($tab as $key=>$value) {
        $current_key=$key;
        if($needle===$key OR (is_array($value) && recursive_key_search($needle,$value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

/************/
/* Tri un tableau multidimensionnel à 2 champs (contacts) */
/* $tableau : le tableau à trier
/* $champ : le champ sur lequel trier
/*************/
function csort(&$tableau, $champ1, $champ2)
{
	usort($tableau, function ($a, $b) use ($champ1, $champ2)
	{
		if($a[$champ1][$champ2]['$t'] > $b[$champ1][$champ2]['$t'])
			return 1;
		elseif($a == $b)
			return 0;
		else
			return -1;
	});
}

/************/
/* Tri un tableau multidimensionnel à 1 champ (groupes)*/
/* $tableau : le tableau à trier
/* $champ : le champ sur lequel trier
/*************/
function gsort(&$tableau, $champ)
{
	usort($tableau, function ($a, $b) use ($champ)
	{
		if($a[$champ]['$t'] > $b[$champ]['$t'])
			return 1;
		elseif($a == $b)
			return 0;
		else
			return -1;
	});
}

/************/
/* Supprime la partie du tableau ne contenant pas la clé */
/* $tableau : le tableau à trier
/* $cle : la clé à chercher
/*************/
function vidange(&$tableau, $cle)
{
	foreach($tableau as $key => $value)
	{
		if(!isset($value[$cle]))
			unset($tableau[$key]);
	}
}

/************/
/* Crée un nouveau contact chez Google et en base */
/* $post : données du contact passées en post
/*************/
function ajouter_contact($post)
{
	$fullName = $previous = $note = '';
	$cpt = 0;
	$db = db_connect();
	
	$new = array('entry' => array(
		'category'	=>	array(
							'term'		=>	'http://schemas.google.com/contact/2008#contact',
							'scheme'	=>	'http://schemas.google.com/g/2005#kind'
						),
		//'gContact$groupMembershipInfo'	=>	$groupes
	), 'version' => '1.0', 'encoding' => 'UTF-8');

	if(!empty($post['prefixe']))
	{
		$fullName .= $post['prefixe'] . ' ';
		$new['entry']['gd$name']['gd$namePrefix']['$t'] = $post['prefixe'];
	}

	if(!empty($post['prenom']))
	{
		$fullName .= $prenom = $post['prenom'] . ' ';
		$new['entry']['gd$name']['gd$givenName']['$t'] = $post['prenom'];
	}
	
	if(!empty($post['additional']))
	{
		$fullName .= $additional = $post['additional'] . ' ';
		$new['entry']['gd$name']['gd$additionalName']['$t'] = $post['additional'];
	}

	if(!empty($post['nom']))
	{
		$fullName .= $nom = $post['nom'] . ' ';
		$new['entry']['gd$name']['gd$familyName']['$t'] = $post['nom'];
	}

	if(!empty($post['suffixe']))
	{
		$fullName .= $suffixe = $post['suffixe'] . ' ';
		$new['entry']['gd$name']['gd$nameSuffix']['$t'] = $post['suffixe'];
	}
	
	$new['entry']['gd$name']['gd$fullName']['$t'] = substr($fullName, 0, -1);

	$notes = insertion_notes($post, 'post');
	if($notes)
		$new['entry']['content']['$t'] = $notes;
	
	if(isset($post['email-0']))
	{
		for($i = 0; $i < 10; $i++)
		{
			if(isset($post['email-' . $i]))
			{
				$new['entry']['gd$email'][$i] = array(
										'address'	=>	$post['email-' . $i],
										'primary'	=>	true,
									 );
				if(!empty($post['lEmail-' . $i]))
					$new['entry']['gd$email'][$i]['label'] = $post['lEmail-' . $i];
				else
					$new['entry']['gd$email'][$i]['rel'] = 'http://schemas.google.com/g/2005#other';
			}
			else
				break;
		}
	}
	if(isset($post['tel-0']))
	{
		for($i = 0; $i < 10; $i++)
		{
			if(isset($post['tel-' . $i]))
			{
				$new['entry']['gd$phoneNumber'][$i] = array(
									'primary'	=>	true,
									'$t'			=>	$post['tel-' . $i],
									 );
				if(!empty($post['lTel-' . $i]))
					$new['entry']['gd$phoneNumber'][$i]['label'] = $post['lTel-' . $i];
				else
					$new['entry']['gd$phoneNumber'][$i]['rel'] = 'http://schemas.google.com/g/2005#other';
			}
			else
				break;
		}
	}
	if(!empty($post['ville-0']))
	{
		for($i = 0; $i < 10; $i++)
		{
			if(isset($post['ville-' . $i]))
			{
				$new['entry']['gd$structuredPostalAddress'][$i] = array(
										 'gd$street'		=>	array('$t'	=>	(isset($post['rue-' . $i])) ? $post['rue-' . $i] : ''),
										 'gd$city'			=>	array('$t'	=>	$post['ville-' . $i]),
										 'gd$postcode' 	=>	array('$t'	=>	(isset($post['postcode-' . $i])) ? $post['postcode-' . $i] : ''),
									 );
				if(!empty($post['lAdresse-' . $i]))
					$new['entry']['gd$structuredPostalAddress'][$i]['label'] = $post['lAdresse-' . $i];
				else
					$new['entry']['gd$structuredPostalAddress'][$i]['rel'] = 'http://schemas.google.com/g/2005#other';
				
				$adresse = $new['entry']['gd$structuredPostalAddress'][$i]['gd$street']['$t'] . ' ' . $new['entry']['gd$structuredPostalAddress'][$i]['gd$postcode']['$t'] . ' ' . $new['entry']['gd$structuredPostalAddress'][$i]['gd$city']['$t'];
			}
			else
				break;
		}
	}
	if(!empty($post['groupes']))
	{
		foreach($post['groupes'] as $groupe)
		{
			$new['entry']['gContact$groupMembershipInfo'][] = array(
									'deleted'	=>	'false',
									'href'			=>	'https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/base/' . $groupe
								);
		}
	}
	
	foreach($post as $key => $value)
	{
		switch($key)
		{
			case 'dateCours':
			case 'dateMES':
			case 'dateCommande':
			case 'dateReception':
			case 'rencontre':
			case 'dispos':
			case 'divers':
			case 'distance':
			case 'duree':
			case 'famille':
			case 'filleuls':
			case 'metier':
				$infos[$key] = $value;
				break;
		}
	}
	
	for($i = 0; $i < 7; $i++)
	{
		$insert = insertContacts($new);
		if($insert['code'] == 201)
		{
			$_SESSION['message']  = 'Nouveau contact créé : <strong>' . $fullName . '</strong>';
			break;
		}
		elseif($i == 6 || $insert['code'] != 201)
		{
			$_SESSION['message'] = 'Le contact <strong>' . $fullName . '</strong> n\'a pas été créé suite à une erreur. Erreur ' . $insert['code'];
			break;
		}
		else
			sleep(1);
	}
	

}

/************/
/* Met à jour un contact dans la base suite à la modification manuelle */
/* $post : données du contact passées en post
/*************/
function modif_contact($post)
{
	/* $db = db_connect();
	$c_id = $post['id'];
	$sql = $update = $fullName = '';
	$modif = 0;
	$insert = $formatted = array();
	$contact = read_contacts($c_id, false);
	$requete = $db->query('SELECT label FROM livres ORDER BY categorie DESC, numero');
	$liste = $requete->fetchAll(PDO::FETCH_ASSOC);
	$insert_adresse = $db->prepare('INSERT INTO adresses (c_id, label, formatted, rue, ville, postcode, is_label, lat, lng) VALUES (:c_id, :label, :formatted, :rue, :ville, :postcode, 1, :lat, :lng)');
	$insert_tel = $db->prepare('INSERT INTO tel(c_id, label, tel, principal, is_label) VALUES (:c_id, :label, :tel, 0, 1)');
	$insert_email = $db->prepare('INSERT INTO emails(c_id, label, email, principal, is_label) VALUES (:c_id, :label, :email, 0, 1)');
	$insert_groupe = $db->prepare('INSERT INTO link_groupes_contacts(gr_id, c_id, u_id) VALUES (:gr_id, :c_id, :u_id)');
	
	$insert_adresse->bindParam(':c_id', $c_id, PDO::PARAM_INT);
	$insert_adresse->bindParam(':label', $label_adresse, PDO::PARAM_STR);
	$insert_adresse->bindParam(':formatted', $formattedAdresse, PDO::PARAM_STR);
	$insert_adresse->bindParam(':rue', $rue, PDO::PARAM_STR);
	$insert_adresse->bindParam(':ville', $ville, PDO::PARAM_STR);
	$insert_adresse->bindParam(':postcode', $postcode, PDO::PARAM_INT);
	$insert_adresse->bindParam(':lat', $lat, PDO::PARAM_STR);
	$insert_adresse->bindParam(':lng', $lng, PDO::PARAM_STR);
	$insert_email->bindParam(':c_id', $c_id, PDO::PARAM_INT);
	$insert_email->bindParam(':label', $label_email, PDO::PARAM_STR);
	$insert_email->bindParam(':email', $email, PDO::PARAM_STR);
	$insert_tel->bindParam(':c_id', $c_id, PDO::PARAM_STR);
	$insert_tel->bindParam(':label', $label_tel, PDO::PARAM_STR);
	$insert_tel->bindParam(':tel', $tel, PDO::PARAM_STR);
	$insert_groupe->bindParam(':gr_id', $gr_id, PDO::PARAM_STR);
	$insert_groupe->bindParam(':c_id', $c_id, PDO::PARAM_INT);
	$insert_groupe->bindParam(':u_id', $_SESSION['user'], PDO::PARAM_INT);
	
	foreach($post as $key => $value)
	{
		switch($key)
		{
			case 'prefixe':
				if((!isset($contact['gd$name']['gd$namePrefix']['$t']) && !empty($value)) || (isset($contact['gd$name']['gd$namePrefix']['$t']) && $contact['gd$name']['gd$namePrefix']['$t'] != $value))
				{
					if(empty($value))
					{
						$update .= 'UPDATE contact SET prefixe=NULL WHERE c_id="' . $c_id . '"; ';
						$value = false;
					}
					else
						$update .= 'UPDATE contact SET prefixe="' . $value . '" WHERE c_id="' . $c_id . '"; ';
					$prefixe = $value;
				}
				break;
			
			case 'prenom':
				if((!isset($contact['gd$name']['gd$givenName']['$t']) && !empty($value)) || (isset($contact['gd$name']['gd$givenName']['$t']) && $contact['gd$name']['gd$givenName']['$t'] != $value))
				{
					if(empty($value))
					{
						$update .= 'UPDATE contact SET prenom=NULL WHERE c_id="' . $c_id . '"; ';
						$value = false;
					}
					else
						$update .= 'UPDATE contact SET prenom="' . $value . '" WHERE c_id="' . $c_id . '"; ';
					$prenom = $value;
				}
				break;

			case 'additional':
				if((!isset($contact['gd$name']['gd$additionalName']['$t']) && !empty($value)) || (isset($contact['gd$name']['gd$additionalName']['$t']) && $contact['gd$name']['gd$additionalName']['$t'] != $value))
				{
					if(empty($value))
					{
						$update .= 'UPDATE contact SET additional=NULL WHERE c_id="' . $c_id . '"; ';
						$value = false;
					}
					else
						$update .= 'UPDATE contact SET additional="' . $value . '" WHERE c_id="' . $c_id . '"; ';
					$additional = $value;
				}
				break;

			case 'nom':
				if((!isset($contact['gd$name']['gd$familyName']['$t']) && !empty($value)) || (isset($contact['gd$name']['gd$familyName']['$t']) && $contact['gd$name']['gd$familyName']['$t'] != $value))
				{
					if(empty($value))
					{
						$update .= 'UPDATE contact SET nom=NULL WHERE c_id="' . $c_id . '"; ';
						$value = false;
					}
					else
						$update .= 'UPDATE contact SET nom="' . $value . '" WHERE c_id="' . $c_id . '"; ';
					$nom = $value;
				}
				break;
			
			case 'suffixe':
				if((!isset($contact['gd$name']['gd$nameSuffix']['$t']) && !empty($value)) || (isset($contact['gd$name']['gd$nameSuffix']['$t']) && $contact['gd$name']['gd$nameSuffix']['$t'] != $value))
				{
					if(empty($value))
					{
						$update .= 'UPDATE contact SET suffixe=NULL WHERE c_id="' . $c_id . '"; ';
						$value = false;
					}
					else
						$update .= 'UPDATE contact SET suffixe="' . $value . '" WHERE c_id="' . $c_id . '"; ';
					$suffixe = $value;
				}
				break;
			
			case 'note':
				if((!isset($contact['content']['$t']) && !empty($value)) || (isset($contact['content']['$t']) && $contact['content']['$t'] != $value))
					$update .= 'UPDATE contact SET notes="' . $value . '" WHERE c_id="' . $c_id . '"; ';
				break;
				
			case 'groupes':
				foreach($value as $gr_id)
				{
					if(!isset($contact['gContact$groupMembershipInfo']) || !in_array(array('deleted' => false, 'href' => $gr_id), $contact['gContact$groupMembershipInfo']))
						$insert_groupe->execute();
				}
				break;
				
			case 'dateCours':
			case 'dateMES':
			case 'dateCommande':
			case 'dateReception':
				if((!isset($contact['infos'][$key]) && !empty($value)) ||  (isset($contact['infos'][$key]) && $contact['infos'][$key] != $value))
					$update .= 'UPDATE contact SET ' . $key . '=STR_TO_DATE("' . $value . '", "%d/%m/%Y") WHERE c_id="' . $c_id . '"; ';
				elseif(isset($contact['infos'][$key]) && empty($value))
					$update .= 'UPDATE contact SET ' . $key . ' =NULL WHERE c_id="' . $c_id . '"; ';
				break;

			case 'rencontre':
			case 'dispos':
			case 'divers':
			case 'distance':
			case 'duree':
			case 'famille':
			case 'filleuls':
			case 'metier':
				if((!isset($contact['infos'][$key]) && !empty($value)) || (isset($contact['infos'][$key]) && $contact['infos'][$key] != $value))
					$update .= 'UPDATE contact SET ' . $key . '="' . $value . '" WHERE c_id="' . $c_id . '"; ';
				break;
				
			case 'id':
			case 'e_id':
			case 't_id':
			case 'a_id':
				break;

			default:	
				$info = array();
				$info = explode('-', $key);
				switch($info[0])
				{
					case 'lAdresse':
						if(!isset($contact['gd$structuredPostalAddress'][$info[1]]))
							$insert[$info[1]]['adresse']['label'] = $value;
						else
						{
							$label = (isset($contact['gd$structuredPostalAddress'][$info[1]]['rel'])) ? 'rel' : 'label';
							if($contact['gd$structuredPostalAddress'][$info[1]][$label] != $value)
								$update .= 'UPDATE adresses SET label="' . $value . '", is_label=1 WHERE a_id="' . $contact['gd$structuredPostalAddress'][$info[1]]['a_id'] . '"; ';
						}
						break;
					
					case 'rue':
						if(!isset($contact['gd$structuredPostalAddress'][$info[1]]))
						{
							$insert[$info[1]]['adresse']['rue'] = $value;
						}
						elseif($contact['gd$structuredPostalAddress'][$info[1]]['gd$street']['$t'] != $value)
						{
							$update .= 'UPDATE adresses SET rue="' . $value . '" WHERE a_id="' . $contact['gd$structuredPostalAddress'][$info[1]]['a_id'] . '"; ';
							$geoUp[$info[1]] = $contact['gd$structuredPostalAddress'][$info[1]]['a_id'];
							$formatted[$info[1]]['rue'] = $value;
						}
						break;
					
					case 'ville':
						if(!isset($contact['gd$structuredPostalAddress'][$info[1]]))
						{
							$insert[$info[1]]['adresse']['ville'] = $value;
						}
						elseif($contact['gd$structuredPostalAddress'][$info[1]]['gd$city']['$t'] != $value)
						{
							$update .= 'UPDATE adresses SET ville="' . $value . '" WHERE a_id="' . $contact['gd$structuredPostalAddress'][$info[1]]['a_id'] . '"; ';
							$geoUp[$info[1]] = $contact['gd$structuredPostalAddress'][$info[1]]['a_id'];
							$formatted[$info[1]]['ville'] = $value;
						}
						break;
					
					case 'postcode':
						if(!isset($contact['gd$structuredPostalAddress'][$info[1]]))
						{
							$insert[$info[1]]['adresse']['postcode'] = $value;
						}
						elseif($contact['gd$structuredPostalAddress'][$info[1]]['gd$postcode']['$t'] != $value)
						{
							$update .= 'UPDATE adresses SET postcode="' . $value . '" WHERE a_id="' . $contact['gd$structuredPostalAddress'][$info[1]]['a_id'] . '"; ';
							$geoUp[$info[1]] = $contact['gd$structuredPostalAddress'][$info[1]]['a_id'];
							$formatted[$info[1]]['postcode'] = $value;
						}
						break;
					
					case 'adresseId':
						$formatted[$info[1]]['a_id'] = $value;
						break;
					
					case 'lTel':
						if(!isset($contact['gd$phoneNumber'][$info[1]]))
							$insert[$info[1]]['tel']['label'] = $value;
						else
						{
							$label = (isset($contact['gd$phoneNumber'][$info[1]]['rel'])) ? 'rel' : 'label';
							if($contact['gd$phoneNumber'][$info[1]] != $value)
								$update .= 'UPDATE tel SET label="' . $value . '", is_label=1 WHERE t_id="' . $contact['gd$phoneNumber'][$info[1]]['t_id'] . '"; ';
						}
						break;

					case 'tel':
						if(!isset($contact['gd$phoneNumber'][$info[1]]))
							$insert[$info[1]]['tel']['tel'] = $value;
						elseif($contact['gd$phoneNumber'][$info[1]] != $value)
							$update .= 'UPDATE tel SET tel="' . $value . '" WHERE t_id="' . $contact['gd$phoneNumber'][$info[1]]['t_id'] . '"; ';
						break;
					
					case 'lEmail':
						if(!isset($contact['gd$email'][$info[1]]))
							$insert[$info[1]]['email']['label'] = $value;
						else
						{
							$label = (isset($contact['gd$email'][$info[1]]['rel'])) ? 'rel' : 'label';
							if($contact['gd$email'][$info[1]] != $value)
								$update .= 'UPDATE emails SET label="' . $value . '", is_label=1 WHERE e_id="' . $contact['gd$email'][$info[1]]['e_id'] . '"; ';
						}
						break;

					case 'email':
						if(!isset($contact['gd$email'][$info[1]]))
							$insert[$info[1]]['email']['email'] = $value;
						elseif($contact['gd$email'][$info[1]] != $value)
							$update .= 'UPDATE emails SET email="' . $value . '" WHERE e_id="' . $contact['gd$email'][$info[1]]['e_id'] . '"; ';
						break;
						
					default:
						break;
				}
				break;
		}
	}

	// On vérifie si des infos n'ont pas été retirées
	if(isset($contact['gd$structuredPostalAddress']))
	{
		foreach($contact['gd$structuredPostalAddress'] as $adresse)
		{
			if(!in_array($adresse['gd$street']['$t'], $post) && !in_array($adresse['gd$city']['$t'], $post) && !in_array($adresse['gd$postcode']['$t'], $post) && !in_array($email['a_id'], $post))
				supprime_adresse($adresse['a_id']);
		}
	}
	if(isset($contact['gd$email']))
	{
		foreach($contact['gd$email'] as $email)
		{
			if(!in_array($email['address'], $post) && !in_array($email['e_id'], $post))
				supprime_email($email['e_id']);
		}
	}
	if(isset($contact['gd$phoneNumber']))
	{
		foreach($contact['gd$phoneNumber'] as $tel)
		{
			if(!in_array($tel['$t'], $post) && !in_array($email['t_id'], $post))
				supprime_tel($tel['t_id']);
		}
	}
	if(isset($contact['gContact$groupMembershipInfo']))
	{
		foreach($contact['gContact$groupMembershipInfo'] as $groupe)
		{
			if(!in_array($groupe['href'], $post['groupes']))
				supprime_groupe($groupe['href'], $c_id);
		}
	}
	
	// On met à jour le champ formatted des adresses s'il y a eu des modif dans l'adresse
	if(!empty($formatted))
	{
		foreach($formatted as $key => $format)
		{
			if(!isset($format['rue']))
				$format['rue'] = $contact['gd$structuredPostalAddress'][$key]['gd$street']['$t'];
			if(!isset($format['postcode']))
				$format['postcode'] = $contact['gd$structuredPostalAddress'][$key]['gd$postcode']['$t'];
			if(!isset($format['ville']))
				$format['ville'] = $contact['gd$structuredPostalAddress'][$key]['gd$city']['$t'];
			$db->exec('UPDATE adresses SET formatted = "' . $format['rue'] . ' ' . $format['postcode'] . ' ' . $format['ville'] . '" WHERE a_id = ' . $format['a_id']);
		}
	}
		
	if(isset($prefixe) && $prefixe)
		$fullName = $prefixe . ' ';
	elseif(!isset($prefixe) && !empty($contact['gd$name']['gd$namePrefix']['$t']))
		$fullName = $contact['gd$name']['gd$namePrefix']['$t'] . ' ';
	
	if(isset($prenom) && $prenom)
		$fullName .= $prenom . ' ';
	elseif(!isset($prenom) && !empty($contact['gd$name']['gd$givenName']['$t']))
		$fullName .= $contact['gd$name']['gd$givenName']['$t'] . ' ';
	
	if(isset($additional) && $additional)
		$fullName .= $additional . ' ';
	elseif(!isset($additional) && !empty($contact['gd$name']['gd$additionalName']['$t']))
		$fullName .= $contact['gd$name']['gd$additionalName']['$t'] . ' ';

	if(isset($nom) && $nom)
		$fullName .= $nom . ' ';
	elseif(!isset($nom) && !empty($contact['gd$name']['gd$familyName']['$t']))
		$fullName .= $contact['gd$name']['gd$familyName']['$t'] . ' ';

	if(isset($suffixe) && $suffixe)
		$fullName .= $suffixe . ' ';
	elseif(!isset($suffixe) && !empty($contact['gd$name']['gd$nameSuffix']['$t']))
		$fullName .= $contact['gd$name']['gd$nameSuffix']['$t'] . ' ';
		
	$fullName = substr($fullName, 0, -1);

	if($contact['gd$name']['gd$fullName']['$t'] != $fullName)
		$update .= 'UPDATE contact SET fullName="' . $fullName . '" WHERE c_id="' . $c_id . '"; ';
		
	if($modif)
	{
		$_SESSION['message'] = '<strong>' . $fullName . '</strong> a bien été modifié(e).';
	} */
}

/************/
/* Extrait les notes pour les décomposer dans les divers éléments */
/* $notes : les notes en tableau explosé ou non
/*************/
function extraction_notes($notes)
{
	$notes = explode('§§§§§ Informations complémentaires §§§§§', $notes);
	if(isset($notes[1]))
		$notes[0] = substr($notes[0], 0, -2);
	elseif(strpos($notes[0], '/*****/'))
		$notes = explode('/*****/', $notes[0]);
	
	return $notes[0];
}

/************/
/* Formate les notes pour en obtenir un champ note complet pour l'envoie chez Google */
/* $data: données envoyées
/* $type : type d'envoie : bdd ou post suite à création
/*************/
function insertion_notes($data, $type)
{
	$db = db_connect();
	$requete = $db->query('SELECT livre, label, categorie, numero FROM livres ORDER BY categorie DESC');
	$liste = $requete->fetchAll(PDO::FETCH_ASSOC);
	$notes = $note = $previous = '';
	$cpt = 0;

	if($type == 'bdd')
	{
		$id = $data;
		$sql = $db->query('SELECT * FROM contact WHERE c_id="' . $id . '"');
		$data = $sql->fetch(PDO::FETCH_ASSOC);
		if(!empty($data['notes']))
			$notes = $data['notes'] . "\n";
	}
	elseif(!empty($data['note']))
		$notes = $data['note'] . "\n";
		
	if(!empty($data['rencontre']) || !empty($data['dateCommande']) || !empty($data['dateReception']) || !empty($data['metier']) || !empty($data['dispos']) || !empty($data['divers']) || !empty($data['distance']) || !empty($data['duree']) || !empty($data['famille']) || !empty($data['dateMES']) || !empty($data['dateCours']) || !empty($data['filleuls']))
		$notes .= "\n§§§§§ Informations complémentaires §§§§§\n";
		
	if(!empty($data['rencontre']))
		$notes .= "Rencontre via : " . $data['rencontre'] . "\n";
	
	if(!empty($data['dateCommande']))
		$notes .= "Date commande : " . dateFr($data['dateCommande']) . "\n";
	
	if(!empty($data['dateReception']))
		$notes .= "Date réception : " . dateFr($data['dateReception']) . "\n";
	
	if(!empty($data['metier']))
		$notes .= "Métier : " . $data['metier'] . "\n";
	
	if(!empty($data['dispos']))
		$notes .= "Disponibilités : " . $data['dispos'] . "\n";
	
	if(!empty($data['divers']))
		$notes .= "Divers : " . $data['divers'] . "\n";
	
	if(!empty($data['distance']))
		$notes .= "Distance : " . $data['distance'] . " km\n";
	
	if(!empty($data['duree']))
		$notes .= "Durée trajet : " . $data['duree'] . "\n";
	
	if(!empty($data['famille']))
		$notes .= "Famille : " . $data['famille'] . "\n";
	
	if(!empty($data['dateMES']))
		$notes .= "Date de Mise en Service : " . dateFr($data['dateMES']) . "\n";
	
	if(!empty($data['dateCours']))
		$notes .= "Date cours de cuisine : " . dateFr($data['dateCours']) . "\n";
	
	if(!empty($data['filleuls']))
		$notes .= "Filleuls : " . $data['filleuls'] . "\n";
	
	foreach($liste as $livre)
	{
		if(array_key_exists($livre['label'], $data) && $data[$livre['label']])
		{
			if($cpt == 0)
				$note .= "\n/*****/\nLivres :";
				
			if($previous != $livre['categorie'])
				$note .= "\n" . $livre['categorie'] . "\n";

			$note .= $livre['numero'] . '. ' . $livre['livre'] . "\n";
			$previous = $livre['categorie'];
			$cpt++;
		}
	}

	if(!empty($note))
		$notes .= $note . "/*****/\n\n";			

	$db = null;
	
	if($type == 'bdd')
	{
		$resume = read_interventions($id);
		if($resume != false)
			$notes .= $resume;
	}
	
	if(!empty($notes))
		return $notes;
	else
		return false;
}

/************/
/* Ajoute les données de la nouvelle intervention */
/* $post : données du formulaire si les données proviennent du formulaire
/*************/
function ajout_intervention($post)
{
	foreach($post as $key => $value)
	{
		switch($key)
		{
			case 'done':
				$tab['done'] = true;
				break;
				
			case 'newIntervention':
				$tab['c_id'] = $value;
				break;
				
			case 'addCalendar':
				break;
				
			default:
				$tab[$key] = $value;
				break;
		}
	}
	gestion_intervention($tab, 'insert');
	$_SESSION['base'] = 'base';
}

/************/
/* Met à jour toute la base chez Google */
/*************/
function updateGoogle()
{
	$db = db_connect();
	$i = 0;
	$error = '';
	
	// Récupération de la dernière importation
	$query = $db->query('SELECT last_update_contacts FROM users WHERE u_id = ' . $_SESSION['user']);
	$data = $query->fetch(PDO::FETCH_ASSOC);
	$maj_contacts = strtotime($data['last_update_contacts']);

	$request = $db->query('SELECT c_id, g_id, fullName, last_updated FROM contact WHERE u_id=' . $_SESSION['user']);
	while($current = $request->fetch(PDO::FETCH_ASSOC))
	{
		// Test si le contact a été mis à jour dans la base ou non, pour ne pas générer trop de requêtes
 		if(strtotime($current['last_updated']) > $maj_contacts)
		{
  			$contact = getContact($current['g_id']);
			$db_contact['entry'] = read_contacts($current['c_id'], true);
			$notes = insertion_notes($current['c_id'], 'bdd');
			$db_contact['entry']['content']['$t'] = ($notes != false) ? $notes : '';
			foreach($db_contact['entry']['gContact$groupMembershipInfo'] as $n => $group)
			{
				$db_contact['entry']['gContact$groupMembershipInfo'][$n]['href'] = 'http://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/base/' . $group['href'];
			}
			
			// Vérification qu'il n'existe pas à la fois 'rel' et 'label'
			unset($contact['entry']['gd$phoneNumber'], $contact['entry']['gd$structuredPostalAddress'], $contact['entry']['gd$email'], $db_contact['entry']['infos'], $db_contact['entry']['livres'], $db_contact['entry']['id']);
			$db_contact['entry']['updated']['$t'] = dateGoogle($db_contact['entry']['updated']['$t']);
			$arr = array_replace_recursive($contact, $db_contact);
			if(isset($db_contact['entry']['gContact$groupMembershipInfo']))
				$arr['entry']['gContact$groupMembershipInfo'] = $db_contact['entry']['gContact$groupMembershipInfo'];
			$retour = updateContact($current['g_id'], $arr);
			if($retour != 200)
			{
				$error .= $current['fullName'] . '\n';
			}
			else
				$i++;
 		}
	}
	
	if(!empty($error))
	{
		echo '<script>alert("Ces contacts n\'ont pas été mis à jour suite à une erreur :\n' . $error . '");</script>';
		insert_log(11, 'Ces contacts n\'ont pas été mis à jour suite à une erreur :\n' . $error);
	}
	else
	{
		if($i != 0)
		{
			$_SESSION['base'] = 'Google';
			$db->exec('UPDATE users SET last_update_contacts="' . date('c') . '" WHERE u_id = ' . $_SESSION['user']);
		}
		$_SESSION['message'] = ($i > 1) ? $i . ' contacts mis à jour.' : '1 contact mis à jour.';
		insert_log(11, ($i > 1) ? $i . ' contacts mis à jour.' : '1 contact mis à jour.');
	}
	$db = null;
}

/************/
/* Convertit une date US en FR */
/* $date : la date à convertir
/*************/
function dateFr($date)
{
	return date('d/m/Y', strtotime($date));
}

/************/
/* Convertit un champ time sql en heures et minutes */
/* $heure : le champ time à convertir
/*************/
function heureminute($heure)
{
	$arrayheure = explode(':',$heure);
	$newheure = $arrayheure[0].':'.$arrayheure[1];
	return $newheure;
}

/************/
/* Convertit un champ date au format Google */
/* $date : date à convertir
/*************/
function dateGoogle($date)
{
	$date = substr_replace($date, 'T', 10, 1);
	$date = substr_replace($date, '.000Z', 19, 0);
	
	return $date;
}
?>