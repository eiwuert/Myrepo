<?php
/**
	An abstract base for objects that can be stored.
*/

abstract class SourcePro_Entity_Storage extends SourcePro_Entity_Base
{
	/// The schema that this object belongs to.
	public $m_schema;

	/// The table that this object is stored in.
	public $m_table;

	/// Vector of relations
	public $v_relation = array();

	/// Vector of Indexes.
	public $v_index = array();

	/// Vector of storage object references.
	public $v_store = array();

	/// Vector of notification object references.
	public $v_notification = array();

	/// Flagged if we will autocreate
	public $f_autocreate = FALSE;

	/// Flagged if failure notification should be sent
	public $f_fail_notification = FALSE;

	/**
		Initializes an instance of this class.

		@param store		The array of storage objects used to save or load this object.
		@param schema		The optional schema that this object is stored in.
	*/
	function __construct ($store = NULL, $schema = NULL)
	{
		parent::__construct();

		if (! is_null ($store))
		{
			$this->v_store = (is_array($store) ? $store : array($store));
		}
		$this->m_schema = $schema;
	}

	function __destruct ()
	{
		unset ($this->v_relation, $this->v_index, $this->v_store, $this->v_notification);
		parent::__destruct ();
	}

	/**
		Sets the table we are stored in.

		@param table		The name of the table.
	*/
	protected function Set_Table ($table)
	{
		$this->m_table = $table;
	}

	/**
		Toggles autocreate behavior. When on, loading an object by key may do an insert.

		@param table		The name of the table.
	*/
	protected function Set_AutoCreate ($flag = TRUE)
	{
		$this->f_autocreate = $flag;
	}

	/**
		Toggles failnotification behavior. When on, save, load, and exist will send a notification when a failure occurs.

		@param table		The name of the table.
	*/
	protected function Set_FailNotification ($flag = TRUE)
	{
		$this->f_fail_notification = $flag;
	}

	/**
		Sets the notification protocol used to send notifications.

		@param protocol					The protocol to use to send the notification.
		@param send_notification		The flag to send notification (default TRUE).
	*/
	protected function Add_Notification ($notification, $send_notification = TRUE)
	{
		$set_flag = FALSE;

		// Did we get an array?
		if (is_array ($notification))
		{
			// Walk each and test for valid object
			foreach ($notification as $notice)
			{
				$set_flag = $this->Set_Notification ($notice, $set_flag);
			}
		}

		// Not an array, test for valid object
		elseif ($notification instanceof SourcePro_Notification_Base)
		{
			$set_flag = $this->Set_Notification ($notification, $set_flag);
		}

		// Did we get at least one valid object?
		if ($set_flag)
		{
			$this->Set_FailNotification ($send_notification);
		}
	}

	private function Set_Notification ($notice, $flag)
	{
		if ($notice instanceof SourcePro_Entity_Notification)
		{
			$this->v_notification[] = $notice;

			return TRUE;
		}
		// Has something worked in the past?
		elseif ($flag)
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
		Adds a relation attribute to this object.

		@param name			The name of this relation.
		@param data		Initial data
		@param class		The class of the object(s) we are related to.
		@param schema	The schema of the object(s) we are related to.
		@param field		The field our external id is in.
		@param type		Internal or External id field.
		@param multi		Indicates if multiple objects are allowed.
		@param load
	*/
	protected function Add_Relation ($name, $data, $class, $schema, $field, $type, $index_key, $multi = FALSE, $load = TRUE, $notification = NULL)
	{
		$relation_class = 'SourcePro_Entity_Attribute_Relation_' . ($multi ?  'Multi' : 'Single');
		$this->v_relation[$name] = $this->v_attribute[$name] = new $relation_class ($this, $name, $class, $schema, $field, $type, $index_key, $load, $notification);

		if (! $multi)
		{
			$this->v_attribute[$name]->m_entity = new $class ($this->v_store, $schema, $data, $notification);
		}

		return TRUE;
	}

	/**
		Adds an index to this object.
		Indexes are used in the order which they are added.

		@param field		The field name(s) included in the index. Multiple arguments are accepted. (ie. Add_Index('field1', 'field2', ...))
	*/
	protected function Add_Index ($field)
	{
		$this->v_index[] = func_get_args();
	}

	/**
		Saves this object into its object store.
	*/
	public function Save ()
	{
		$try_count = 0;
		$total_count = count($this->v_store);

		foreach ($this->v_store as $index => $store)
		{
			try
			{
				$store->Save_Object ($this);
			}

			catch (Exception $e)
			{
				// Try Notification
				$this->Notify ($e);

				// Are there more stores to try?
				if (++$try_count >= $total_count)
				{
					// At the end, all have failed, punt
					throw $e;
				}

				// Try the next iteration
				continue;
			}

			// There was no exception, must have worked, break out
			break;
		}
	}

	/**
		Loads this object from its object store.

		@param index	Array of columns to index on
	*/
	public function Load ($index = NULL)
	{
		foreach ($this->v_store as $store)
		{
			try
			{
				$rs = $store->Load_Object ($this, $index);
				if (count ($rs) > 0)
				{
					foreach ($rs[0] as $key => $val)
					{
						if (isset ($this->v_attribute[$key]))
						{
							$this->{$key} = $val;
						}
					}

					foreach ($this->v_relation as $relation)
					{
						if ($relation->m_load)
						{
							$relation->Load ($store, $this->{$relation->m_field});
						}
					}
					$this->f_changed = FALSE;
				}
			}
			catch (Exception $e)
			{
				// Try Notification
				$this->Notify ($e);

				// We do not try to load from fall back systems for corruption.
				throw $e;
			}
		}
	}

	/**
		Deletes this object from its object store.
	*/
	public function Delete ()
	{
		foreach ($this->v_store as $store)
		{
			try
			{
				$rs = $store->Delete_Object ($this);
			}
			catch (Exception $e)
			{
				// Try Notification
				$this->Notify ($e);

				throw $e;
			}
		}
	}

	/**
		Tests this object for existance in object store.

		@param index	Array of columns to index on
	*/
	public function Exist ($index = NULL)
	{
		foreach ($this->v_store as $store)
		{
			try
			{
				return $store->Exist_Object ($this, $index);
			}
			catch (Exception $e)
			{
				// Try Notification
				$this->Notify ($e);

				// We do not try to search from fall back systems for corruption.
				throw $e;
			}
		}
	}

	/**
		Get the id for this object.
	*/
	public function Get_Id ()
	{
		return $this->r_field_id ? $this->r_field_id->_get() : NULL;
	}

	/**
		Get the key for this object.
	*/
	public function Get_Key ()
	{
		return $this->r_field_key ? $this->r_field_key->_get() : NULL;
	}

	/**
		Sends a notification.
	*/
	private function Notify ($e)
	{
		// Send notification, if flagged
		if ($this->f_fail_notification)
		{
			foreach ($this->v_notification as $notification)
			{
				$notification->error_message = $e->getMessage();
				$notification->error_code = $e->getCode();
				$notification->error_trace = $e->getTraceAsString();

				$notification->Send ();
			}
		}
	}
}

?>
