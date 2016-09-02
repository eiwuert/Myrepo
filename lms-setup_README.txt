This document is a description of how to construct the loan management system.  It takes basic assumptions about the reader’s technical prowess, and expects the reader to have a high level of LAMP sever and network configuration knowledge.  It is also expected that the reader take some time with the code to explore the various setting found within the configuration files, and the various utility routines.

To construct the loan management system it will need at minimum of 6 apache servers: fw1/ins1, vendorapi, ecash, web1, mysql, and condor.  It preferred to split fw1 and ins1 into separate servers.  Adding a condor drive server, a monitoring server, and a second mysql server for replications are additional options.
fw1: 	Firewall; standard open source firewall apache systems are available.
ins1:	DNS server; standard open source DNS apache systems are available.
vendorapi:	Vendor management and web1 interface; apache2/php server.
ecash:	Loan management system. apache2/php server.
condor:	Document management system. apache2/php server.
mysql:	MYSQL Database server for ldb_generic, condor, and condor_admin databases.
web1:	Customer interface server with CMS management: apache2/php server with mysql databse.

The apache servers will need to be installed with SSL’s, mod-rewrite, and the root directories defined.  They should also force http calls to use https, and enable the use of php scripts.  Once apache is set up, place the appropriate php folder at the servers code root.

Once the php code is installed, look for the generic.php configuration file on vendorapi and ecash.  Update the fields within this file with the appropriate values.  Also the php files contained in the same folders as the generic.php files are the other configuration files then need to get updated.  There is also some CRA reporting configurations that need to be set on ecash.  Look for the class ECashCra_Driver_Commercial_Config and set the parameters found there.

Most of the condor and web1 configurations are managed in the databases, but there are several locations where things are set in the php code.  On condor look for the config.php file and set the configurations appropriately.  The database connections are set in this file.  Condor is currently set to use an additional condor drive server in the php code.   Condor drive settings are also set in this file.  On web1 look for the defines.inc.php file.  The database configurations are there, and you need to properly set the domain name in to get access to the web1 CMS and myAdmin mysql GUI.

The mysql servers need to be set up, a mysql admin user with password needs to be set.  Then import the ldb_generic, condor, and condor_admin databases.  The table views within these files my not be dumped in the right order, so you should split the view creation out of the dump files and run these in order required that their interdependent relationships dictate.   The web1 database is local, and once installed and imported can also be accessed through the web1/cms-admin/myadmin/ web page.

After the databases are set up and the proper database parameters are set in the php configuration files, the code should come up and give basic operations.  There is an ‘admin’ user set up as an agent/user for each of the web based systems that require logins.  The web1 cms is accessed through the web1/tools path.
