<?php
/**************************************************************/
/* header.php
/* Contient les onglets ainsi que la synchronisation Google
/**************************************************************/

if(!isset($_SESSION['page']) || empty($_SESSION['page']))
	$_SESSION['page'] = 'contact';
	
if(isset($_GET['page']) && !empty($_GET['page']))
	$_SESSION['page'] = $_GET['page'];

?>

<a href="?page=contact">Contacts</a>
<a href="?page=interventions">Interventions</a>

<div class="right">
    <?php google(); ?>
</div>

<div class="clear"></div>
