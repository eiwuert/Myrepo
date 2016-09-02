<!-- DV8 :: PAPERLESS -->
<div id="wf-@@template_css@@-copy" class="@@template_css@@-copy">
	<form action="@@URL_ROOT@@" method="post" onSubmit="exit=false";>
	<input type="hidden" name="page" value="app_decline_02" />
	<p>In order to better understand your needs, please help us out and let us know 
	why you did not want to process your loan:</p>

	<ol>
		<li class="main"><input type="radio" name="declined_reason" value="WANTED_MORE_MONEY">I Needed More Money</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="WANTED_LESS_MONEY">I Needed Less Money</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="FEES_TOO_HIGH">Your fees are too high</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="JUST_CURIOUS">Just looking, I don't need the money</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="WANT_MORE_INFO">My questions weren't answered on the site</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="NEVER_INTERESTED">I was never interested</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="NO_TIME">I don't have time to get everything together</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="NOT_NOW">I probably want a loan in the future, but not right now</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="DUE_TOO_SOON">The due date is too soon</input></li>
		<li class="main"><input type="radio" name="declined_reason" value="OTHER">Other (please let us know)</input></li>
	</ol>

	<table border="0">
	<tr>
		<td><textarea name="declined_other" cols="50" rows="10"></textarea></td>
	</tr>
	<tr>
		<td class="sh-align-center"><input type="submit" class="button" name="submit" value="SUBMIT" /></td>
	</tr>
	</table>
</form>
</div>