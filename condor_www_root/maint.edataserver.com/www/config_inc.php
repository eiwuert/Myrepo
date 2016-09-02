<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: config_inc.php.sample,v 1.14 2004/09/12 12:23:35 vboctor Exp $
	# --------------------------------------------------------
	
	# This sample file contains the essential files that you MUST
	# configure to your specific settings.  You may override settings
	# from config_defaults_inc.php by assigning new values in this file

	# Rename this file to config_inc.php after configuration.

	###########################################################################
	# CONFIGURATION VARIABLES
	###########################################################################

	# In general the value OFF means the feature is disabled and ON means the
	# feature is enabled.  Any other cases will have an explanation.

	# Look in http://www.mantisbt.org/manual or config_defaults_inc.php for more
	# detailed comments.

	# --- database variables ---------

	# set these values to match your setup
	$g_hostname      = "db100.clkonline.com";
	$g_db_username   = "sellingsource";
	$g_db_password   = "%selling\$_db";
	$g_database_name = "mantis-maint";

	# --- email variables -------------
	$g_administrator_email  = 'mikeg@sellingsource.com';
	$g_webmaster_email      = 'mikeg@sellingsource.com';

	# the "From: " field in emails
	$g_from_email           = 'noreply@sellingsource.com';

	# the return address for bounced mail
	$g_return_path_email    = 'mikeg@sellingsource.com';

	# --- file upload settings --------
	# This is the master setting to disable *all* file uploading functionality
	#
	# The default value is ON but you must make sure file uploading is enabled
	#  in PHP as well.  You may need to add "file_uploads = TRUE" to your php.ini.
	$g_allow_file_upload	= OFF;

	# Defined here to add a status. I used the default status matrix and added "In CVS" status
	$g_status_enum_string = 
		'10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved,90:closed';

	# Status color additions
	$g_status_colors['in CVS'] = '#FF0000';
	
	$g_status_enum_workflow[NEW_]= '10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved'; 
	$g_status_enum_workflow[FEEDBACK] = '10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved'; 
	$g_status_enum_workflow[ACKNOWLEDGED] = '20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved'; 
	$g_status_enum_workflow[CONFIRMED] = '20:feedback,40:confirmed,50:assigned,80:resolved'; 
	$g_status_enum_workflow[ASSIGNED] = '20:feedback,50:assigned,80:resolved,90:closed'; 
	$g_status_enum_workflow[INCVS] = '20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved,90:closed';
	$g_status_enum_workflow[RESOLVED] = '50:assigned,80:resolved,90:closed'; 
	$g_status_enum_workflow[CLOSED] = '50:assigned';
?>
