<?php

class ReferenceColumn
{
    protected $class;
    protected $local_column;

    public function __construct($class)
    {
        $this->class = $class;
    }


}

class OLP_Application
{
    protected $model;
    protected $factory;
    protected $reference_columns;


    public function __construct(OLP_Factory $factory)
    {
        $this->factory = $factory;
        $this->reference_columns = array();
        $this->addReferenceTo('StatusHistory')->on('status_id');
    }

    public function __get($key)
    {
        if (is_object($this->model) && method_exists($this->model('__get')))
        {
            return $this->model->__get($key);
        }
    }

    public function __set($key, $value)
    {
        if (is_object($this->model) && method_exists($this->model('__set')))
        {
            return $this->model->__set($key, $value);
        }
    }

    public function addReferenceTo()
    {

    }
}