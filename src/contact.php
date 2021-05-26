<?php
/**************************************************************/
/* contact.php
/* Affiche le contact existant ou nouveau dans le popup
/**************************************************************/

if(!isset($_SESSION))
	session_start();
	
require_once('globals.php');

if(isset($_GET['c_id']) && $_GET['c_id'] != 'undefined')
{
	//afficheContact($_GET['c_id']);
}
elseif(isset($_GET['addContact']) && $_GET['addContact'])
{ ?>
	<div id="popupContact">
		<form method="post" action="index.php">
			<div class="blocNomContact">
				Nom : <input type="text" name="prefixe" size="5" placeholder="Préfixe">
				<input type="text" name="prenom" size="15" placeholder="Prénom">
				<input type="text" name="additional" size="15" placeholder="Second prénom">
				<input type="text" name="nom" size="15" placeholder="Nom">
				<input type="text" name="suffixe" size="5" placeholder="Suffixe"><br>
			</div>
			<div id="groupes">
				Groupes : <select name="groupes[]" multiple>
 					<?php/* 
					$db = db_connect();
					$sql = $db->query('SELECT gr_id, nom FROM groupes WHERE u_id = ' . $_SESSION['user'] . ' ORDER BY nom');
					while($groupe = $sql->fetch(PDO::FETCH_ASSOC))
					{
						$selected = ($groupe['gr_id'] == '6') ? 'selected' : '';
						echo '<option value="' . $groupe['gr_id'] . '" ' . $selected . '>' . $groupe['nom'] . '</option>';
					}
					$db = null;
					 */?>
				</select>
			</div>
			<div class="blocContact">
				<fieldset name="adresses">
					<legend>Adresses : </legend>
					<div id="ajoutAdresse" class="cursor" data-valeur="0">Ajouter une adresse</div>
				</fieldset>
				<fieldset name="tel">
					<legend>Téléphones : </legend>
					<div id="ajoutTel" class="cursor" data-valeur="0">Ajouter un numéro de téléphone</div>
				</fieldset>
				<fieldset name="emails">
					<legend>Courriels : </legend>
					<div id="ajoutEmail" class="cursor" data-valeur="0">Ajouter une adresse email</div>
				</fieldset>
			</div><!--
			--><fieldset class="blocContact" name="notes">
				<legend>Notes : </legend>
				<textarea rows="20" cols="50" autofocus name="note"></textarea>
			</fieldset>
			<fieldset class="blocContact" name="infoSup">
				<legend>Infos supplémentaires</legend>
				<label>Rencontre via : <input type="text" name="rencontre" size="20"></input></label><br>
				<label>Date commande : <input type="text" name="dateCommande" size="9" class="datepicker"></input></label><br>
				<label>Date réception : <input type="text" name="dateReception" size="9" class="datepicker"></input></label><br>
				<label>Métier : <input type="text" name="metier" size="25"></input></label><br>
				<label>Disponibilités : <textarea rows="3" cols="15" name="dispos"></textarea></label><br>
				<label>Infos diverses : <input type="text" name="divers" size="20"></input></label><br>
				<label>Distance : <input type="text" name="distance" size="3"></input></label> km<br>
				<label>Durée trajet : <input type="text" name="duree" size="4"></input></label> minutes<br>
				<label>Famille : <textarea rows="3" cols="20" name="famille"></textarea></label><br>
				<label>Date Mise en Service : <input type="text" name="dateMES" size="9" class="datepicker"></input></label><br>
				<label>Date cours de cuisine : <input type="text" name="dateCours" size="9" class="datepicker"></input></label><br>
				<label>Filleuls : <textarea rows="3" cols="20" name="filleuls"></textarea></label>
			</fieldset>
			<input type="hidden" value="addContact" name="id">
			<input type="submit" value="Enregistrer" name="save">
		</form>
	</div><?php
}
elseif(isset($_POST['id']) && !empty($_POST['id']))
{
	$modif = modif_contact($_POST);
}
elseif(isset($_GET['c_id']) && $_GET['c_id'] == 'undefined')
{
	echo '<strong>Veuillez sélectionner un contact.</strong>';
}

function afficheContact($id)
{
	$cpt_adresse = $cpt_email = $cpt_tel = 0;
	$db = db_connect();
	$tab = read_contacts($id);
	?>
	<div id="popupContact">
		<form method="post" action="index.php" id="formContact">
			<div class="blocNomContact">
				Nom : <?php
				$prefixe = (isset($tab['gd$name']['gd$namePrefix']['$t'])) ? $tab['gd$name']['gd$namePrefix']['$t'] : '';
				$prenom = (isset($tab['gd$name']['gd$givenName']['$t'])) ? $tab['gd$name']['gd$givenName']['$t'] : '';
				$additional = (isset($tab['gd$name']['gd$additionalName']['$t'])) ? $tab['gd$name']['gd$additionalName']['$t'] : '';
				$nom = (isset($tab['gd$name']['gd$familyName']['$t'])) ? $tab['gd$name']['gd$familyName']['$t'] : '';
				$suffixe = (isset($tab['gd$name']['gd$nameSuffix']['$t'])) ? $tab['gd$name']['gd$nameSuffix']['$t'] : '';
				echo '<input type="text" name="prefixe" size="' . strlen($prefixe) . '" value="' . $prefixe . '" placeholder="Préfixe">
				<input type="text" name="prenom" size="' . strlen($prenom) . '" value="' . $prenom . '" placeholder="Prénom">
				<input type="text" name="additional" size="' . strlen($additional) . '" value="' . $additional . '" placeholder="Second prénom">
				<input type="text" name="nom" size="' . strlen($nom) . '" value="' . $nom . '" placeholder="Nom">
				<input type="text" name="suffixe" size="' . strlen($suffixe) . '" value="' . $suffixe . '" placeholder="Suffixe">'; ?><br>
			</div>
			<div id="maj">Dernière mise à jour le <?php echo dateFR($tab['updated']['$t']) . ' à ' . heureminute(substr($tab['updated']['$t'], 11)); ?></div>
			<div id="groupes">Groupes : <?php
				if(isset($tab['gContact$groupMembershipInfo']))
				{
					foreach($tab['gContact$groupMembershipInfo'] as $groupe)
					{
						if($groupe['href'] == '6')
						{
							echo '<span class="contactGroupe">Mes contacts</span>';
						}
						else
						{
							$reponse = $db->query('SELECT nom FROM groupes WHERE u_id = ' . $_SESSION['user'] . ' AND gr_id="' . $groupe['href'] . '";');
							$data = $reponse->fetchAll(PDO::FETCH_ASSOC);
							
							echo '<span class="contactGroupe">' . $data[0]['nom'] . '</span>';
						}
					}
				}?>
			<span id="modifGroupes" class="cursor">Modifier les groupes</span></div>
			<div id="selectGroupes"><select id="resize" name="groupes[]" multiple><?php/* 
				$sql = $db->query('SELECT gr_id, nom FROM groupes WHERE u_id=' . $_SESSION['user'] . ' ORDER BY nom');
				while($groupe = $sql->fetch(PDO::FETCH_ASSOC))
				{
					$selected = '';
					if(isset($tab['gContact$groupMembershipInfo']) && in_array(array('deleted' => false, 'href' => $groupe['gr_id']), $tab['gContact$groupMembershipInfo']))
						$selected = 'selected ';
					echo '<option value="' . $groupe['gr_id'] . '" ' . $selected . '>' . $groupe['nom'] . '</option>';
				} */ ?>
			</select></div>
			<div class="blocContact">
				<fieldset name="adresses">
					<legend>Adresses : </legend>
					<?php				
					if(isset($tab['gd$structuredPostalAddress']))
					{
						foreach($tab['gd$structuredPostalAddress'] as $adresse)
						{
							(isset($adresse['gd$formattedAddress']['$t'])) ? $formatted = $adresse['gd$formattedAddress']['$t'] : $formatted = '';
							(isset($adresse['gd$street']['$t'])) ? $rue = $adresse['gd$street']['$t'] : $rue = '';
							(isset($adresse['gd$city']['$t'])) ? $ville = $adresse['gd$city']['$t'] : $ville = '';
							(isset($adresse['gd$postcode']['$t'])) ? $postcode = $adresse['gd$postcode']['$t'] : $postcode = '';
							(isset($adresse['label'])) ?	(($adresse['label'] == 'main') ? $label = 'Principal' : $label = $adresse['label']) : $label = parse_label($adresse['rel']);
							echo '<div class="ligneAdresse">';
							echo '<input class="inputarea" type="text" size="8" name="lAdresse-' . $cpt_adresse . '" value="' . $label . '"><div>';
							
							if(empty($rue) && empty($ville) && empty($postcode))
							{
								echo '<textarea name="rue-' . $cpt_adresse . '">' . $formatted . '</textarea><br>';
							}
							else
							{
								echo '<textarea name="rue-' . $cpt_adresse . '">' . $rue . '</textarea><br>';
							}
							echo '<span class="postcode">CP : </span><input class="inputarea" type="text" size="5" name="postcode-' . $cpt_adresse . '" value="' . $postcode . '">';
							echo '<input class="inputarea" type="text" size="12" name="ville-' . $cpt_adresse . '" value="' . $ville . '"></div>';
							echo '<input type="hidden" name="adresseId-' . $cpt_adresse . '" value="' . $adresse['a_id'] . '">';
							echo '<div class="imgMini"><img data-id="' . $adresse['a_id'] . '" class="delete cursor" src="./img/delete.png" /><br>';
							
							if($adresse['lat'] != null && $adresse['lng'] != null)
								echo '<img class="reload locate" src="./img/home.png" width="17px" height="17px" data-id="' . $adresse['a_id'] . '" />';
							else
								echo '<img class="reload cursor" src="./img/reload.png" data-id="' . $adresse['a_id'] . '" />';
							echo '</div></div>';
							$cpt_adresse++;
						}
					}
					?>
					<div id="ajoutAdresse" class="cursor" data-valeur="<?php echo $cpt_adresse; ?>">Ajouter une adresse</div>
				</fieldset>
				<fieldset name="tel">
					<legend>Téléphones : </legend>
					<?php
					if(isset($tab['gd$phoneNumber']))
					{
						foreach($tab['gd$phoneNumber'] as $tel)
						{
							(isset($tel['label'])) ?	(($tel['label'] == 'main') ? $label = 'Principal' : $label = $tel['label']) : $label = parse_label($tel['rel']);
							echo '<div class="ligneTel">';
							echo '<input type="text" size="8" name="lTel-' . $cpt_tel . '" value="' . $label . '">';
							echo '<input type="tel" size="12" name="tel-' . $cpt_tel . '" value="' . $tel['$t'] . '">';
							echo '<input type="hidden" name="telId-' . $cpt_tel . '" value="' . $tel['t_id'] . '">';
							echo '<img id="' . $tel['t_id'] . '" class="delete" src="./img/delete.png" /><br></div>';
							$cpt_tel++;
						}
					}
					?>
					<div id="ajoutTel" class="cursor" data-valeur="<?php echo $cpt_tel; ?>">Ajouter un numéro de téléphone</div>
				</fieldset>
				<fieldset name="emails">
					<legend>Courriels : </legend>
					<?php
					if(isset($tab['gd$email']))
					{
						foreach($tab['gd$email'] as $email)
						{
							(isset($email['label'])) ? (($email['label'] == 'main') ? $label = 'Principal' : $label = $email['label']) : $label = parse_label($email['rel']);
							echo '<div class="ligneEmail">';
							echo '<input type="text" size="8" name="lEmail-' . $cpt_email . '" value="' . $label . '">';
							echo '<input type="email" size="25" name="email-' . $cpt_email . '" value="' . $email['address'] . '">';
							echo '<input type="hidden" name="emailId-' . $cpt_email . '" value="' . $email['e_id'] . '">';
							echo '<img id="' . $email['e_id'] . '" class="delete" src="./img/delete.png" /><br></div>';
							$cpt_email++;
						}
					}
					?>
					<div id="ajoutEmail" class="cursor" data-valeur="<?php echo $cpt_email; ?>">Ajouter une adresse email</div>
				</fieldset>
			</div><!--
			--><fieldset class="blocContact" id="notes">
				<legend>Notes : </legend>
				<textarea rows="20" cols="50" autofocus name="note"><?php echo (isset($tab['content']['$t'])) ? $tab['content']['$t'] : ''; ?></textarea>
			</fieldset>
			<fieldset class="blocContact" name="infoSup">
				<legend>Infos supplémentaires</legend>
				<label>Rencontre via : <input type="text" name="rencontre" size="20" value="<?php echo (isset($tab['infos']['rencontre'])) ? $tab['infos']['rencontre'] : ''; ?>"></input></label><br>
				<label>Date commande : <input type="text" name="dateCommande" size="9" class="datepicker" value="<?php echo (isset($tab['infos']['dateCommande'])) ? dateFr($tab['infos']['dateCommande']) : ''; ?>"></input></label><br>
				<label>Date réception : <input type="text" name="dateReception" size="9" class="datepicker" value="<?php echo (isset($tab['infos']['dateReception'])) ? dateFr($tab['infos']['dateReception']) : ''; ?>"></input></label><br>
				<label>Métier : <input type="text" name="metier" size="25" value="<?php echo (isset($tab['infos']['metier'])) ? $tab['infos']['metier'] : ''; ?>"></input></label><br>
				<label>Disponibilités : <textarea rows="3" cols="15" name="dispos"><?php echo (isset($tab['infos']['dispos'])) ? $tab['infos']['dispos'] : ''; ?></textarea></label><br>
				<label>Infos diverses : <input type="text" name="divers" size="20" value="<?php echo (isset($tab['infos']['divers'])) ? $tab['infos']['divers'] : ''; ?>"></input></label><br>
				<label>Distance : <input type="text" name="distance" size="3" value="<?php echo (isset($tab['infos']['distance'])) ? $tab['infos']['distance'] : ''; ?>"></input></label> km<br>
				<label>Durée trajet : <input type="text" name="duree" size="4" value="<?php echo (isset($tab['infos']['duree'])) ? $tab['infos']['duree'] : ''; ?>"></input></label> minutes<br>
				<label>Famille : <textarea rows="3" cols="20" name="famille"><?php echo (isset($tab['infos']['famille'])) ? $tab['infos']['famille'] : ''; ?></textarea></label><br>
				<label>Date Mise en Service : <input type="text" name="dateMES" size="9" class="datepicker" value="<?php echo (isset($tab['infos']['dateMES'])) ? dateFr($tab['infos']['dateMES']) : ''; ?>"></input></label><br>
				<label>Date cours de cuisine : <input type="text" name="dateCours" size="9" class="datepicker" value="<?php echo (isset($tab['infos']['dateCours'])) ? dateFr($tab['infos']['dateCours']) : ''; ?>"></input></label><br>
				<label>Filleuls : <textarea rows="3" cols="20" name="filleuls"><?php echo (isset($tab['infos']['filleuls'])) ? $tab['infos']['filleuls'] : ''; ?></textarea></label>
			</fieldset>
			<input type="hidden" value="<?php echo $id; ?>" name="id">
			<input type="submit" value="Supprimer" name="delete">
			<div id="addInter">
				<span>Nouvelle intervention</span>
			</div>
			<input type="submit" value="Enregistrer" name="save">
		</form>
	</div><?php
	$db = null;
}
?>