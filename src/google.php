<?php
/**************************************************************/
/* google.php
/* Permet de récupérer les contacts google via l'API
/**************************************************************/
require_once $_SERVER["DOCUMENT_ROOT"] . '/test/lib/google-api-php-client-2.2.2/vendor/autoload.php';


/************/
/* Constructeur de l'interfaçage Google */
/*************/
function google()
{
	$client = google_connect();
	
 	if(isset($_REQUEST['logout']))
	{
		$client->revokeToken();
		session_destroy();
		$db = null;
	}
	
/*  	if(!$client->getAccessToken())
	{
		$url = $client->createAuthUrl();			
		echo '<a class="login" href="' . $url . '">Connexion à Google</a>';
		echo '<a class="logout" href="?logout">Déconnexion</a>';
	}
	else
	{
		echo '<a class="logout" href="?logout">Déconnexion</a>';
	}
 */
	echo '<a class="logout" href="?logout">Déconnexion</a>';
}

/************/
/* Se connecte à Google et récupère/rafraichit le token */
/*************/
function google_connect()
{
	$client = new Google_Client();

	$client->setClientID(G_CLIENT_ID);
	$client->setClientSecret(G_CLIENT_SECRET);
	$client->setRedirectUri(ROOT);

	$client->addScope('profile');
	$client->addScope('https://www.googleapis.com/auth/contacts');

	if (isset($_GET['oauth'])) {
		// Start auth flow by redirecting to Google's auth server
		$auth_url = $client->createAuthUrl();
		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
	} else if (isset($_GET['code'])) {
		// Receive auth code from Google, exchange it for an access token, and
		// redirect to your base URL
		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();
		$redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/test/';
		header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	} else if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		// You have an access token; use it to call the People API
		$client->setAccessToken($_SESSION['access_token']);
		$people_service = new Google_Service_PeopleService($client);
		// TODO: Use service object to request People data
	} else {
		$redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/test/?oauth';
		header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}
}

/************/
/* Importe la liste des contacts */
/*************/
function listContacts()
{
	//google_connect();
	$headers = array(
		'Authorization: Bearer ' . $_SESSION['access_token']['access_token'],
		'Accept: application/json'
	);
	
	$curl = curl_init('https://people.googleapis.com/v1/people/me/connections?personFields=names%2CemailAddresses');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	$contacts = curl_exec($curl);
	//curl_close($curl);
	//$_SESSION['contacts'] = $contacts;
	return true;
	/* 	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/contacts/' . $_SESSION['user_email'] . '/full/?alt=json&max-results=9999');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$contacts = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	$_SESSION['contacts'] = json_decode($contacts, true);
	$_SESSION['base'] = 'Google';

	return $httpCode;
 */}

/************/
/* Met à jour un seul contact */
/*************/
function updateContact($id, $arr)
{
	$client = google_connect();
	$contact = json_encode($arr, true);
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: ' . strlen($contact),
			'Content-type: application/json',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/contacts/' . $_SESSION['user_email'] . '/full/' . $id);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $contact);
	$update = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
		
	return $httpCode;
}

/************/
/* Supprime un seul contact */
/*************/
function deleteContact($id)
{
	$client = google_connect();
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'If-match: *',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/contacts/' . $_SESSION['user_email'] . '/full/' . $id);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$delete = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	
	return $httpCode;
}

/************/
/* Récupère un seul contact */
/*************/
function getContact($id)
{
	$client = google_connect();
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/contacts/' . $_SESSION['user_email'] . '/full/' . $id . '/?alt=json');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$contact = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	
	if($httpCode == 200)
		return json_decode($contact, true);
	else
	{
		$_SESSION['message'] = 'Erreur lors de la réception du contact demandé : erreur ' . $httpCode;
		return false;
	}
}

/************/
/* Crée de nouveaux contacts */
/*************/
function insertContacts($contacts)
{
	$client = google_connect();
	$contacts = json_encode($contacts);
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: ' . strlen($contacts),
			'Content-type: application/json',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/contacts/' . $_SESSION['user_email'] . '/full/');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $contacts);
	$insert = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
		
	$retour['code'] = $httpCode;
	$retour['new'] = $insert;
		
	return $retour;
}

/************/
/* Importe la liste des groupes */
/*************/
function listGroups()
{
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'Content-type: application/atom+xml',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/full/?alt=json&max-results=9999');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$groups = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	$_SESSION['groups'] = json_decode($groups, true);

	return $httpCode;
}

/************/
/* Ajoute un groupe */
/*************/
function newGroup($name)
{
	$client = google_connect();
	$name = json_encode($name);

	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: ' . strlen($name),
			'Content-type: application/json',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/full/');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $name);
	$insert = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
		
	$retour['code'] = $httpCode;
	$retour['new'] = $insert;
		
	return $retour;
}

/************/
/* Met à jour un groupe */
/*************/
function updateGroup($gr_id, $arr)
{
	$client = google_connect();
	$group = json_encode($arr, true);

	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: ' . strlen($group),
			'Content-type: application/json',
			'If-Match: *',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/full/' . $gr_id);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $group);
	$update = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
		
	return $httpCode;
}

/************/
/* Récupère les infos d'un seul groupe */
/*************/
function getGroup($gr_id)
{
	$client = google_connect();
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/full/' . $gr_id . '/?alt=json');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$groupe = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	
	if($httpCode == 200)
		return json_decode($groupe, true);
	else
	{
		$_SESSION['message'] = 'Erreur lors de la réception du groupe demandé : erreur ' . $httpCode;
		return false;
	}
}

/************/
/* Supprime un groupe */
/*************/
function deleteGroup($gr_id)
{
	$client = google_connect();
	$headers = array(
			'Host: www.google.com',
			'Gdata-Version: 3.0',
			'Content-length: 0',
			'If-match: *',
			'Authorization: Bearer ' . $_SESSION['access_token']
	);
	
	$curl = curl_init('https://www.google.com/m8/feeds/groups/' . $_SESSION['user_email'] . '/full/' . $gr_id);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$delete = curl_exec($curl);
	$httpCode = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	curl_close($curl);
	
	return $httpCode;
}

/* Renvoie l'id google du contact ou du groupe */
function search_id($tableau = array())
{
	$id = explode('/', $tableau['id']['$t']);
	$tableau['id']['$t'] = array_pop($id);
		
	return $tableau;
}

function search_id_group_in_contact($tableau = array())
{
	$id = explode('/', $tableau['href']);
	$tableau['href'] = array_pop($id);
		
	return $tableau;
}

/************/
/* Renvoi la latitude et longitude de l'adresse demandée */
/* $adresse : adresse à analyser */
/*************/
function geoCode($adresse)
{
	if($adresse != '')
	{
		$geocoder = "https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=" . API_KEY . "&language=fr&region=fr";
		$url_address = urlencode(htmlentities($adresse));
		$query = sprintf($geocoder,$url_address);
		$results = file_get_contents($query);
	 
		$latlng = json_decode($results, true);
	}
	else
	{
		$latlng['results'] = 'Adresse inexistante';
	}
	return $latlng;
}

?>