<?php

// code common to all GE sites

// Get last batch timestamp
$last_batch_timestamp = 0;
$query = "SELECT batch_date FROM ge_batch.batch_file"
	." WHERE site_code = '$promo->site_code'"
	." ORDER BY batch_date DESC";
$result_date = $sql->Query (SQL_DB_BATCH, $query);

if ($sql->Row_Count($result_date) > 0)
{
	$row = $sql->Fetch_Array_Row($result_date);
	$last_batch_timestamp = $row['batch_date'];
}

$query = "SELECT"
	."  person.firstname as first_name"
	.", person.lastname as last_name"
	.", person.middlename as middle_name"
	.", address.phone, address1, address2, city, state, zip"
	.", orders.order_id as order_id"
	.", orders.creation_stamp as date_of_sale"
	.", promo_id"
	.", cc.b as card_type"
	.", cc.c as card_num"
	.", cc.d as card_exp"
	." FROM orders"
	." LEFT JOIN person ON orders.person_id = person.person_id "
	." LEFT JOIN address ON orders.bill_addr = address.address_id "
	." LEFT JOIN cc ON orders.order_id = cc.a "
	." WHERE orders.status = 'APPROVED' "
	." AND promo_id != '99999' "
	." AND orders.creation_stamp > '$last_batch_timestamp'"
	." AND NOT (first_name == 'joe' && last_name == 'cool')"
	." AND NOT (first_name == 'test' || last_name == 'test')"
	;
	//." AND orders.creation_stamp < NOW()";

$result = $sql->Query (SITE_DB, $query);
$result_count = $sql->Row_Count ($result);

if ($result_count > 0)
{
	$bl[] = new stdClass();
	$bl_count = 0;
	while ($row = $sql->Fetch_Array_Row($result))
	{
		$bl[$bl_count]->order_id = $row['order_id'];
		$bl[$bl_count]->promo_id = $row['promo_id'];
		$bl[$bl_count]->first_name = $row['first_name'];
		$bl[$bl_count]->last_name = $row['last_name'];
		$bl[$bl_count]->middle_name = $row['middle_name'];
		$bl[$bl_count]->phone = $row['phone'];
		$bl[$bl_count]->address1 = $row['address1'];
		$bl[$bl_count]->address2 = $row['address2'];
		$bl[$bl_count]->city = $row['city'];
		$bl[$bl_count]->state = $row['state'];
		$bl[$bl_count]->zip = $row['zip'];
		$bl[$bl_count]->date_of_sale = $row['date_of_sale'];
		$bl[$bl_count]->card_num = $row['card_num'];
		$bl[$bl_count]->card_type = $row['card_type'];
		$bl[$bl_count]->card_exp = $row['card_exp'];

		$bl_count++;
	}
#	print "\$bl($bl_count): ".print_r($bl,true)."\n";

	$new_batch = new GE_Batch;
	$new_batch->Make_Batch ($bl, $promo, $db, $ftp, $show_success);
}
else
{
	echo 'No records to batch.';
	$confirm_message = 'No sales records to batch.';
	mail ($ftp->confirm_email
		,'Nightly Batch-' . date ('m/d/Y') . ': ' . SITE_NAME
		,$confirm_message
		,"From: $ftp->confirm_reply_email\n"
		."Cc: $ftp->confirm_email_cc\n");
}
?>
