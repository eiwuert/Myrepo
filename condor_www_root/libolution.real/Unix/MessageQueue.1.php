<?php
	/**
	 * @package Unix
	 */

	/**
	 * Wrapper class for System V IPC message queues.
	 * NOTE: Requires the sysvipc php extension to function.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Unix_MessageQueue_1
	{
		/**
		 * @var resource
		 */
		protected $resource;

		/**
		 * @var int
		 */
		protected $max_message_size;

		/**
		 * @var int
		 */
		protected $queue_size;

		/**
		 * Returns the maximum message size
		 *
		 * @return int
		 */
		public function getMaxMessageSize()
		{
			return $this->max_message_size;
		}

		/**
		 * @param Unix_IPCKey_1 $key
		 * @param bool $flush
		 */
		public function __construct(Unix_IPCKey_1 $key, $flush = FALSE, $queue_size = NULL)
		{
			$this->resource = @msg_get_queue($key->getKey(), 0666);
			$this->max_message_size = (int)file_get_contents("/proc/sys/kernel/msgmax");
			$max_queue_size = (int)file_get_contents('/proc/sys/kernel/msgmnb');

			if (!$this->resource)
			{
				throw new Unix_MessageQueueException_1("msg_get_queue failed.");
			}

			$this->queue_size = ($queue_size !== NULL ? $queue_size : $max_queue_size);

			$opt = array(
				'msg_qbytes' => $this->queue_size
			);

			if (!@msg_set_queue($this->resource, $opt))
			{
				throw new Unix_MessageQueueException_1("msg_set_queue failed.");
			}

			if ($flush)
			{
				$this->flush();
			}
		}

		/**
		 * Destroy the message queue
		 *
		 */
		public function remove()
		{
			if($this->resource !== NULL)
			{
				$n = $this->count();
				if($n)
				{
					echo "WARNING: removing message queue with $n messages!\n";
				}

				msg_remove_queue($this->resource);
				$this->resource = NULL;
			}
		}

		/**
		 * Discards any waiting messages
		 *
		 * @return bool TRUE on success
		 */
		public function flush()
		{
			$count = $this->count();

			for ($msg = TRUE ; $count && $msg !== FALSE ; $count--)
			{
				$msg = $this->receive(0, MSG_IPC_NOWAIT);
			}

			return TRUE;
		}

		/**
		 * returns number of messages waiting in the queue
		 *
		 * @return int
		 */
		public function count()
		{
			$ms = @msg_stat_queue($this->resource);
			return $ms['msg_qnum'];
		}

		/**
		 * adds a message to the queue
		 *
		 * @param int $type integer representing message type
		 * @param mixed $msg msg to deliver
		 */
		public function send($type, $msg)
		{
			$errcode = NULL;

			if (strlen($msg) > $this->max_message_size)
			{
				throw new Unix_MessageQueueException_1("Message size ".strlen($msg)." exceeeds pre-defined system limit of " . $this->max_message_size . " bytes\n".preg_replace('/[\x00-\x1F]/', '?', $msg));
			}

			if (!msg_send($this->resource, $type, $msg, FALSE, TRUE, $errcode))
			{
				throw new Unix_MessageQueueException_1("msg_send failed $errcode --- " . print_r(msg_stat_queue($this->resource),TRUE));
			}
		}

		/**
		 * sends a variable as a message. will serialize the data before sending.
		 * Receive the message using mqRecvPackage or mqRecvPackageQuick
		 *
		 * @param int $type
		 * @param mixed $package
		 */
		public function sendPackage($type, $package)
		{
			$this->send($type, serialize($package));
		}

		/**
		 * Receive the next message in the queue of this type.
		 *
		 * @param int $type message type integer
		 * @param int $flag flags to be passed to msg_receive
		 *
		 * @return mixed
		 */
		public function receive($type, $flag = 0, &$msg_type = NULL, &$msg_error = NULL)
		{
			$msg = NULL;
			if (@msg_receive($this->resource, $type, $msg_type, $this->max_message_size, $msg, FALSE, $flag, $msg_error))
			{
				return $msg;
			}
			return FALSE;
		}


		/**
		 * Receives a message and performs unserialization on it. This will block unless specified.
		 *
		 * @param int $type
		 * @param int $flag
		 * @return mixed
		 */
		public function receivePackage($type, $flag = 0)
		{
			$msg = $this->receive($type, $flag);
			if ($msg)
				return unserialize($msg);
			return $msg;
		}

		/**
		 * Receives a message and performs unserialization on it. will return NULL if no msg available
		 *
		 * @param int $type
		 * @param int $flag
		 * @return mixed
		 */
		public function receivePackageQuick($type, $flag = 0)
		{
			$msg = $this->receiveQuick($type, $flag);
			if ($msg)
				return unserialize($msg);
			return $msg;
		}

		/**
		 * Identical to receive(), but automatically uses MSG_IPC_NOWAIT
		 *
		 * @param int $type message type integer
		 *
		 * @return mixed returns message data, or NULL if no message waiting
		 */
		public function receiveQuick($type, $flag = 0, &$msg_type = NULL, &$msg_error = NULL)
		{
			$msg = NULL;
			$flag |= MSG_IPC_NOWAIT;
			if (msg_receive($this->resource, $type, $msg_type, $this->max_message_size, $msg, FALSE, $flag, $msg_error))
			{
				return $msg;
			}
			return NULL;
		}
	}

?>
