<?php                                                                                                                                   

/*
 * This file was automatically generated by generate_writable_model.php
 * at 2009-03-24, 06:44:35 from 'mysql:host=reporting.olp.ept.tss;port=3306;dbname=olp'.
 *                                                                                      
 * NOTE: Modifications to this file will be overwritten if/when                         
 * it is regenerated.                                                                   
 *                                                                                      
 */                                                                                     
class OLP_StatusHistory extends DB_Models_WritableModel_1                               
{                                                                                       
        /**                                                                             
         * The list of columns this model contains                                      
         * @return array string[]                                                       
         */                                                                             
        public function getColumns()                                                    
        {                                                                               
                static $columns = array(                                                
                        'status_history_id', 'application_id',                          
                        'application_status_id', 'date_created'                         
                );                                                                      
                return $columns;                                                        
        }                                                                               

        /**
         * An array of the columns that comprise the primary key
         * @return array string[]                               
         */                                                     
        public function getPrimaryKey()                         
        {                                                       
                return array('status_history_id');              
        }                                                       

        /**
         * The auto increment column, if any
         * @return string|void              
         */                                 
        public function getAutoIncrement()  
        {                                   
                return 'status_history_id'; 
        }                                   

        /**
         * Indicates the table name
         * @return string
         */
        public function getTableName()
        {
                return 'status_history';
        }

        /**
         * Gets the column data for updating/insertion
         *
         * This is used to perform per-column transformations as the data goes into the database.
         *
         * @return array
         */
        public function getColumnData()
        {
                $column_data = parent::getColumnData();
                $column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
                return $column_data;
        }

        /**
         * Sets the column data in the model
         *
         * This is used to perform per-column transformation as the data comes from the database
         *
         * @return void
         */
        protected function setColumnData($column_data)
        {
                $column_data['date_created'] = strtotime($column_data['date_created']);
                parent::setColumnData($column_data);
        }
}

?>