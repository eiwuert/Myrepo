<?php
if (lum_requirePermission('Requests\View')) :
	$num_nights = 0;
	$obj = null;
	$render_form = true;
	$new = 1;
	$quote = null;
	
	if (isset($_GET['request_id']))
	{
		unset($_SESSION['request_reply_sent']);
		$new = 0;
		$obj = lum_call('Requests', 'get', array('request_id'=>$_GET['request_id']));
		
		if (!$obj)
		{
			echo "<p>The request could not be found in the database.</p>";
			$render_form = false;
		}
		
		$sql = "select quote_id from lum_quotes where request_id = ?";
		$value_array = array($obj['request_id']);
		global $lumRegistry;
		$quote = $lumRegistry->db->getRow($sql, $value_array, true);
		
	}

	// we are sending a reply!
	if (isset($_POST['request_id']))
	{
		$new = 0;
		$obj = lum_call('Requests', 'get', array('request_id'=>$_POST['request_id']));
		if (!$obj)
		{
			echo "<p>The request could not be found in the database.</p>";
			$render_form = false;
		}
		else
		{
			$reply = stripslashes($_POST['reply']);
			$reply .= $_POST['agent_signature'];
			$reply .= '<hr/>';
			$reply .= stripslashes($obj['comments']);
			
			lum_setString('{BECAUSE}', '[EMAIL_BECAUSE_CONTACT]');
			lum_setString('{MESSAGE}', $reply);
      
			ob_start();
			include(TEMPLATES_PATH.'email_template.inc.php');
			$email = ob_get_clean();
				
			$email = lum_replaceStrings($email);
			$email = lum_replaceStrings($email);
			
			$bcc = unserialize(QUOTE_APPROVAL_EMAILS);
			
			if (!isset($_SESSION['request_reply_sent']))
			{
				$sent = lum_sendEmail($user->email, $user->first_name .' '. $user->last_name, $obj['email'], $obj['name'], $email, $_POST['reply_subject'], $bcc, true);
			}
				
			lum_call('Users', 'updateSignature', array('signature'=>$_POST['agent_signature'], 'user_id'=>$user->user_id));
			lum_call('Requests', 'addReply', array('request_id'=>$_POST['request_id'], 'reply'=>$reply, 'reply_subject'=>$_POST['reply_subject']));
			$_SESSION['request_reply_sent'] = true;
			echo '<p style="color: #ff0000; font-weight: bold;">WARNING: Refreshing the page can resend your reply.</p>';
			$obj = lum_call('Requests', 'get', array('request_id'=>$_POST['request_id']));
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
				<?php if ($obj['property_id']) : ?>
				<tr>
					<td>Property ID</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['property_id'])); ?></td>
				</tr>				
				<tr>
					<td>Property</td>
					<td><?php
						$row = lum_call('Properties', 'get', array('property_id'=>$obj['property_id'], 'lang_code'=>lum_getCurrentLanguage()));
						echo $row['title'];
					?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td>Name</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['name'])); ?></td>
				</tr>
				<tr>
					<td>Email</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['email'])); ?></td>
				</tr>
				<tr>
					<td>Phone</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['phone'])); ?></td>
				</tr>
				<?php if ($obj['property_id']) : ?>
				<tr>
					<td>Arrival Date</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['arrival_date'])); ?></td>
				</tr>
				<tr>
					<td>Departure Date</td>
					<td><?php echo (!$obj ? '' : stripslashes($obj['departure_date'])); ?></td>
				</tr>				
				<tr>
					<td>Nights</td>
					<td><?php $num_nights = intval(lum_subtractDates($obj['arrival_date'], $obj['departure_date']) / ONE_DAY);
					echo $num_nights?></td>
				</tr>
				<?php endif; ?>
				<?php
					if ($obj['other_data'])
					{
						$other = unserialize(base64_decode($obj['other_data']));
						foreach ($other as $label=>$data)
						{
							?>
				<tr>
					<td><?=$label?></td>
					<td><?=$data?></td>
				</tr>
							<?
						}
					}
				?>
				<tr>
					<td>Comments</td>
					<td><?=$obj['comments']?></td>
				</tr>				
			</table>
			
		</fieldset>
		<input type="button" value="View Requests" onclick="window.location.href = TOOLS_PATH+'/Requests/list';"/>
		
		<?php if ($obj['property_id'] && !$quote) : ?>
		<input type="button" value="Send a Quote" onclick="window.location.href = TOOLS_PATH+'/Quotes/give?request_id=<?=$obj['request_id']?>&property_id=<?=$obj['property_id']?>&arrival_date=<?=$obj['arrival_date']?>&num_nights=<?=$num_nights?>';"/>
		<?php endif; ?>
	  </form>
	<?php if (!$obj['replied']) : ?>
	<br/>
	<fieldset>
		<legend>Send a Reply</legend>
		<script type="text/javascript" src="../../../cms-admin/tiny_mce/jquery.tinymce.js"></script>
		<form id="send_reply" method="POST" action="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Requests/edit">
			<input type="hidden" name="request_id" value="<?=$_REQUEST['request_id']?>"/>
			<table>
				<tr>
					<td>Subject</td>
					<td><input type="text" class="required" name="reply_subject" value=""/></td>
				</tr>				
				<tr>
					<td>Reply</td>
					<td><textarea name="reply" class="tinymce"  style="width: 300px; height: 75px;"></textarea></td>
				</tr>
				<tr>
					<td>Agent Signature</td>
					<td><textarea name="agent_signature" class="tinymce" style="width: 300px; height: 75px;"><?php echo stripslashes($user->signature); ?></textarea></td>
				</tr>
			</table>				
			<input type="button" value="Send Reply" class="send_reply"/>
		</form>	
	</fieldset>
	<?php endif; ?>
	<?php if ($obj['replied']) : ?>
	<br/>
	<fieldset>
		<legend>Reply Sent <?=$obj['replied_on']?></legend>
		<p><b>Subject: <?=$obj['reply_subject']?></b><br/><br/>
		<?=$obj['reply'];?>
		</p>
	</fieldset>
	<?php endif; ?>	
</div>
<?php
	endif;
endif;
?>
