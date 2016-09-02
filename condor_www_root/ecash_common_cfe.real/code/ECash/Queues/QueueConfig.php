<?php

	/**
	 * Class for managing the configuration for a queue.
	 * Currently this is a little lax with database access.
	 *
	 * @todo Maybe make this use some kind of static cache of all queue configs that's populated once.
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_QueueConfig extends Object_1 //ECash_Queues_QueueBase
	{
		/**
		 * @var ECash_Models_QueueConfig
		 */
		protected $model;

		/**
		 * @var array
		 */
		protected $config;

		/**
		 * @var bool
		 */
		protected $is_loaded;

		/**
		 * @var ECash_Queues_BasicQueue
		 */
		protected $parent_queue;

		/**
		 * @var array
		 */
		protected $default_config;

		/**
		 * @param ECash_Queue $queue
		 */
		public function __construct(ECash_Queues_BasicQueue $queue)
		{
			$this->parent_queue = $queue;
		}

		/**
		 * loads from database
		 */
		protected function load()
		{
			$this->model = ECash::getFactory()->getModel('QueueConfigList');
			$this->model->loadByQueueId($this->parent_queue->Model->queue_id);
			$this->config = array();
			$this->default_config = array();

			foreach ($this->model as $queue_config)
			{
				if ($queue_config->queue_id != 0)
				{
					$this->config[$queue_config->config_key] = $queue_config;
				}
				else
				{
					$this->default_config[$queue_config->config_key] = $queue_config;
				}
			}
			$this->is_loaded = TRUE;
		}

		/**
		 * Returns a config variable.
		 *
		 * @param string $config_key
		 * @return mixed
		 */
		public function getValue($config_key)
		{
			if ($this->isValueSpecified($config_key))
			{
				return $this->config[$config_key]->config_value;
			}
			else if (isset($this->default_config[$config_key]))
			{
				return $this->default_config[$config_key]->config_value;
			}
			else
			{
				throw new ECash_Queues_QueueException("Unable to find queue configuration key '$config_key'");
			}
		}

		/**
		 * Sets a config variable. Writes to database immediately.
		 *
		 * @param string $config_key
		 * @param string $config_value
		 * @return bool
		 */
		public function setValue($config_key, $config_value)
		{
			if ($this->isValueSpecified($config_key))
			{
				$this->config[$config_key]->config_value = $config_value;
			}
			else
			{
				$model = ECash::getFactory()->getModel('QueueConfig');
				$model->queue_id = $this->parent_queue->Model->queue_id;
				$model->config_key = $config_key;
				$model->config_value = $config_value;
				$this->config[$config_key] = $model;
			}

			$this->config[$config_key]->save();
		}

		/**
		 * deletes the given config key.
		 *
		 * @param unknown_type $config_key
		 */
		public function deleteValue($config_key)
		{
			if ($this->isValueSpecified($config_key))
			{
				$this->config[$config_key]->delete();
				unset($this->config[$config_key]);
			}
		}

		/**
		 * Returns whether this queue config has a specific setting.
		 * Note: This will return FALSE even if a default config setting
		 * is inherited
		 *
		 * @param string $config_key
		 * @return bool
		 */
		public function isValueSpecified($config_key)
		{
			if (!$this->is_loaded)
			{
				$this->load();
			}
			return isset($this->config[$config_key]);
		}
	}
?>
