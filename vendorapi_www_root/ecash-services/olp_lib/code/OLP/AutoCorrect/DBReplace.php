<?php

/**
 * Simple replacement of words, based on a database connection.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_DBReplace extends OLP_AutoCorrect_Replace
{
	/**
	 * @var DB_Models_WritableModel_1
	 */
	protected $model;
	
	/**
	 * @var array
	 */
	protected $where;
	
	/**
	 * @var string
	 */
	protected $model_original_name;
	
	/**
	 * @var string
	 */
	protected $model_replacement_name;
	
	/**
	 * Sets up the database-sourced replacement autocorrection system.
	 *
	 * @param DB_Models_WritableModel_1 $model
	 * @param array $where
	 * @param string $model_original_name
	 * @param string $model_replacement_name
	 */
	public function __construct(DB_Models_WritableModel_1 $model, array $where = array(), $model_original_name = 'original_word', $model_replacement_name = 'replacement_word')
	{
		parent::__construct(array());
		
		$this->model = $model;
		$this->where = $where;
		$this->model_original_name = $model_original_name;
		$this->model_replacement_name = $model_replacement_name;
		
		// Assert that the model is 'safe' to use
		$columns = $model->getColumns();
		if (!in_array($model_original_name, $columns)
			|| !in_array($model_replacement_name, $columns)
		)
		{
			throw new InvalidArgumentException("Model does not implement the necessary columns to be able to auto correct words.");
		}
	}
	
	/**
	 * Gets the replacement word for this word.
	 *
	 * @param string $word
	 * @return string
	 */
	protected function getReplacementWord($word)
	{
		$replacement = parent::getReplacementWord($word);
		
		if ($replacement === NULL)
		{
			$where = $this->where;
			$where[$this->model_original_name] = $word;
			
			if ($this->model->loadBy($where))
			{
				$replacement = $this->model->__get($this->model_replacement_name);
			}
			else
			{
				$replacement = FALSE;
			}
			
			$this->replacement_data[$word] = $replacement;
		}
		
		return $replacement;
	}
}

?>
