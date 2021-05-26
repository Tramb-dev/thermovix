<?php
/**************************************************************/
/* newIntervention.php
/* Crée une nouvelle intervention pour le contact sélectionné
/**************************************************************/

if(!isset($_SESSION))
	session_start();
	
require_once('globals.php');

if(isset($_GET['id']) && $_GET['id'] != 'undefined')
{
	$id = $_GET['id'];
	$db = db_connect(); ?>
	<div id="popupIntervention">
		<div><?php 
		$request = $db->query('SELECT fullName FROM contact WHERE c_id="' . $id . '"');
		$retour = $request->fetch(PDO::FETCH_ASSOC);
		echo $retour['fullName'];
		$db = null; ?></div>
		<form method="post" action="index.php" id="formIntervention">
		<div>
			<fieldset class="dataInter" name="details">
				<legend>Détails</legend>
				<label><input type="checkbox" name="done">Intervention faite</input></label><br>
				<label>Urgence : 
				<select name="priority">
					<option value="3">Très haute</option>
					<option value="2">Haute</option>
					<option value="1" selected>Normale</option>
					<option value="0">Basse</option>
				</select></label><br>
				<label>Date : <input required="true" type="text" name="date" size="8" class="datepicker" value=""></input></label><br>
				<label>Heure : <input type="text" name="hour" size="4" class="timepicker"></input></label>
			</fieldset>
			<fieldset class="dataInter" name="type_inter">
				<legend>Type d'intervention</legend>
				<select required="true" size="9" name="typeInter" defval="Sélectionnez un champ">
					<option value="appel">Appel</option>
					<option value="email">Email</option>
					<option value="courrier">Courrier</option>
					<option disabled>Rdv</option>
						<option value="r7">&nbsp;&nbsp;R7</option>
						<option value="dci">&nbsp;&nbsp;DCI</option>
						<option value="mes">&nbsp;&nbsp;Mise en service</option>
						<option value="revisite">&nbsp;&nbsp;Revisite</option>
					<option value="livre">Livre à offrir</option>
				</select>
			</fieldset>
			<!---<fieldset class="dataInter" name="calendar">
				<legend>Google Calendar</legend>
				<input type="checkbox" name="addCalendar">Ajouter au calendrier</input>
			</fieldset>--->
			<div class="soumission">
				<input type="submit" name="enregistrer" value="Enregistrer et fermer"></input><br />
				<input type="submit" name="continuer" value="Enregistrer et continuer"></input>
			</div>
		</div>
		<div id="noteIntervention">
			<div><span class="interSelected cursor" id="interCom">Commentaires</span><span class="cursor" id="interRecap">Récapitulatif</span></div>
			<textarea name="noteInter" rows="25" cols="70" placeholder="Commentaires..."></textarea>
		</div>
		<input type="hidden" name="newIntervention" value="<?php echo $id; ?>"></input>
		</form>
	</div><?php
}
elseif(isset($_GET['id']) && $_GET['id'] == 'undefined')
{
	echo '<strong>Veuillez sélectionner un contact.</strong>';
}

?>