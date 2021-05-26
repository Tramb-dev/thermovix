<?php
/**************************************************************/
/* autocomplete.php
/* Affiche les contacts du champ recherche
/**************************************************************/

session_start();
require_once('../src/globals.php');

$term = $_GET['term'];
$liste = array();

$db = db_connect();
$requete = $db->prepare('SELECT fullName, c_id FROM contact WHERE u_id = ' . $_SESSION['user'] . ' AND fullName LIKE :term');
$requete->execute(array('term'	=>	'%' . $term . '%'));

while($result = $requete->fetch(PDO::FETCH_ASSOC))
{
	$liste[] = array(
		'value'	=>	$result['fullName'],
		'label'	=>	$result['fullName'],
		'desc'	=>	$result['c_id']
	);
}
$db = null;

echo json_encode($liste);

?>