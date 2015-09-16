<?php
	require_once 'model/model.php';
	require_once 'lib/lib.php';
	require_once 'control/site_controller.php';
	
	require_once 'lib/log4php/Logger.php';		//TODO not nice that I have to go to log4php directly
	Logger::configure('conf/log4php.xml');
	$logger= Logger::getLogger(basename($_SERVER['PHP_SELF'], '.php'));
	
	SiteController::mark();
	
	$logger->info('hello from log4php');
	
	if (array_key_exists('action', $_REQUEST)) {
		if ($_REQUEST['action'] == "New") {
			redirect('edit_parent.php');
		}
	}
	
	ModelFactory::initialise();
	
	$parents= ModelFactory::getActiveParents();
	
	function print_children($children) {
		$result= "";
		
		foreach($children as $happyChild) {
			$result= $result . "<div>" . $happyChild->nickname . "</div>";
		}
		
		print $result;
	}
	
	function print_mailto_link($mailAddresses, $salutation)
	{
		$result= '<a href="mailto:' . $mailAddresses . '?body=' . htmlspecialchars($salutation) . '">' . $mailAddresses . "</a>";
		
		print $result;
	}
	
	function print_string($s)
	{
		if (isset($s) && strlen($s) > 0) {
			print $s;
		} else {
			print "&nbsp;";
		}
	}
	
	function print_edit_link($id)
	{
		global $logger;
		$logger->debug("id is $id");
		
		$result= '<a href="edit_parent.php?id=' . $id . '"><img src="img/b_edit.png"></a>';
		
		print $result;
	}
	
?>	
<html>
  <head>
    <title>Happy Nest Parents</title>
    <link rel="stylesheet" type="text/css" href="hnstyle.css">
  </head>
  <body>
  	<form method="post">
	  	<table id="parents">
	  		<thead>
	  			<tr>
		  			<th class="child">Child</th>
		  			<th class="name">Name</th>
		  			<th class="email">Email</th>
		  			<th class="phone">Mother</th>
		  			<th class="phone">Father</th>
		  			<th class="phone">Other</th>
		  			<th>&nbsp;</th>
		  		</tr>
	  		</thead>
	  		<tbody>
	  		<?php
		foreach ($parents as $parent) { ?>
	  			<tr>
	  				<td class="child"><?php print_children($parent->children) ?></td>
	  				<td class="name"><?php print $parent->name ?></td>
	  				<td class="email"><?php print_mailto_link($parent->email, $parent->salutation) ?></td>
	  				<td class="phone"><?php print_string($parent->phone_1)?></td>
	  				<td class="phone"><?php print_string($parent->phone_2)?></td>
	  				<td class="phone"><?php print_string($parent->phone_3)?></td>
	  				<td><?php print_edit_link($parent->id)?></td>
	  			</tr>
		<?php } ?>
	  		</tbody>
	  	</table>
	  	<div>
					<input type="submit" name="action" value="New">
	  	</div>
	  </form>
  </body>
</html>