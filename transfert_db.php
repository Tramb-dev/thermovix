<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
require_once('src/config.php');
include('src/globals.php');
mb_internal_encoding('UTF-8');

$db = db_connect();

$contact = $db->query('SELECT g_id, fullname, prefixe, prenom, additional, nom, suffixe, notes, last_updated, rencontre, dateCommande, dateReception, metier, dispos, divers, distance, duree, famille, dateMES, dateCours, filleuls, a_table, envie_dessert, france_gourmande, saveurs_enfance, mille_pates, soupes_4_saisons, top_chrono, cuisinez_chefs, equilibre_saveurs, petits_plats, 50_recettes_famille, jours_fetes, recettes_4_saisons, desserts_gourmands, vegetale, cuisine_quotidien, soupes, recettes_rapides, cuisine_vapeur, 100_facons, gouts_monde, espace_recette, sauces, boissons, aperitifs, chocolat, pains, confitures, c_cuisine_au_quotidien, c_soupes, c_cuisine_rapide, c_tout_choco, c_pains, cookkey FROM contact')->fetchAll(PDO::FETCH_ASSOC);
$c_sql = 'INSERT INTO contact (c_id, u_id, g_id, fullname, prefixe, prenom, additional, nom, suffixe, notes, last_updated, rencontre, dateCommande, dateReception, metier, dispos, divers, distance, duree, famille, dateMES, dateCours, filleuls, a_table, envie_dessert, france_gourmande, saveurs_enfance, mille_pates, soupes_4_saisons, top_chrono, cuisinez_chefs, equilibre_saveurs, petits_plats, 50_recettes_famille, jours_fetes, recettes_4_saisons, desserts_gourmands, vegetale, cuisine_quotidien, soupes, recettes_rapides, cuisine_vapeur, 100_facons, gouts_monde, espace_recette, sauces, boissons, aperitifs, chocolat, pains, confitures, c_cuisine_au_quotidien, c_soupes, c_cuisine_rapide, c_tout_choco, c_pains, cookkey) VALUES ';

$adresse = $db->query('SELECT g_id, label, formatted, rue, ville, postcode, is_label, lat, lng FROM adresses')->fetchAll(PDO::FETCH_ASSOC);
$a_sql = 'INSERT INTO adresses (c_id, label, formatted, rue, ville, postcode, is_label, lat, lng) VALUES ';

$email = $db->query('SELECT g_id, label, email, principal, is_label FROM emails')->fetchAll(PDO::FETCH_ASSOC);
$e_sql = 'INSERT INTO emails (c_id, label, email, principal, is_label) VALUES ';

$tel = $db->query('SELECT g_id, label, tel, principal, is_label FROM tel')->fetchAll(PDO::FETCH_ASSOC);
$t_sql = 'INSERT INTO tel (c_id, label, tel, principal, is_label) VALUES ';

$groupe = $db->query('SELECT gr_id, nom, last_updated FROM groupes')->fetchAll(PDO::FETCH_ASSOC);
$g_sql = 'INSERT INTO groupes (u_id, gr_id, nom, last_updated) VALUES ';

$link = $db->query('SELECT gr_id, g_id FROM link_groupes_contacts')->fetchAll(PDO::FETCH_ASSOC);
$l_sql = 'INSERT INTO link_groupes_contacts (gr_id, c_id, u_id) VALUES ';

$inter = $db->query('SELECT g_id, done, priority, date, heure, typeInter, noteInter FROM interventions')->fetchAll(PDO::FETCH_ASSOC);
$i_sql = 'INSERT INTO interventions (c_id, done, priority, date, heure, typeInter, noteInter) VALUES ';


$c_cpt = $a_cpt = $e_cpt = $t_cpt = $g_cpt = $l_cpt = $i_cpt = 0;

foreach($groupe as $value)
{
	if($g_cpt != 0)
		$g_sql .= ', (';
	else
		$g_sql .= '(';
	
	$g_sql .= '2, "' . $value['gr_id'] . '", "' . $value['nom'] . '", "' . $value['last_updated'] . '")';
	$g_cpt++;
}

foreach($contact as $c_id => $array)
{
	$c_cpt2 = 0;
	if($c_cpt != 0)
		$c_sql .= ', (';
	else
		$c_sql .= '(';
	
	$c_sql .= ($c_id + 1) . ', 2, ';
	foreach($array as $value)
	{
		if($c_cpt2 !=0)
			$c_sql .= ', ';
		$c_sql .= '"' . slash($value) . '"';
		$c_cpt2++;
	}
	foreach($adresse as $value)
	{
		if($value['g_id'] == $array['g_id'])
		{
			if($a_cpt != 0)
				$a_sql .= ', (';
			else
				$a_sql .= '(';
			
			$a_sql .= ($c_id + 1) . ', "' . $value['label'] . '", "' . $value['formatted'] . '", "' . $value['rue'] . '", "' . $value['ville'] . '", "' . $value['postcode'] . '", "' . $value['is_label'] . '", "' . $value['lat'] . '", "' . $value['lng'] . '")';
			$a_cpt++;
		}
	}
	foreach($email as $value)
	{
		if($value['g_id'] == $array['g_id'])
		{
			if($e_cpt != 0)
				$e_sql .= ', (';
			else
				$e_sql .= '(';
			
			$e_sql .= ($c_id + 1) . ', "' . $value['label'] . '", "' . $value['email'] . '", "' . $value['principal'] . '", "' . $value['is_label'] . '")';
			$e_cpt++;
		}
	}
	foreach($tel as $value)
	{
		if($value['g_id'] == $array['g_id'])
		{
			if($t_cpt != 0)
				$t_sql .= ', (';
			else
				$t_sql .= '(';
			
			$t_sql .= ($c_id + 1) . ', "' . $value['label'] . '", "' . $value['tel'] . '", "' . $value['principal'] . '", "' . $value['is_label'] . '")';
			$t_cpt++;
		}
	}
	foreach($inter as $value)
	{
		if($value['g_id'] == $array['g_id'])
		{
			if($i_cpt != 0)
				$i_sql .= ', (';
			else
				$i_sql .= '(';
			
			$i_sql .= ($c_id + 1) . ', "' . $value['done'] . '", "' . $value['priority'] . '", "' . $value['date'] . '", "' . $value['heure'] . '", "' . $value['typeInter'] . '", "' . slash($value['noteInter']) . '")';
			$i_cpt++;
		}
	}
	foreach($link as $value)
	{
		if($value['g_id'] == $array['g_id'])
		{
			if($l_cpt != 0)
				$l_sql .= ', (';
			else
				$l_sql .= '(';
			
			$l_sql .=  '"' . $value['gr_id'] . '", ' . ($c_id + 1) . ', 2)';
			$l_cpt++;
		}
	}
	
	$c_sql .= ')';
	$c_cpt++;
}
echo $c_sql . ';<br/>';
echo $a_sql . ';<br/>';
echo $e_sql . ';<br/>';
echo $t_sql . ';<br/>';
echo $g_sql . ';<br/>';
echo $l_sql . ';<br/>';
echo $i_sql . ';<br/>';

$db = null;

function slash($chaine)
{
	$format = str_replace('"', '\"', $chaine);
	return $format;
}
?>
</body>
</html>