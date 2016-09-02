<?php
/**
 * Base class for SOAP Model service client calls
 * 
 * @author Todd Huish <toddh@sellingsource.com>
 * @package WebService
 * 
 * @todo - modifying calls should probably in the future uniformly pass agent_id
 */
abstract class WebServices_Client_SOAPModelClient extends WebServices_Client
{
	/**
	 * Save a SOAP Model
	 * 
	 * @param object $dto
	 * @return mixed
	 */
	public function save($dto)
	{
		$retval = FALSE;

		if (!$this->getModelService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getModelService()->save($dto);

		return $retval;
	}
	
	/**
	 * Delete a SOAP Model row
	 * 
	 * @param int $id
	 * @return int affected rows
	 */
	public function deleteById($id)
	{
		$retval = FALSE;

		if (!$this->getModelService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getModelService()->deleteById($id);

		return $retval;
	}

	/**
	 * Find and load a model by SOAP
	 * 
	 * @param object $qdto
	 * @param int $limit
	 * @param object $obdto
	 * @return mixed
	 */
	public function find($qdto,$limit,$obdto)
	{
		$retval = FALSE;

		if (!$this->getModelService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getModelService()->find($qdto,$limit,$obdto);

		return $retval;
	}
}

?>
