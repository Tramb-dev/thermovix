<?php
/**************************************************************/
/* contacts.php
/* Affiche les contacts sous forme de tableau dans la section main
/**************************************************************/
?>
<table id="tableContacts">
	<thead>
    	<th></th>
		<th>Nom</td>
		<th>Email</td>
		<th>Téléphone</td>
		<th>Ville</th>
		<th>Groupes</th>
	</thead>
<?php
$db = db_connect();
$i = 0;

if(empty($_SESSION['tri']) || ($_SESSION['tri']['triDate'] == 'none' && empty($_SESSION['tri']['groupes'])))
	$where = 'WHERE c.u_id = ' . $_SESSION['user'];
	// $where = ' WHERE NOT EXISTS (
						// SELECT * FROM link_groupes_contacts l LEFT JOIN contact c ON l.c_id = c.c_id WHERE c.u_id = ' . $_SESSION['user'] . '
					// )';
else
{
	$where = ' WHERE c.u_id = ' . $_SESSION['user'] . ' AND (';
	foreach($_SESSION['tri']['groupes'] as $value)
	{
		if($i != 0)
			$where .= ' OR ';
		$where .= 'l.gr_id="' . $value . '"';
		$i++;
	}
	$where .= ')';
	if($_SESSION['tri']['triDate'] == 'MES')
		$where .= ' AND dateMES IS NULL';
	elseif($_SESSION['tri']['triDate'] == 'cours')
		$where .= ' AND dateCours IS NULL';
}

$sql = 'SELECT DISTINCT c.c_id, c.fullName, e.email, t.tel, a.ville, a.postcode, a.lat, a.lng, l.gr_id FROM contact c
						LEFT JOIN emails e ON c.c_id = e.c_id
						LEFT JOIN tel t ON c.c_id = t.c_id
						LEFT JOIN adresses a ON c.c_id = a.c_id
						LEFT JOIN link_groupes_contacts l ON c.c_id = l.c_id
						' . $where . '
						GROUP BY c.c_id
						ORDER BY c.fullName';
$requete = $db->query($sql);

while($liste = $requete->fetch(PDO::FETCH_ASSOC))
{
	echo '<tr id="' . $liste['c_id'] . '" class="contact cursor">';
	echo '<td class="located">';
	if($liste['lat'] != null && $liste['lng'] != null)
		echo '<img src="img/home.png" width="17px" height="17px" />';
	echo '</td>';
	echo '<td class="nom">' . $liste['fullName'] . '</td>';
	echo '<td class="email"><a href="mailto:' . $liste['email'] . '">' . $liste['email'] . '</a></td>';
	echo '<td class="tel">' . $liste['tel'] . '</td>';
	echo '<td class="ville">' . $liste['ville'] . ' ' . $liste['postcode'] . '</td>';
	echo '<td class="contactGroupes">';
	$sql = $db->query('SELECT DISTINCT g.nom FROM groupes g
									LEFT JOIN link_groupes_contacts l ON g.gr_id = l.gr_id
									WHERE l.c_id="' . $liste['c_id'] . '" AND g.u_id = ' . $_SESSION['user'] . '
									ORDER BY g.nom');
	while($groupe = $sql->fetch(PDO::FETCH_ASSOC))
	{
		echo '<span>' . $groupe['nom'] . '</span>';
	}
	echo '</td></tr>';
}

$db = null;
?>
</table>