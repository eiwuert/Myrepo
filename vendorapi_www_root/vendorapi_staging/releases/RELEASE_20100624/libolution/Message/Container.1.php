<?php
/**
 * @package Message
 */

/**
 * A standard shipping container
 *
 * @example examples/message.php
 * @author Rodric Glaser <rodric.glaser@sellingsource.com>
 */
class Message_Container_1 extends Object_1
{
	/**
	 * Unique key per message
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Message Source
	 *
	 * @var string
	 */
	protected $src;

	/**
	 * Message Destination
	 *
	 * @var string
	 */
	protected $dst;

	/**
	 * Message Headers
	 *
	 * @var Collections_List_1
	 */
	protected $head;

	/**
	 * Message Body
	 *
	 * @var mixed
	 */
	protected $body;

	/**
	 * Created timestamp
	 *
	 * @var int
	 */
	protected $ctime;

	/**
	 * Create a new Message
	 *
	 * @param string $src Source
	 * @param string $dst Destination
	 */
	public function __construct($src, $dst, $body = NULL, array $head = NULL)
	{
		$this->key = Util_Guid_1::newId();
		$this->src = $src;
		$this->dst = $dst;
		$this->head = new Collections_List_1();
		if ($body !== NULL)
		{
			$this->setBody($body);
		}
		if ($head !== NULL)
		{
			foreach ($head as $k => $v)
			{
				$this->head[$k] = $v;
			}
		}
		$this->ctime = time();
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getSrc()
	{
		return $this->src;
	}

	/**
	 * @return string
	 */
	public function getDst()
	{
		return $this->dst;
	}

	/**
	 * @return Collections_List_1
	 */
	public function getHead()
	{
		return $this->head;
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
		return unserialize(gzuncompress($this->body));
	}

	/**
	 * @param mixed $val
	 */
	public function setBody($val)
	{
		$this->body = gzcompress(serialize($val));
	}

	/**
	 * @return int
	 */
	public function getTimeCreated()
	{
		return $this->ctime;
	}
}

?>
