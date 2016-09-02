// functions common to all ge sites
function validate_form()
{

	if (false == validate_form_2part1())
	{
		return false;
	}

	if (false == validate_form_2part2())
	{
		return false;
	}

	return true;
}

// functions common to all ge sites
function validate_form_2part1()
{
	with(document.join)
	{
		if(firstname.value.length < 1)
		{
			alert("Please enter your first name.");
			firstname.focus();
			firstname.select();
			return false;
		}
		if(lastname.value.length < 1)
		{
			alert("Please enter your last name.");
			lastname.focus();
			lastname.select();
			return false;
		}
		if(phone.value.length < 1)
		{
			alert("Please enter your phone number.");
			phone.focus();
			phone.select();
			return false;
		}
		if(birthmonth.selectedIndex == 0)
		{
			alert("Please select your birth month.");
			birthmonth.focus();
			return false;
		}
		if(birthdom.selectedIndex == 0)
		{
			alert("Please select your birth date.");
			birthdom.focus();
			return false;
		}
		if(birthyear.value.length < 1)
		{
			alert("Please enter your birth year.");
			birthyear.focus();
			birthyear.select();
			return false;
		}
		

		if(email1.value.length < 1)
		{
			alert("Please enter your email.");
			email1.focus();
			email1.select();
			return false;
		}
		if(email2.value.length < 1)
		{
			alert("Please confirm your email.");
			email2.focus();
			email2.select();
			return false;
		}
		if(email1.value != email2.value)
		{
			alert("Please make sure your email is correct in both fields.");
			email1.focus();
			email1.select();
			return false;
		}
		if(state.selectedIndex == 0)
		{
			alert("Please select your state.");
			state.focus();
			return false;
		}
	}

	return true;

}


function validate_form_2part2()
{
	with(document.join)
	{
		if(address1.value.length < 1)
		{
			alert("Please enter your address.");
			address1.focus();
			address1.select();
			return false;
		}
		if(city.value.length < 1)
		{
			alert("Please enter your city.");
			city.focus();
			city.select();
			return false;
		}
		if(state.selectedIndex == 0)
		{
			alert("Please select your state.");
			state.focus();
			return false;
		}
		if(zip.value.length < 1)
		{
			alert("Please enter your ZIP.");
			zip.focus();
			zip.select();
			return false;
		}
		if(cardtype.selectedIndex == 0)
		{
			alert("Please select the type of card you will be using.");
			cardtype.focus();
			return false;
		}
		if(cardnumber.value.length < 1)
		{
			alert("Please enter your credit card number.");
			cardnumber.focus();
			cardnumber.select();
			return false;
		}
		if(cardnumber.value.length < 15)
		{
			alert("Please correct your card number.");
			cardnumber.focus();
			cardnumber.select();
			return false;
		}
		if(expiremonth.selectedIndex == 0)
		{
			alert("Please select your card expiration month.");
			expiremonth.focus();
			return false;
		}
		if(expireyear.selectedIndex == 0)
		{
			alert("Please select your card expiration year.");
			expireyear.focus();
			return false;
		}
		if(!authorize.checked)
		{
			alert("You must give us authorization to charge your card before you can continue.");
			authorize.focus();
			return false;
		}
		if(!agree.checked)
		{
			alert("You must check that you agree to the terms and conditions of this offer.");
			agree.focus();
			return false;
		}
	}

	return true;
}


