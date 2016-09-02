#!perl     

my $i;
my $o1 = 0;
my $o2 = 0;
my $o3 = 0;
my $o4 = 0;
my $o5 = 0;
my $o = 0;
my @fields;
my $fields;
my @fields2;
my $record;
my $record2;
my $record3;
my $hcustnum=0;
my $loannum=1;
my $servdelta=0;
my $hsrvchrg=0;
 
 sub white {  
	 $_[0] =~ s/""//g;
	 $_[0] =~ s/\"//g;
	 $_[0] =~ s/(?<=[^,])NULL(?=[^,])/,NULL,/g;
     $_[0] =~s/\s+$//g;
	chomp($_[0]);
}

sub clardate {  
	  $unix_date= ($_[0]-61729)*86400;
 	  ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)= localtime($unix_date);
      $year=$year+1900;
      $mon=$mon+1;
      $mon = "0$mon" if ( length($mon) < 2 );
     $mday = "0$mday" if ( length($mday) < 2 );
    $_[0]= "$year$mon$mday";
  }

sub lastweekdate {  
	  ($lsec,$lmin,$lhour,$lmday,$lmon,$lyear,$lwday,$lyday,$lisdst)= localtime($_[0]);
      $lyear=$lyear+1900;
      $lmon=$lmon+1;
      $lmon = "0$lmon" if ( length($lmon) < 2 );
      $lmday = "0$lmday" if ( length($lmday) < 2 );
      $_[0]= "$lyear$lmon$lmday";
  }
$now=time;

#  SET DATE to go back to in DAYS relative to current date
$qtime=$now-(8*86400);
$lastweek=lastweekdate($qtime);
print "$lastweek\n";
 

#use strict;
use DBI ;
$user = 'jandrews' ;
$passwd = 'ingres.db' ;

# Connect to the SQLServer 2000 databaseÊÊ 

$dbh = DBI->connect('DBI:Sybase:server=MyServer2k',$user, $passwd)
 or die "Can't connect to SQLSERVER: $DBI::errstr\n";
 

# Connect to the MySQL databaseÊ
 
my $dsn = 'DBI:mysql:react_db:170.224.71.199';
my $db_user = 'johna';
my $db_pass = 'clv6362';
my $dbh2 = DBI->connect('DBI:mysql:react_db:170.224.71.199', "johna", "clv6362") 
  or die "Can't connect to TRANLINK: $DBI::errstr\n";

# Local MySQL database for testing
#my $dsn = 'DBI:mysql:ErrLog:10.1.24.1';
#my $db_user = 'root';
#my $db_pass = '';
#my $dbh2 = DBI->connect('DBI:mysql:ErrLog:10.1.24.1','root','')
#	or die "Can't connect to TRANLINK: $DBI::errstr\n";


# Empty Table trancycle
my $sqlx = "TRUNCATE TABLE d1";
my $sthx = $dbh2->prepare($sqlx) ;
   $sthx->execute() ;
print "Empty table d1 - MySQL...\n"; 


print "$lastweek\n";
 

$dbh->do("use nms");
$sql_statement = qq{select max(customer.customernumber) ,max(status),max(firstname),max(lastname),max(emailaddress),ssnumber, max(type), max(birthdate),
convert(varchar,max(datepaid),112) from customer INNER JOIN transact on transact.customernumber = customer.customernumber 
where (substring(convert(varchar,datepaid,112),1,8) >= $lastweek and type = 'ADVANCE' and status = 'INACTIVE' and amount = paid and emailaddress !='') 
group by ssnumber ORDER BY ssnumber asc  };
$sth = $dbh->prepare($sql_statement) ;
$sth->execute or die "Couldn't execute statement: " . $sth->errstr;
$i=0;


while (my ($custnum,$status,$fn,$ln,$email,$ssn,$trantype,$dob,$datepaid) = $sth->fetchrow_array) {
		white($trantype);
		white($status); 
		white($email);
		white($fn);
		white($ln); 
		white($ssn);
		
   	  	if($dob >=1 )
   	    {clardate($dob);}
   	    else
   	    {$dob="";}
   	   	$record = "$dob,$email,$fn,$ln,$ssn,$status";
     	
     		$sql =  qq{insert into d1 (birthdate,email,lastname,firstname,ssnumber,status)  values ("$dob","$email","$fn","$ln","$ssn","$status")};
 	 		#print "$sql\n";
 	 		 my  $sth2 = $dbh2->prepare($sql);
 	 	 		$sth2->execute or die "Couldn't execute statement: " . $sth->errstr;

     	
     	#print OUTPUT1 "$record\n";
     	$o1++;
 

 	if ($i++ % 1000 == 0)
		{
			 print  "INPUT1 : $i\n";
		}

}
print "D1 rows loaded......$i\n";


$i=0;

 


$dbh->disconnect;  
$dbh2->disconnect;  



 
