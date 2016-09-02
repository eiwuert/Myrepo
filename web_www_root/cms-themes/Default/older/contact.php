<?php
/*
Template: Contact Us
Description: Contact page for the site
*/
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
[cmsinclude Header]
	<div class="wrapper">
		<div class="interior">
			<h1>[PAGE_TITLE]</h1>
			[cmsrichtext Content]
<table id="contact_tbl" cellspacing="10px">
<tbody>
<tr>
<td>
	[cmsrichtext Contact Info]
</td>
<td><img src="/cms-themes/Default/images/graphic1.png" alt="Speak with a specialist today" /><br /> <!-- live2support.com tracking codes starts -->
<div id="l2s_trk" style="z-index:99;" class="right"><a href="http://live2support.com" style="font-size:1px;">Live Support Software</a></div>
<script type="text/javascript"><!-- 

var l2slay_bcolor="#035091";
var l2slay_himg="https://live2support.com/imgs/lyrjs/h1.png";
var l2sdialogofftxt="Live Chat Offline";
var l2sdialogontxt="Live Chat Online";
var l2sminimize=true;
var l2senblyr=true; 
var l2slay_pos="M";
var l2s_pht=escape(location.protocol); 
if(l2s_pht.indexOf("http")==-1) l2s_pht='http:';  
var off2="cms-themes/Default/images/[cmsimage offline_graphic]"; 
var on2="cms-themes/Default/images/[cmsimage online_graphic]"; 
function l2s_load() { 
document.write('<scr'+'ipt type="text/javascr'+'ipt" src ="'+unescape(l2s_pht)+'//sa.live2support.com/js/lsjs1.php?stid=15697&jqry=Y&cmi=1"  defer=true>'+'</scr'+'ipt>');  
} 
l2s_load();  
document.getElementById('l2s_trk').style.visibility='hidden'; 
//--></script><!-- live2support.com tracking codes closed --></td>
</tr>
</tbody>
</table>			
		</div>
	</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
