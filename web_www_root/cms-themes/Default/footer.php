<?php
/*
Template: (Include) Footer
Description: The footer for the web site
*/
?>
		<div class="wrapper">
			<div class="badges">
				<table width="135" border="0" cellpadding="2" cellspacing="0" title="Click to Verify - This site chose GeoTrust SSL for secure e-commerce and confidential communications.">
					<tr>
						<td width="135" align="center" valign="top"><script type="text/javascript" src="https://seal.geotrust.com/getgeotrustsslseal?host_name=someloancompany.com&amp;size=S&amp;lang=en"></script><br />
						<a href="http://www.geotrust.com/ssl/" target="_blank"  class="geotrust"></a></td>
					</tr>
				</table>
				<a target="_blank" href="http://www.onlinelendersalliance.org">
					<img alt="OLA Mamber" title="OLA Mamber" src="<?php lum_getThemeImageUrl();?>/ola.jpg" class="ola_image"/>
				</a>
				<a href="http://www.credit-card-logos.com">
					<img alt="Credit Card Logos" title="Credit Card Logos" src="<?php lum_getThemeImageUrl();?>/cards.jpg" class="card_image" />
				</a>
			</div>
			<div class="font_size">
				<a id="jfontsize-plus" style="cursor: pointer; ">A+</a>
				<a id="jfontsize-minus" style="cursor: pointer; ">A-</a>
				<h2>[cmstext Change Font Size]</h2>
			</div>
		</div>
		<div id="footer">
			<div class="wrapper clearfix">
				<p class="resizeable">[cmstext Not All Applicants]</p>
				<p class="resizeable">[cmstext Typically will not]</p>
				<p class="resizeable">[cmstext Clearlake Business]</p>
				<div class="menu">
					<h4>[cmstext Company Title]</h4>
					<ul>
						<li><a href="/<?=lum_getCurrentLanguage()?>">[cmstext Home Link Text]</a></li>
						<li><a href="/<?=lum_getCurrentLanguage()?>/faqs">[cmstext FAQ Link Text]</a></li>
						<li><a href="/<?=lum_getCurrentLanguage()?>/rates-and-terms">[cmstext Rates Link Text]</a></li>
					</ul>
				</div>
				<div class="menu">
					<h4>[cmstext Communicate Title]</h4>
					<ul>
						<?php if (lum_getString("[ENABLE_SOCIAL]")== 'TRUE') {?>
						<li><a href="/<?=lum_getCurrentLanguage()?>/social">[cmstext Social Link Text]</a></li>
						<?php } ?>
						<li><a href="/<?=lum_getCurrentLanguage()?>/contact">[cmstext Contact Link Text]</a></li>
						<li><div id="l2s_trk2"></div>
 <script type="text/javascript">  
 var l2sontxt2 = "Live Chat Online"; 
 var l2sofftxt2 = "Live Chat Offline"; 
</script> </li>
					</ul>
				</div>
				<div class="menu">
					<h4>[cmstext Policy Title]</h4>
					<ul>
						<li><a href="/<?=lum_getCurrentLanguage()?>/privacy-policy">[cmstext Privacy Link Text]</a></li>
						<li><a href="/<?=lum_getCurrentLanguage()?>/report-spam">[cmstext Spam Link Text]</a></li>
						<li><a href="/<?=lum_getCurrentLanguage()?>/terms-of-use">[cmstext Terms Link Text]</a></li>
					</ul>
				</div>
			</div>
			<div class="footer_bottom">
				<div class="wrapper">
					<p>[cmstext Copyright]</p>
				</div>
			</div>
		</div>

