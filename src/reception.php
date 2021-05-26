<?php
/**************************************************************/
/* reception.php
/* Affiche les contacts qui ont commandé un TM mai pa encore reçu
/**************************************************************/

?>

<table id="tableReception">
    <tr class="entete">
    	<thead>
            <th>Nom</td>
            <th>Email</td>
            <th>Téléphone</td>
            <th>Ville</th>
			<th>Date commande</th>
        </thead>
    </tr>
<?php
$db = db_connect();
$i = 0;

$requete = $db->query('SELECT c.c_id, c.fullName, c.dateCommande, e.email, t.tel, a.ville, a.postcode FROM contact c
									LEFT JOIN emails e ON c.c_id = e.c_id
									LEFT JOIN tel t ON c.c_id = t.c_id
									LEFT JOIN adresses a ON c.c_id = a.c_id
									WHERE c.dateCommande IS NOT NULL AND c.dateReception IS NULL AND c.u_id = ' . $_SESSION['user'] . ' 
									GROUP BY c.c_id
									ORDER BY c.dateCommande');

while($liste = $requete->fetch(PDO::FETCH_ASSOC))
{
	echo '<tr id="' . $liste['c_id'] . '" class="contact cursor">';
	echo '<td class="nom">' . $liste['fullName'] . '</td>';
	echo '<td class="email">' . $liste['email'] . '</td>';
	echo '<td class="tel">' . $liste['tel'] . '</td>';
	echo '<td class="ville">' . $liste['ville'] . ' ' . $liste['postcode'] . '</td>';
	echo '<td class="date">' . dateFr($liste['dateCommande']) . '</td></tr>';
}

$db = null;
?>
</table>