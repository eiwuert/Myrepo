<?php
/*
Template: Home
Description: Home page for the site
*/
?>
<?php include(TEMPLATES_PATH.'application_header_html.php');
	function getCurrentLanguage(){
		return lum_getCurrentLanguage() == '' ? lum_isDefaultLanguage(): lum_getCurrentLanguage();
	}
	// check if user is logged in.  if so go to customer service page.
	if ((isset($_COOKIE[lum_getString("[SESSION_NAME]")])) &&
	    (!empty($_COOKIE[lum_getString("[SESSION_NAME]")])) &&
	    (isset($_SESSION['application']))){
		lum_redirect("/".getCurrentLanguage()."/customer_portal");
	}
?>
<div id="normal_page">
	[cmsinclude Header]
	<div class="slider_container">
		<div class="wrapper clearfix">
			<div class="flexslider">
				<ul class="slides">
					<li>
						<img src="<?php lum_getThemeImageUrl();?>/slide1.jpg" />
					</li>
					<li>
						<img src="<?php lum_getThemeImageUrl();?>/slide2.jpg" />
					</li>
				</ul>
			</div>
			<?php if (lum_getString("[ENABLE_SOCIAL]") == 'TRUE') {?>
			<div class="social">
				<span>[cmstext Follow Us]</span>
				<a href="http://www.facebook.com/pages/someloancompany" target="_blank" class="facebook"></a>
				<a href="http://twitter.com/someloancompany" target="_blank" class="twitter"></a>
			</div>
			<?php } ?>
		</div>
	</div>
	<div id="content">
		<div class="wrapper">
			<img src="[cmsimage home graphic]" alt="[cmstext Lock Image Alt Text]" />
			<div class="home_bottom clearfix">
				<form class="validate_form" id="application_form" method="post" action="/<?=getCurrentLanguage()?>/application_submit">
					<div class="left_column">
						<div class="white_box quick_cash">
							<input id="requested_amount" name="requested_amount" type="hidden" />
							<h3>[cmstext Quick Cash]</h3>
							<p>[cmstext need to borrow]</p>
							<div id="request_div">
							      <span id="request_space"></span>
							      <div id="request_val" style="width:100"></div>
							</div>
							<div id="request_slide"></div>
							<br>
							<p>[cmstext Fees]&nbsp<a href="/<?=getCurrentLanguage()?>/rates">[cmstext Rates]</a></p>
						</div>
						<div class="white_box apply_now clearfix">
							<h3>[cmstext Apply Now]</h3>
							<p>[cmstext See how much]</p>
							[cmsinclude Application]
						</div>
					</div>
					<div class="right_column">
						<div class="white_bwebox question">
							<h3>[cmstext Have a Question]</h3>
							<p>[cmstext Another Way]</p>
							<img src="[cmsimage specialist graphic]" alt="[cmstext Specialst Image Alt Text]" />
							<div class="button_container">
	<!-- live2support.com tracking codes starts -->
	<div id="l2s_trk" style="z-index:99;"><a href="http://live2support.com" style="font-size:1px;">Live Support Software</a></div>
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
	var off2="[cmsimage offline graphic]"; 
	var on2="[cmsimage online graphic]"; 
	function l2s_load() { 
	document.write('<scr'+'ipt type="text/javascr'+'ipt" src ="'+unescape(l2s_pht)+'//sa.live2support.com/js/lsjs1.php?stid=15697&jqry=Y&cmi=1"  defer=true>'+'</scr'+'ipt>');  
	} 
	l2s_load();  
	document.getElementById('l2s_trk').style.visibility='hidden'; 
	//--></script><!-- live2support.com tracking codes closed -->
							</div>
						</div>
						<div class="white_box testimonials resizeable">
							<h3>[cmstext Testimonials Title]</h3>
							<p>[cmstext Testimonial 1 Content]</p>
							<span>[cmstext Testimonial 1 Name]<br />
							[cmstext Testimonial 1 Location]</span>
							<hr />
							<p>[cmstext Testimonial 2 Content]</p>
							<span>[cmstext Testimonial 2 Name]<br />
							[cmstext Testimonial 2 Location]</span>
							<a href="/<?=getCurrentLanguage()?>/testimonials">[cmstext View More]</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	[cmsinclude Footer]
</div>
<table id="waiting_page_fbl"><tbody><tr><td id="waiting_page_td">
	<div class="waiting_page hide">
		<p class="waiting_text">[cmstext Please Wait]</p>
	</div>
</td></tr></tbody></table>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
