<?php
/**************************************************************/
/* modif_intervention.php
/* Affiche l'intervention à modifier
/**************************************************************/
if(!isset($_SESSION))
	session_start();
	
require_once('globals.php');

if(isset($_GET['id']) && $_GET['id'] != 'undefined')
{
	$db = db_connect();
	$id = $_GET['id'];
	$result = $db->query('SELECT i.c_id, i.done, i.priority, i.date, i.heure, i.typeInter, i.noteInter, c.fullName FROM interventions i
		LEFT JOIN contact c
			ON i.c_id = c.c_id
		WHERE i.i_id =' . $id);
	$inter = $result->fetch(PDO::FETCH_ASSOC);
	?>
	<div id="popupIntervention">
		<div><?php echo $inter['fullName']; ?></div>
		<form method="post" action="index.php" id="formIntervention">
		<div>
			<fieldset class="dataInter" name="details">
				<legend>Détails</legend>
				<label><input type="checkbox" name="done" <?php echo ($inter['done']) ? 'checked="checked"' : ''; ?>>Intervention faite</input></label><br>
				<label>Urgence : 
				<select name="priority">
					<option value="3" <?php echo ($inter['priority'] == 3) ? 'selected' : ''; ?>>Très haute</option>
					<option value="2" <?php echo ($inter['priority'] == 2) ? 'selected' : ''; ?>>Haute</option>
					<option value="1" <?php echo ($inter['priority'] == 1) ? 'selected' : ''; ?>>Normale</option>
					<option value="0" <?php echo ($inter['priority'] == 0) ? 'selected' : ''; ?>>Basse</option>
				</select></label><br>
				<label>Date : <input required="true" type="text" name="date" size="8" class="datepicker" value="<?php echo dateFr($inter['date']); ?>"></input></label><br>
				<label>Heure : <input type="text" name="hour" size="4" class="timepicker" <?php echo ($inter['heure'] != '00:00:00') ? 'value="' . heureminute($inter['heure']) . '"' : ''; ?>></input></label>
			</fieldset>
			<fieldset class="dataInter" name="type_inter">
				<legend>Type d'intervention</legend>
				<select required="true" size="9" name="typeInter" defval="Sélectionnez un champ">
					<option value="appel" <?php echo ($inter['typeInter'] == 'appel') ? 'selected' : ''; ?>>Appel</option>
					<option value="email" <?php echo ($inter['typeInter'] == 'email') ? 'selected' : ''; ?>>Email</option>
					<option value="courrier">Courrier</option>
					<option disabled>Rdv</option>
						<option value="r7" <?php echo ($inter['typeInter'] == 'r7') ? 'selected' : ''; ?>>&nbsp;&nbsp;R7</option>
						<option value="dci" <?php echo ($inter['typeInter'] == 'dci') ? 'selected' : ''; ?>>&nbsp;&nbsp;DCI</option>
						<option value="mes" <?php echo ($inter['typeInter'] == 'mes') ? 'selected' : ''; ?>>&nbsp;&nbsp;Mise en service</option>
						<option value="revisite" <?php echo ($inter['typeInter'] == 'revisite') ? 'selected' : ''; ?>>&nbsp;&nbsp;Revisite</option>
					<option value="livre" <?php echo ($inter['typeInter'] == 'livre') ? 'selected' : ''; ?>>Livre à offrir</option>
				</select>
			</fieldset>
			<!---<fieldset class="dataInter" name="calendar">
				<legend>Google Calendar</legend>
				<input type="checkbox" name="addCalendar">Ajouter au calendrier</input>
			</fieldset>--->
			<div class="soumission">
				<input type="submit" name="enregistrer" value="Enregistrer et fermer"></input><br>
				<input type="submit" name="continuer" value="Enregistrer et continuer"></input><br>
				<input type="submit" name="supprimer" value="Supprimer"></input>
			</div>
		</div>
		<div id="noteIntervention">
			<div><span class="interSelected cursor" id="interCom">Commentaires</span><span class="cursor" id="interRecap">Récapitulatif</span></div>
			<textarea name="noteInter" rows="25" cols="70" placeholder="Commentaires..."><?php echo html_entity_decode($inter['noteInter']); ?></textarea>
		</div>
		<input type="hidden" name="modifIntervention" value="<?php echo $id; ?>"></input>
		<input type="hidden" name="interOwner" value="<?php echo $inter['c_id']; ?>"></input>
		</form>
	</div><?php
	$db = null;
}
elseif(isset($_GET['id']) && $_GET['id'] == 'undefined')
{
	echo '<strong>Veuillez sélectionner une intervention.</strong>';
}
?>