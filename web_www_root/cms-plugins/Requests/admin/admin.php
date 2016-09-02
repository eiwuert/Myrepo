<?php if (lum_requirePermission('Requests\View', false)) : ?>
<?php
	$num = lum_call('Requests', 'getNewCount');
	$iq_num = lum_call('Quotes', 'getNewCount');
	$partner_num = lum_call('PartnerRequests', 'getNewCount');
	$total = $num + $iq_num + $partner_num;
?>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Requests/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Requests/admin/images/requests.png" class="menu-icon"/>Requests <b>(<?=$total?>)</b></a>
				<ul>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Requests/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Requests/admin/images/requests_edit.png" class="menu-icon"/>Contacts (<?=$num?>)</a></li>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/PartnerRequests/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Requests/admin/images/requests_edit.png" class="menu-icon"/>Partner Requests (<?=$partner_num?>)</a></li>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Quotes/instant"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Quotes/admin/images/quotes_edit.png" class="menu-icon"/>Instant Quotes (<?=$iq_num?>)</a></li>
				</ul>
			</li>
<?php endif; ?>