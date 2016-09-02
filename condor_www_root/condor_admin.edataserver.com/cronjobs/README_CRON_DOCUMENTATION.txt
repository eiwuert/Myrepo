
Lifecycle of statuses as they relate to cron jobs:

                                                transaction
                                  application   (enrollment fee)   card                            cron name
                                  -----------   ----------------   ----                            ---------
initial enrollment:               PENDING       PENDING            n/a                             n/a

7 days after enrollment:          ACTIVE        COMPLETE           PENDING                         enrollment_cron.php

CashLynk creates account:         ACTIVE        COMPLETE           WAITING PIN                     activate_card.php

Notification User has activated:  ACTIVE        COMPLETE           ENABLED (fcmc)/ACTIVE (egc)     card_activated_cron.php




Narrative:
	
1.	The initial enrollment creates an application in PENDING status and
	creates a transaction representing the enrollment fee in PENDING status.
	The date_completed on the transaction is set to approximately 7 days in
	the future because 7 days (or so) without an ACH return means we successfully
	cleared the enrollment fee from the user's bank account.

2.	Some cron job will get a file of returned/failed ACH transactions and will
    mark the applicable enrollment transactions with a status of RETURNED.
    	
3.	The "transaction_cron.php" will run everyday and for all PENDING enrollment
 	transactions with a date_completed <= today, it will change the
 	status from PENDING -> COMPLETE.
 	    	
4.	The "enrollment_cron.php" will run everyday and will find all PENDING applications
	with COMPLETE enrollment transactions.  It will change the application status
	from PENDING -> ACTIVE and will create a card record with a blank card number
	and will create the cardholder account in CashLynk.  The card will be
	placed in PENDING status.

5.	The "activate_cards.php" cron will run everyday finding all cards in PENDING
	status atttached to applications in ACTIVE status.  This cron will check if
	the cardholder account has been created in CashLynk and if so, will update
	the card number and will change the card status from PENDING -> WAITING PIN.

6.	The "card_activated_cron.php" will check to see if the user has activated their card.  If
	so, the card status will be changed from WAITING PIN -> ACTIVE.
		
		
 