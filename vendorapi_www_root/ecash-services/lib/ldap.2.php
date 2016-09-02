<?php

/**
   @publicsection
   @public
   @brief This class provides standard tss/php interaction with an LDAP server

   Updated from previous version to provide protocol selection support
*/
class LDAP_2
{
	var $bind_cn;
	var $bind_pw;
	var $ldap_handle;
	var $self_search;
	
	/**
		@publicsection
		@public
		@fn boolean LDAP_2($server, $bind_cn, $bind_pw)
		@brief boolean LDAP_2(string server, string bind_cn, string bind_pw)
        @param string server \n ldap server name
        @param string bind_cn \n context name to bind with
        @param string bind_pw \n password for context name
        @return boolean \n TRUE

        Creates an LDAP_1 object and attempts to connect to the server
	*/
	function LDAP_2($server, $bind_cn, $bind_pw)
	{
		$this->bind_cn = $bind_cn;
		$this->bind_pw = $bind_pw;
		$this->ldap_handle = ldap_connect($server);
		$this->Set_LDAP_Protocol_Version(3);
		return TRUE;
	}

	/**
		@publicsection
		@public
		@fn boolean Set_LDAP_Protocol_Version($protocol_version)
		@brief boolean Set_LDAP_Protocol_Version(int protocol_version)
        @param int protocol_version \n version of the LDAP protocol to use
        @return boolean \n returns TRUE if success, FALSE otherwise

        Sets an LDAP protocol version to use when binding and performing operations
        on the server, default is 3. Use this method right after creating the object
        to force another version.
	*/
	function Set_LDAP_Protocol_Version($protocol_version)
	{
		if(ldap_set_option($this->ldap_handle, LDAP_OPT_PROTOCOL_VERSION, $protocol_version))
			return true;
		else
			return false;
	}

	/**
	   @publicsection
	   @public
	   @fn boolean Bind()
	   @brief boolean Bind()
       @return boolean \n returns TRUE if success, FALSE if not

       Will return TRUE or FALSE depending if the bind to the ldap
       server (with the given context and password) succeeded.  Will
       also return FALSE if connect failed.
	*/	
	function Bind()
	{
		if($this->ldap_handle)
		{
			return ldap_bind ( $this->ldap_handle, $this->bind_cn, $this->bind_pw);
		}
		return FALSE;
	}

	/**
	   @publicsection
	   @public
	   @fn boolean Unbind()
	   @brief boolean Unbind()
       @return boolean \n returns TRUE if success, FALSE if not

       Will return TRUE or FALSE depending if the unbind from the ldap
       server succeeded.  Will also return TRUE if connect failed.
	*/	
	function Unbind()
	{
		if($this->ldap_handle)
		{
			return ldap_unbind($this->ldap_handle);
		}
		//else return true because we never bound to this,
		//so we don't have to unbind
		return TRUE;
	}

	/**
	   @publicsection
	   @public
	   @fn boolean Is_Expired()
	   @brief boolean Is_Expired()
       @return boolean \n returns TRUE if account has expired, FALSE if not

       Will return TRUE or FALSE if the account you bound to the ldap
       server with is expired.
	*/	
	function Is_Expired()
	{
		//keys should be lowercase for php (for whatever reason)
		$days = $this->Get_Attributes("shadowexpire");
		if($days && $days <= $this->_Get_Expire_Days())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	   @publicsection
	   @public
	   @fn mixed Get_Attributes($attributes)
	   @brief mixed Get_Attributes(mixed attributes)
       @return mixed \n either an array containing attributes/values
       		or a single element if only one attribute was requested

       If only a single attribute was passed in, a single value will
       be returned.  If an array is passed in, the ldap
       attribute/value array will be passed back.
	*/	
	function Get_Attributes($attributes)
	{
		if(!is_array($attributes))
		{
			$attributes = array( $attributes );
		}
		
		$filter="(objectClass=*)";
		$search_result = ldap_search($this->ldap_handle, $this->bind_cn, $filter, $attributes);
		$info = ldap_get_entries($this->ldap_handle, $search_result);
		
		if(count($attributes) == 1)
		{
			//return first element
			reset($attributes);
			list($key,$val) = each($attributes);
			return $info[0][$val][0];
		}
		return $info;
	}

	function Search($base, $filter)
	{
		$search_result = ldap_search($this->ldap_handle, $base, $filter);
		if(!ldap_count_entries($this->ldap_handle, $search_result))
		{
			return FALSE;
		}
		$info = ldap_get_entries($this->ldap_handle, $search_result);
		return $info;
	}
	
	/**
	   @publicsection
	   @public
	   @fn Expire_Account($days=NULL)
	   @brief Expire_Account([int days])

       Expires LDAP account on the nth day after UNIX Epoch (Jan. 1st
       1970).  If days is not specified, it is expired today (now).
	*/	
	function Expire_Account($days=NULL)
	{
		if(!$days)
		{
			$days = $this->_Get_Expire_Days();
		}
		$info['shadowexpire'] = $days;
		ldap_mod_add($this->$ldap_handle, $this->$bind_cn, $info);
	}

	/**
	   @privatesection
	   @private
	   @fn _Get_Expire_Days()
	   @brief _Get_Expire_Days()
	   @return int \n number of days between today and UNIX Epoch (Jan. 1st 1970)
	*/	
	function _Get_Expire_Days()
	{
		$diff = time();
		$remain_sec = $diff % 86400;
		return ($diff - $remain_sec) / 86400;
	}
	
}

?>