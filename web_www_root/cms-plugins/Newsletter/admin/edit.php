<?php
if (lum_requirePermission('Requests\Edit')) :

	$obj = null;
	$render_form = true;
	$new = 1;

	if (isset($_GET['request_id']))
	{
		$new = 0;
		$obj = lum_call('Newsletter', 'get', array('request_id'=>$_GET['request_id']));
		
		if (!$obj)
		{
			echo "<p>The request could not be found in the database.</p>";
			$render_form = false;
		}
	}

	if ($render_form) :
?>
<div id="plugin-header">
	<h1><?php echo ($new ? 'Add a Request' : 'View Request');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="" enctype="multipart/form-data">
		<fieldset>
			<legend>Requests Details</legend>
			<table>
				<tr>
					<td>Request Date</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['request_date'])); ?></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['email'])); ?></td>
				</tr>
			</table>
			
		</fieldset>
		<input type="button" value="View Requests" onclick="window.location.href = TOOLS_PATH+'/Newsletter/list';"/>

	  </form>
</div>
<?php
	endif;
endif;
?>
