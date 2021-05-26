<span class="message">
<?php
	if(!empty($_SESSION['message']))
	{
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}
?>
</span>