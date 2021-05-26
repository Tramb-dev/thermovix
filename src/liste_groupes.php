<?php
/**************************************************************/
/* liste.php
/* Affiche les groupes sous forme de liste dans le nav pour filtrer les contacts
/**************************************************************/

//$db = db_connect();

/* if(isset($_POST['tri']) && $_POST['tri'])
{
	$_SESSION['tri'] = array();
	foreach($_POST as $key => $value)
	{
		switch($key)
		{
			case 'tri':
				break;
				
			default:
				$_SESSION['tri']['groupes'][] = $key;
		}
	}
	$db->exec('UPDATE users SET gr_contacts = \'' . serialize($_SESSION['tri']) . '\' WHERE u_id = ' . $_SESSION['user']);
}
elseif(!isset($_POST['tri']) && empty($_SESSION['tri']))
{
	$sql = $db->query('SELECT gr_id FROM groupes WHERE u_id = ' . $_SESSION['user']);
	while($groupes = $sql->fetch(PDO::FETCH_ASSOC))
	{
		$_SESSION['tri']['groupes'][] = $groupes['gr_id'];
	}
}
 */?>
<div id="gestion"><div class="titre">Gestion</div>
	<div id="addContact" class="cursor gestion" href="src/contact.php?addContact=1">Nouveau contact</div>
	<div id="modifContact" class="cursor gestion">Modifier</div>
	<div id="newIntervention" class="cursor gestion">Nouvelle intervention</div>
</div>
<div>
	<div class="titre">Filtres</div>
	<form id="listeGroupe" action="index.php" method="post">
		<fieldset>
			<legend>Rechercher</legend>
			<input type="text" id="search" size="16"></input><br>
		</fieldset>
		<fieldset>
			<legend>Groupes</legend>
			<?php
/* 			$cpt = 0;
			$sql = $db->query('SELECT gr_id, nom FROM groupes WHERE u_id = ' . $_SESSION['user'] . ' ORDER BY nom');
			while($groupe = $sql->fetch(PDO::FETCH_ASSOC))
			{
				$requete = $db->query('SELECT COUNT(*) FROM link_groupes_contacts WHERE u_id = ' . $_SESSION['user'] . ' AND gr_id="' . $groupe['gr_id'] . '"');
				$cpt = $requete->fetch(PDO::FETCH_ASSOC);

				if(!empty($_SESSION['tri']['groupes']) && in_array($groupe['gr_id'], $_SESSION['tri']['groupes']))
					$checked = 'checked';
				else
					$checked = '';
					
				echo '<input type="checkbox" name="' . $groupe['gr_id'] . '" class="groupe cursor" ' . $checked . '><span>' . $groupe['nom'] . '</input><span class="cptGroupe"> (' . $cpt['COUNT(*)'] . ') </span></span><br>';
			}
 */			?>
			<div><span id="triAll" class="cursor">Tous</span> | <span id="triNone" class="cursor">Aucun</span></div>
			<input type="hidden" name="tri" value="true"/>
		</fieldset>
		<input type="submit" value="Trier"/>
	</form>
</div>
<?php
//$db = null;
?>