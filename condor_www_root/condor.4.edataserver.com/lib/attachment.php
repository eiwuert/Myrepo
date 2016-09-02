<?php
/**
 * This class represents an attachment within a Condor document. The attachment could be
 * file data or another document.
 *
 * @author Brian Feaver
 */
class Attachment
{
	private $uri;
	private $content_type;
	private $data;
	private $type;
	private $archive_id;
	private $part_id;
	private $attachments;
	
	const TYPE_DOCUMENT = 0;
	const TYPE_FILE = 1;
	
	/**
	 * Attachment constructor
	 *
	 * @param mixed $data
	 * @param int $type
	 * @param string $uri
	 * @param string $content_type
	 */
	function __construct($data, $type, $uri = '', $content_type = '')
	{
		$this->data = $data;
		$this->type = $type;
		$this->uri = $uri;
		$this->content_type = $content_type;
		$this->attachments = array();
	}
	
	/**
	 * Returns the attachment and its attachments as an array.
	 *
	 * @return array
	 */
	public function As_Array()
	{
		$attachment_list = array();
		
		if(!empty($this->attachments))
		{
			foreach($this->attachments as $attachment)
			{
				$attachment_list[] = $attachment->As_Array();
			}
		}
		
		$ret_val = array(
			'data' => $data,
			'content_type' => $this->content_type,
			'uri' => $this->uri,
			'attachments' => $attachment_list
		);
		
		return $ret_val;
	}
	
	/**
	 * Returns the content ID of the attachment.
	 *
	 * @return string
	 */
	public function Get_URI()
	{
		return $this->uri;
	}
	
	/**
	 * Returns the data object, whether it be actual binary data, text, or a
	 * Document object.
	 *
	 * @return mixed
	 */
	public function Get_Data()
	{
		return $this->data;
	}
	
	/**
	 * Returns the type of the attachment. Currently this is either a DOCUMENT or a FILE.
	 *
	 * @return int
	 */
	public function Get_Type()
	{
		return $this->type;
	}
	
	/**
	 * Returns the content type of the attachment.
	 *
	 * @return string
	 */
	public function Get_Content_Type()
	{
		return $this->content_type;
	}
	
	/**
	 * Retrieves attached data to this attachment. This will only really apply if the
	 * attachment is a document.
	 *
	 * @return array
	 */
	public function Get_Attached_Data()
	{
		$ret_val = array();
		
		foreach($this->attachments as $file)
		{
			$new_doc = new stdClass();
			$new_doc->data = $file->Get_Data();
			$new_doc->uri = $file->Get_URI();
			$new_doc->content_type = $file->Get_Content_Type();
			$new_doc->attached_data = $file->Get_Attached_Data();
			
			$ret_val[] = $new_doc;
		}
		
		return $ret_val;
	}
	
	/**
	 * Returns an array of part ID's.
	 *
	 * @return array
	 */
	public function Get_Part_Ids()
	{
		$ret_val = array();
		
		$ret_val[] = $this->part_id;
		
		foreach($this->attachments as $attachment)
		{
			$ret_val = array_merge($ret_val, $attachment->Get_Part_Ids);
		}
		
		return $ret_val;
	}
}
?>
