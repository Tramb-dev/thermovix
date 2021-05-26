<?php
/**************************************************************/
/* interventions.php
/* Liste et gère les interventions créés
/**************************************************************/

// Vérifie si la variable de filtre existe
if(!isset($_SESSION['filtre']))
	$_SESSION['filtre'] = array(
		'echelle'	=>	'all',
		'futur'		=>	1,
		'finished'	=>	'non',
		'priority'	=>	4
	);

if(isset($_POST['filtre']) && $_POST['filtre'])
{
	$_SESSION['filtre'] = array();
	foreach($_POST as $key => $value)
	{
		if($key == 'type')
		{
			foreach($_POST['type'] as $cpt => $type)
			{
				$_SESSION['filtre']['type'][$cpt] = $type;
			}
		}
		elseif($key != 'filtre')
			$_SESSION['filtre'][$key] = $value;
	}
}
?>
<div id="caption">Interventions</div>
	<nav>
		<div id="gestion"><div class="titre">Gestion</div>
			<div id="modifInter" class="cursor gestion">Modifier</div>
			<div id="ficheContact" class="cursor gestion">Fiche contact</div>
		</div>
		<div>
			<div class="titre">Filtres</div>
			<form id="filtreInter" action="index.php" method="post">
				<fieldset>
					<legend>Tri par date</legend>
					<input type="radio" name="echelle" value="toToday" class="groupe cursor" <?php echo ($_SESSION['filtre']['echelle'] == 'toToday') ? 'checked="checked"' : ''; ?>>Jusqu'à aujourd'hui</input><br>
					<input type="radio" name="echelle" value="futur" class="groupe cursor" <?php echo ($_SESSION['filtre']['echelle'] == 'futur') ? 'checked="checked"' : ''; ?>>
						<select name="futur">
							<option value="1" <?php echo ($_SESSION['filtre']['futur'] == 1) ? 'selected' : ''; ?>>+ 1 semaine</option>
							<option value="2" <?php echo ($_SESSION['filtre']['futur'] == 2) ? 'selected' : ''; ?>>+ 2 semaine</option>
							<option value="3" <?php echo ($_SESSION['filtre']['futur'] == 3) ? 'selected' : ''; ?>>+ 1 mois</option>
						</select>
					</input><br>
					<input type="radio" name="echelle" value="all" class="groupe cursor" <?php echo ($_SESSION['filtre']['echelle'] == 'all') ? 'checked="checked"' : ''; ?>>Tout</input><br>
				</fieldset>
				<fieldset>
					<legend>Tri par type</legend>
					<select multiple size="9" name="type[]">
						<option value="appel"<?php echo (isset($_SESSION['filtre']['type']) && in_array('appel', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>Appel</option>
						<option value="email"<?php echo (isset($_SESSION['filtre']['type']) && in_array('email', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>Email</option>
						<option value="courrier"<?php echo (isset($_SESSION['filtre']['type']) && in_array('courrier', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>Courrier</option>
						<option disabled>Rdv</option>
							<option value="r7"<?php echo (isset($_SESSION['filtre']['type']) && in_array('r7', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>&nbsp;&nbsp;R7</option>
							<option value="dci"<?php echo (isset($_SESSION['filtre']['type']) && in_array('dci', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>&nbsp;&nbsp;DCI</option>
							<option value="mes"<?php echo (isset($_SESSION['filtre']['type']) && in_array('mes', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>&nbsp;&nbsp;Mise en service</option>
							<option value="revisite"<?php echo (isset($_SESSION['filtre']['type']) && in_array('revisite', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>&nbsp;&nbsp;Revisite</option>
						<option value="livre"<?php echo (isset($_SESSION['filtre']['type']) && in_array('livre', $_SESSION['filtre']['type'])) ? 'selected' : ''; ?>>Livre à offrir</option>
					</select>
				</fieldset>
				<fieldset>
					<legend>Terminée</legend>
					<input type="radio" name="finished" value="oui" class="groupe cursor" <?php echo ($_SESSION['filtre']['finished'] == 'oui') ? 'checked="checked"' : ''; ?>>Oui</input><br>
					<input type="radio" name="finished" value="non" class="groupe cursor" <?php echo ($_SESSION['filtre']['finished'] == 'non') ? 'checked="checked"' : ''; ?>>Non</input><br>
					<input type="radio" name="finished" value="both" class="groupe cursor" <?php echo ($_SESSION['filtre']['finished'] == 'both') ? 'checked="checked"' : ''; ?>>Les deux</input><br>
				</fieldset>
				<label>Urgence : <select name="priority">
					<option value="4" <?php echo ($_SESSION['filtre']['priority'] == 4) ? 'selected' : ''; ?>>Non filtré</option>
					<option value="3" <?php echo ($_SESSION['filtre']['priority'] == 3) ? 'selected' : ''; ?>>Très haute</option>
					<option value="2" <?php echo ($_SESSION['filtre']['priority'] == 2) ? 'selected' : ''; ?>>Haute</option>
					<option value="1" <?php echo ($_SESSION['filtre']['priority'] == 1) ? 'selected' : ''; ?>>Normale</option>
					<option value="0" <?php echo ($_SESSION['filtre']['priority'] == 0) ? 'selected' : ''; ?>>Basse</option>
				</select></label><br>
				<input type="hidden" name="filtre" value="true"/>
				<input type="submit" value="Filtrer"/>
			</form>
		</div>
	</nav>
	
	<section>
		<div class="contenu">
			<table id="tableInterventions">
			<?php
				//$db = db_connect();
				?>
				<tr class="entete">
					<thead>
						<th>Nom</td>
						<th>Type d'intervention</td>
						<th>Date</td>
						<th>Heure</th>
						<th>Urgence</th>
					</thead>
				</tr>
				
				<?php
				$typeInter = array(
					'appel'		=>	'Appel',
					'email'		=>	'Email',
					'courrier'	=>	'Courrier',
					'r7'			=>	'Rendez-vous recette',
					'dci'			=>	'DCI',
					'revisite'	=>	'Revisite',
					'mes'		=>	'Mise en service',
					'livre'		=>	'Livre à offrir'
				);
				
				// Zone de filtre
				if($_SESSION['filtre']['finished'] == 'oui')
					$done = 'i.done=1';
				elseif($_SESSION['filtre']['finished'] == 'non')
					$done = 'i.done=0';
				else
					$done = '';
				
				switch($_SESSION['filtre']['echelle'])
				{
					case 'toToday':
						$echelle = 'i.date <= CURDATE()';
						break;
					
					case 'all':
						$echelle = '';
						break;
					
					case 'futur':
						if($_SESSION['filtre']['futur'] == '1')
							$echelle = 'i.date >= CURDATE() AND i.date <= ADDDATE(CURDATE(), INTERVAL 7 DAY)';
						elseif($_SESSION['filtre']['futur'] == '2')
							$echelle = 'i.date >= CURDATE() AND i.date <= ADDDATE(CURDATE(), INTERVAL 14 DAY)';
						else
							$echelle = 'i.date >= CURDATE() AND i.date <= ADDDATE(CURDATE(), INTERVAL 1 MONTH)';
						break;
				}
				
				if($_SESSION['filtre']['priority'] == '4')
					$priority = '';
				else
					$priority = 'i.priority = ' . $_SESSION['filtre']['priority'];
				
				if(isset($_SESSION['filtre']['type']))
				{
					$i = 0;
					foreach($_SESSION['filtre']['type'] as $value)
					{
						if($i != 0)
							$type .= ' OR ';
						else
							$type = '(';
						$type .= 'typeInter="' . $value . '"';
					}
					$type .= ')';
				}
				
				if(empty($done) && empty($echelle) && empty($priority) && empty($type))
					$condition = ' WHERE c.u_id = ' . $_SESSION['user'];
				else
				{
					$condition = ' WHERE ' . $done;
					if(!empty($done) && !empty($echelle))
						$condition .= ' AND ' . $echelle;
					elseif(empty($done) && !empty($echelle))
						$condition .= $echelle;
					
					if(!empty($priority) && $condition != ' WHERE ')
						$condition .= ' AND ' . $priority;
					elseif(!empty($priority))
						$condition .= $priority;
					
					if(!empty($type) && $condition != ' WHERE ')
						$condition .= ' AND ' . $type;
					elseif(!empty($type))
						$condition .= $type;
/* 					if((!empty($echelle) && !empty($priority)) || (empty($echelle) && !empty($done) && !empty($priority)))
						$condition .= ' AND ' . $priority;
					elseif(empty($done) && empty($echelle) && !empty($priority))
						$condition .= $priority;
*/					$condition .= ' AND c.u_id = ' . $_SESSION['user'];
 				}

/* 				$sql = $db->prepare('SELECT i.i_id, i.c_id, i.done, i.priority, i.date, i.heure, i.typeInter, i.noteInter, c.fullName FROM interventions i
				LEFT JOIN contact c
					ON i.c_id = c.c_id' . $condition . 	
				' ORDER BY date, heure DESC');
				$sql->execute();

				while($result = $sql->fetch(PDO::FETCH_ASSOC))
				{
					if($result['done'] == 1)
						$class = 'interFinie';
					elseif(strtotime($result['date']) <= strtotime('today'))
						$class = 'interTresProche';
					elseif(strtotime($result['date']) <= strtotime('+ 1 day') && $result['date'] > date('Y-m-d'))
						$class = 'interProche';
					else
						$class = '';
					
					switch($result['priority'])
					{
						case 0:
							$urgence = 'bUrgence';
							break;
						case 1:
						default:
							$urgence = '';
							break;
						case 2:
							$urgence = 'hUrgence';
							break;
						case 3:
							$urgence = 'vhUrgence';
							break;
					}
					?>
				   <tr id="<?php echo $result['i_id']; ?>" class="intervention cursor <?php echo $class; ?>" data-id="<?php echo $result['c_id']; ?>">
						<td class="nom"><?php echo $result['fullName']; ?></td>
						<td class="typeInter"><?php echo $typeInter[$result['typeInter']]; ?></td>
						<td class="dateInter"><?php echo dateFr($result['date']); ?></td>            
						<td class="heureInter"><?php echo(isset($result['heure']) && $result['heure'] != '00:00:00') ? heureminute($result['heure']) : ''; ?></td> 
						<td class="<?php echo $urgence; ?>"></td>
				   </tr><?php
				}
 */			?>
			</table>		
		</div>
		<div class="notes">
			<ul id="tabs">
				<li id="choixCommentaires" class="choix cursor">Commentaires</li>
				<li id="choixResume" class="choix cursor">Résumé</li>
			</ul>
			<div class="right-panel"><?php
				switch($_SESSION['champInter'])
				{
					case 'resume':
						echo 'Liste des interventions : <br>';
						break;
						
					case 'commentaires':
					default:
						echo 'Commentaires : <br>';
						break;
				}
			?></div>
		</div>
	</section>
</div>

