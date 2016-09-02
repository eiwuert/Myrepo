<?

# ------------------------------------------------------------------------
# Credit Card Validation Solution, version 3.5                 PHP Edition
# 25 May 2000
#
# COPYRIGHT NOTICE:
# a) This code is property of The Analysis and Solutions Company.
# b) It is being distributed free of charge and on an "as is" basis.
# c) Use of this code, or any part thereof, is contingent upon leaving
#     this copyright notice, name and address information in tact.
# d) Written permission must be obtained from us before this code, or any
#     part thereof, is sold or used in a product which is sold.
# e) By using this code, you accept full responsibility for its use
#     and will not hold the Analysis and Solutions Company, its employees
#     or officers liable for damages of any sort.
# f) This code is not to be used for illegal purposes.
# g) Please email us any revisions made to this code.
#
# Copyright 2000                 http://www.AnalysisAndSolutions.com/code/
# The Analysis and Solutions Company         info@AnalysisAndSolutions.com
# ------------------------------------------------------------------------
#
# DESCRIPTION:
# Credit Card Validation Solution uses a four step process to ensure
# credit card numbers are keyed in correctly.  This procedure accurately
# checks cards from American Express, Australian BankCard, Carte Blache,
# Diners Club, Discover/Novus, JCB, MasterCard and Visa.
#
# CAUTION:
# CCVS uses exact number ranges as part of the validation process. These
# ranges are current as of 20 October 1999.  If presently undefined ranges
# come into use in the future, this program will improperly deject card
# numbers in such ranges, rendering an error message entitled "Potential
# Card Type Discrepancy."  If this happens while entering a card & type
# you KNOW are valid, please contact us so we can update the ranges.
#
# POTENTIAL CUSTOMIZATIONS:
# *  If you don''t accept some of these card types, edit Step 2, using pound
# signs "#" to comment out the "elseif," "$CardName" and "$ShouldLength"
# lines in question.
# *  Additional card types can be added by inserting new "elseif,"
# "$CardName" and "$ShouldLength" lines in Step 2.
# *  The three functions here can be called by other PHP documents to check
# any number.
#
# CREDITS:
# We learned of the Mod 10 Algorithm in some Perl code, entitled
# "The Validator," available on Matt''s Script Archive,
# http://worldwidemart.com/scripts/readme/ccver.shtml.  That code was
# written by David Paris, who based it on material Melvyn Myers reposted
# from an unknown author.  Paris credits Aries Solis for tracking down the
# data underlying the algorithm.  At the same time, our code bears no
# resemblance to its predecessors.  CCValidationSolution was first written
# for Visual Basic, on which Allen Browne and Rico Zschau assisted.
# Neil Fraser helped prune down the OnlyNumericSolution() for Perl.


function CCValidationSolution ($Number) {
    global $CardName;
	global $CardNumber;

    # 1) Get rid of spaces and non-numeric characters.
    $Number = OnlyNumericSolution($Number);

    # 2) Do the first four digits fit within proper ranges?
    #     If so, who''s the card issuer and how long should the number be?
    $NumberLeft = substr($Number, 0, 4);
    $NumberLength = strlen($Number);

    if ($NumberLeft >= 3000 and $NumberLeft <= 3059) {
        $CardName = "Diners Club";
        $ShouldLength = 14;
    } elseif ($NumberLeft >= 3600 and $NumberLeft <= 3699) {
        $CardName = "Diners Club";
        $ShouldLength = 14;
    } elseif ($NumberLeft >= 3800 and $NumberLeft <= 3889) {
        $CardName = "Diners Club";
        $ShouldLength = 14;

    } elseif ($NumberLeft >= 3400 and $NumberLeft <= 3499) {
        $CardName = "American Express";
        $ShouldLength = 15;
    } elseif ($NumberLeft >= 3700 and $NumberLeft <= 3799) {
        $CardName = "American Express";
        $ShouldLength = 15;

    } elseif ($NumberLeft >= 3528 and $NumberLeft <= 3589) {
        $CardName = "JCB";
        $ShouldLength = 16;

    } elseif ($NumberLeft >= 3890 and $NumberLeft <= 3899) {
        $CardName = "Carte Blache";
        $ShouldLength = 14;

    } elseif ($NumberLeft >= 4000 and $NumberLeft <= 4999) {
        $CardName = "Visa";
        if ($NumberLength > 14) {
            $ShouldLength = 16;
        } elseif ($NumberLength < 14) {
            $ShouldLength = 13;
        } else {
            $cc_val = 'The <b>Visa</b> number entered, ' . $Number . ', is 14 digits long. <b>Visa</b> cards usually have 16 digits, though some have 13.<br>Please check the number and try again.';
			return $cc_val;
        }

    } elseif ($NumberLeft >= 5100 and $NumberLeft <= 5599) {
        $CardName = "MasterCard";
        $ShouldLength = 16;

    } elseif ($NumberLeft == 5610) {
        $CardName = "Australian BankCard";
        $ShouldLength = 16;

    } elseif ($NumberLeft == 6011) {
        $CardName = "Discover/Novus";
        $ShouldLength = 16;

    } else {
        $cc_val = 'The first four digits of the number entered are ' . $NumberLeft . '.<br>&nbsp;If that' . "'" . 's correct, we don' . "'" . 't accept that type of credit card.<br>&nbsp;If it' . "'" . 's wrong, please try again.';
        return $cc_val;
    }


    # 3) Is the number the right length?
    if ($NumberLength <> $ShouldLength) {
        $Missing = $NumberLength - $ShouldLength;
        if ($Missing < 0) {
            $cc_val = 'The <b>' . $CardName . '</b> number entered, ' . $Number . ', is <font color="#FF0000"><b>missing</b></font> ' . abs($Missing) . ' digit(s).<br>&nbsp;Please check the number and try again.';
        } else {
            $cc_val = 'The <b>' . $CardName . '</b> number entered, ' . $Number . ', has ' . $Missing . ' too many digit(s).<br>&nbsp;Please check the number and try again.';
        }
        return $cc_val;
    }


    # 4) Does the number pass the Mod 10 Algorithm Checksum?
    if (Mod10Solution($Number) == TRUE) {
	    $CardNumber = $Number;
        return TRUE;
    } else {
    $cc_val = 'The <b>' . $CardName . '</b> number entered, ' . $Number . ', is <font color="#FF0000"><b>invalid</b></font>. Please check the number and try again.';
	return $cc_val;
    }
}

function OnlyNumericSolution ($Number) {
   # Remove any non numeric characters.
   # Ensure number is no more than 19 characters long.
   return substr( ereg_replace( "[^0-9]", "", $Number) , 0, 19);
}


function Mod10Solution ($Number) {
    $NumberLength = strlen($Number);
    $Checksum = 0;

    # Add even digits in even length strings
    # or odd digits in odd length strings.
    for ($Location = 1 - ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
        $Checksum += substr($Number, $Location, 1);
    }

    # Analyze odd digits in even length strings
    # or even digits in odd length strings.
    for ($Location = ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
        $Digit = substr($Number, $Location, 1) * 2;
        if ($Digit < 10) {
            $Checksum += $Digit;
        } else {
            $Checksum += $Digit - 9;
        }
    }

    # Is the checksum divisible by ten?
    return ($Checksum % 10 == 0);
}

?>
