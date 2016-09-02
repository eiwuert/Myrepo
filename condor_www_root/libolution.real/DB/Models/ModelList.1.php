<?php
	/**
	 * @package DB.Models
	 */

	require_once('libolution/Object.1.php');
	require_once('libolution/DB/Models/DatabaseModel.1.php');
	require_once('libolution/DB/Models/WritableModel.1.php');
	require_once('libolution/Collections/List.1.php');

	/**
	 * class for model collection wrappers
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class DB_Models_ModelList_1 extends Collections_List_1
	{
		/**
		 * @var string
		 */
		protected $class_name;

		/**
		 * @var DB_IConnection_1
		 */
		protected $database;

		/**
		 * @param string $class_name
		 * @param DB_IConnection_1 $database
		 */
		public function __construct($class_name, DB_IConnection_1 $database)
		{
			$this->class_name = $class_name;
			$this->database = $database;

			if (!is_subclass_of($this->class_name, "DB_Models_WritableModel_1"))
			{
				throw new Exception("Class '".$this->class_name."' does not extend DB_Models_WritableModel_1!");
			}
		}

		/**
		 * overrides base functionality in List class to ensure that
		 * all of the items being added match our defined type.
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if (!($value instanceof $this->class_name))
			{
				throw new Exception("Invalid type for ModelList.");
			}
			parent::offsetSet($offset, $value);
		}

		/**
		 * Simply calls save() on all items in the list.
		 * @return void
		 */
		public function saveSimple()
		{
			foreach ($this->items as $item)
			{
				$item->save();
			}
		}

		/**
		 * insert mode: INSERT
		 */
		const INSERT_STANDARD = 1;

		/**
		 * insert mode: INSERT IGNORE
		 */
		const INSERT_IGNORE = 2;

		/**
		 * insert mode: INSERT DELAYED
		 */
		const INSERT_DELAYED = 3;

		/**
		 * @var int
		 */
		protected $insert_mode = self::INSERT_STANDARD;

		/**
		 * Override the default insert method. Normally, the type of insert performed is
		 * INSERT.
		 *
		 * DB_Models_WritableModel_1::INSERT_STANDARD : INSERT
		 * DB_Models_WritableModel_1::INSERT_DELAYED : INSERT DELAYED
		 * DB_Models_WritableModel_1::INSERT_IGNORE : INSERT IGNORE
		 *
		 * @param int $mode
		 * @return void
		 */
		public function setInsertMode($mode = self::INSERT_STANDARD)
		{
			$this->insert_mode = $mode;
		}

		/**
		 * Performs a save on all sub-items.  Will batch inserts together.
		 * Side effect of batching inserts: Won't get insert IDs.  If you need insert
		 * IDs, use saveSimple()
		 *
		 * @return void
		 */
		public function save()
		{
			if (count($this->items))
			{
				$base_item = reset($this->items);
				$columns = $base_item->getColumns();
				$auto_increment = $base_item->getAutoIncrement();

				if ($auto_increment !== NULL)
				{
					unset($columns[array_search($auto_increment, $columns)]);
				}

				if ($this->insert_mode === self::INSERT_STANDARD)
				{
					$query = "INSERT ";
				}
				else if ($this->insert_mode === self::INSERT_DELAYED)
				{
					$query = "INSERT DELAYED ";
				}
				else if ($this->insert_mode === self::INSERT_IGNORE)
				{
					$query = "INSERT IGNORE ";
				}
				else
				{
					throw new Exception("Invalid insert mode specified.");
				}

				$query .= "INTO " . $base_item->getTableName();
				$query .= " (".implode(",", $columns).") VALUES ";

				$query_piece = "(?".str_repeat(",?", count($columns) - 1).")";
				$query_data = array();
				$insert_items = array();

				foreach ($this->items as $item)
				{
					/* @var $item DB_Models_WritableModel_1 */
					if ($item->isStored())
					{
						$item->update();
					}
					else
					{
						$column_data = $item->getColumnData();
						if ($auto_increment !== NULL)
						{
							unset($column_data[$auto_increment]);
						}
						if (count($insert_items) > 0) $query .= ",";
						$query .= $query_piece;
						$query_data = array_merge($query_data, array_values($column_data));
						$insert_items[] = $item;
					}
				}

				if (count($insert_items) > 0)
				{
					$stx = $this->database->prepare($query);
					$stx->execute($query_data);
					$last_id = $this->database->lastInsertId();

					if ($auto_increment !== NULL)
					{
						$auto_increment_increment = 1;

						// Avoid unnecessary trip to the database if possible
						if (count($insert_items) > 1)
						{
							$auto_increment_increment = $this->getAutoIncrementIncrement();
						}

						for ($i = 0; $i < count($insert_items); $i++)
						{
							$insert_items[$i]->{$auto_increment} = $last_id;
							$insert_items[$i]->setDataSynched();
							$last_id += $auto_increment_increment;
						}
					}
					else
					{
						foreach ($insert_items as $item)
						{
							$item->setDataSynched();
						}
					}
				}
			}
		}

		/**
		 * Grabs info from the server regarding current auto_increment settings.
		 *
		 * WARNING: This is mysql specific
		 *
		 * @return int
		 */
		protected function getAutoIncrementIncrement()
		{
			return DB_Util_1::querySingleValue($this->database, 'select @@auto_increment_increment');
		}

		/**
		 * Returns whether the list contains the specified item.
		 * $item must be an instance of the proper type as defined in the constructor.
		 * @param mixed $item
		 * @return bool
		 */
		public function contains($item)
		{
			if (!$item instanceof $this->class_name)
			{
				throw new Exception("Invalid type for ModelList.");
			}

			$found = FALSE;

			if (count($this->items))
			{
				$pk = $this->items[0]->getPrimaryKey();
				$item_key = array_intersect_key($item->getColumnData(), $pk);

				foreach ($this->items as $match)
				{
					$match_key = array_intersect_key($match->getColumnData(), $pk);
					if ($found = (count(array_diff_assoc($item_key, $match_key)) === 0)) break;
				}
			}

			return $found;
		}
	}

?>
