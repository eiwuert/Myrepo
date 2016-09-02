<?php
	/**
	 * @package Util
	 */

	/**
	 * Allows you to iterate a CSV file, similar to a database result set.
	 * @sample
	 * <code>
	 *   class MyCSVFile extends Util_CSVIterator_1
	 *   {
	 *     protected function getColumns() { return array('name', 'ssn', 'address'); }
	 *   }
	 *
	 *   $csv = new MyCSVFile('/tmp/test.csv');
	 *   foreach ($csv as $record)
	 *   {
	 *     echo 'Name: '.$record['name']."\n";
	 *   }
	 * </code>
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	abstract class Util_CSVIterator_1 implements Iterator
	{
		/**
		 * @var resource
		 */
		protected $fp;

		/**
		 * @var string
		 */
		protected $delimiter = ',';

		/**
		 * @var sting
		 */
		protected $enclosure = '"';

		/**
		 * @var array
		 */
		protected $columns;

		/**
		 * @var int
		 */
		protected $column_count;

		/**
		 * @var array
		 */
		protected $current;

		/**
		 * @var int
		 */
		protected $key = -1;

		/**
		 * Construct a new CSV iterator. Accepts a filename or file descriptor.
		 * @param mixed $file filename or descriptor
		 */
		public function __construct($file)
		{
			if (is_resource($file))
			{
				$this->fp = $file;
			}
			elseif (!is_string($file)
				|| ($this->fp = fopen($file, 'r')) === FALSE)
			{
				throw new Exception('Could not open file');
			}

			$this->columns = $this->getColumns();
			$this->column_count = count($this->columns);
		}

		/**
		 * The implementation must return an array of column names, which
		 * will become the keys for the CSV records.
		 * @return array
		 */
		abstract protected function getColumns();

		/**
		 * Required by Iterator; rewinds to the beginning of the file.
		 * @return void
		 */
		public function rewind()
		{
			fseek($this->fp, 0);
			$this->key = 0;

			$this->next();
		}

		/**
		 * Required by Iterator; returns the current record (or FALSE).
		 * @return mixed
		 */
		public function current()
		{
			return $this->current;
		}

		/**
		 * Required by Iterator; moves to and returns the next record (or FALSE).
		 * @return mixed
		 */
		public function next()
		{
			if (($row = fgetcsv($this->fp, 8192, $this->delimiter, $this->enclosure)) !== FALSE)
			{
				$this->key++;
				$c = count($row);

				if ($c < $this->column_count)
				{
					throw new Exception('Invalid row: column count does not match definition ('.$this->key.')');
				}
				elseif ($c > $this->column_count)
				{
					$row = array_slice($row, 0, $this->column_count);
				}

				return ($this->current = array_combine($this->columns, $row));
			}

			return ($this->current = FALSE);
		}

		/**
		 * Required by Iterator; returns whether a current item is available.
		 * @return bool
		 */
		public function valid()
		{
			return ($this->current !== FALSE);
		}

		/**
		 * Required by Iterator; returns the current record offset (1-based).
		 * @return int
		 */
		public function key()
		{
			return $this->key;
		}

		/**
		 * Iterates the entire file and returns an instance of Collections_List_1 that
		 * contains all of the rows..
		 * @return Collections_List_1
		 */
		public function toList()
		{
			$list = new Collections_List_1();

			foreach ($this as $item)
			{
				$list[] = $item;
			}

			return $list;
		}
	}

?>
