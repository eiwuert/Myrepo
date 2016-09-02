<?php

	/**
	 * Company business object
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Company extends Object_1
	{
		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var ECash_Models_Reference_Company
		 */
		protected $model;

		/**
		 * @param DB_IConnection_1 $db
		 * @param int $company_id
		 */
		public function __construct(DB_IConnection_1 $db, $company_id)
		{
			$this->db = $db;
			$this->company_id = $company_id;
		}

		/**
		 * @return ECash_Models_Reference_Company
		 */
		public function getModel()
		{
			if ($this->model === NULL)
			{
				$company_list = ECash::getFactory()->getReferenceList('Company', $this->db);
				$this->model = $company_list[$this->company_id];
			}

			return $this->model;
		}


		/**
		 * @depricated
		 */
		public function getCompanyId()
		{
			return $this->getId();
		}

		/**
		 * Returns the company_id for this Company
		 *
		 * @return int
		 */
		public function getId()
		{
			return $this->company_id;
		}
		/**
		 * "magic method" for processing getting a property on the company model
		 *
		 * @param string $property_name
		 * @return mixed
		 */
		public function __get($property_name)
		{
			if (in_array($property_name, $this->getModel()->getColumns()))
			{
				return $this->getModel()->$property_name;
			}
			else
			{
				return parent::__get($property_name);
			}
		}
		/**
		 * "magic method" for checking if a property isset on the company model
		 *
		 * @param string $property_name
		 * @return bool
		 */
		public function __isset($property_name)
		{
			if (in_array($property_name, $this->getModel()->getColumns()))
			{
				return $this->getModel()->$property_name != NULL;
			}
			else
			{
				return parent::__isset($property_name);
			}
		}
	}

?>