<?php
	/**
	 * Represents a writable database row
	 *
	 * @author Todd Huish <todd.huish@sellingsource.com>
	 * @package DB.Models
	 */
	interface DB_Models_IWritableModel_1 extends DB_Models_IBaseModel
	{
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
		 * insert mode: INSERT ... ON DUPLICATE KEY UPDATE
		 */
		const INSERT_ON_DUPLICATE_KEY_UPDATE = 4;

		/**
		 * Override this method with one that returns an array of valid
		 * column names.
		 *
		 * @return array
		 */
		public function getColumns();
		/**
		 * Override this method with one that returns a string containing
		 * the name of your table.
		 *
		 * @return string
		 */
		public function getTableName();
		/**
		 * Override this method to return an array containing the primary key
		 * column(s) for your table.
		 *
		 * @return array
		 */
		public function getPrimaryKey();
		/**
		 * Override this method with one that returns the name of the auto_increment
		 * column in your table. Return NULL if your table does not contain an
		 * auto_increment column.
		 *
		 * @return string
		 */
		public function getAutoIncrement();

		/**
		 * Whether or not this row has been stored.
		 *
		 * @return bool
		 */
		public function isStored();

		/**
		 * Whether this row has been altered.
		 *
		 * @return bool
		 */
		public function isAltered();
		
		/**
		 * Creates a new ready to be inserted model.
		 * 
		 * Any delete flags will be removed, all data will be marked as altered
		 * and if there is an auto increment column it will be set to NULL. Any
		 * call to save on the resultant object will result in the the model 
		 * being reinserted with a new id.
		 *
		 * @return DB_Models_WritableModel_1
		 */
		public function copy();

		/**
		 * Override the default insert method. Normally, the type of insert performed is
		 * INSERT.
		 *
		 * @param int $mode
		 * @return void
		 */
		public function setInsertMode($mode = INSERT_STANDARD);

		/**
		 * Inserts current row data into the database and returns the affected row count
		 *
		 * NOTE: Currently you have the possibility of getting incorrect
		 * auto increment IDs if you have addition unique indexes and use
		 * any of the non-vanilla insert modes. Because there is no
		 * straight-forward, catch-all approach to solving the potential
		 * issues, the onus is on you to handle them as best suits
		 * your application. Here are some suggestions:
		 *
		 *   - INSERT ... ON DUPLICATE KEY UPDATE: if insert() returns an
		 *      affected row count of 0 or 2(?!), you should run a loadBy
		 *      on your unique index to fetch the proper ID
		 *   - INSERT DELAYED: you'll never get an auto increment ID, so
		 *      don't use this if you need to get the ID back immediately
		 *   - INSERT IGNORE: if insert() returns an affected row count
		 *      of 0, loadBy on the unique index to get the ID
		 *
		 * @throws Exception
		 * @return int
		 */
		public function insert();

		/**
		 * Updates this row in the database using the primary key currently stored
		 * and returns the affected row count
		 * @todo You can't change a value that's part of the primary key!
		 * @throws Exception
		 * @return int
		 */
		public function update();

		/**
		 * Takes an associative array (presumably from a database select)
		 * and loads the current object with the data.
		 *
		 * @throws Exception
		 * @param array $db_row Associative data array
		 * @param string $column_prefix prefix (if any) to the column names in the array
		 * @return void
		 */
		public function fromDbRow(array $db_row, $column_prefix = '');

		/**
		 * Gets column data ready for the database
		 * NOTE: DO NOT USE.
		 *
		 * @internal
		 * @return array
		 */
		public function getColumnData();

		/**
		 * Returns current changes to the model
		 * @return array
		 */
		public function getAlteredColumnData();

		/**
		 * internal method used to reset the altered columns state
		 * this should be called any time we're sure our data is
		 * identical to what is in the database.
		 *
		 * @internal
		 * @return void
		 */
		public function setDataSynched();

		/**
		 * Loads a row by the primary key
		 *
		 * Takes a variable number of arguments... Note that the
		 * of arguments to this function MUST be in the order
		 * returned by getPrimaryKey()
		 *
		 * @param mixed $key
		 * @return bool
		 */
		public function loadByKey($key);

		/**
		 * gets the affected row count from the last save().  Should
		 * be NULL if save() was called but nothing changed on the
		 * model
		 *
		 * @return int
		 */
		public function getAffectedRowCount();

		/**
		 * Sets the readonly state of the model.
		 *
		 * @param boolean $state
		 * @return void
		 */
		public function setReadOnly($state=FALSE);

		/**
		 * Gets the readonly state of the model.
		 * @return bool
		 */
		public function getReadOnly();

		/**
		 * Flag this model to be deleted when save() is called
		 *
		 * @param bool $delete
		 * @return void
		 */
		public function setDeleted($delete);

		/**
		 * Gets whether or not this model is scheduled for deletion
		 * @return bool
		 */
		public function getDeleted();
		
		/**
		 * Dangerous function written to remove the Vendor API State Object Persistor hack
		 *
		 * @param array $data
		 * @return void
		 */
		public function setModelData(array $data);
	}

?>
