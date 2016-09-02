<?php

  /** This class duplicates some stuff found in
   *  ECash_Models_WritableModel because libolution's
   *  DB_Models_ReferenceModel_1 needs to be refactored and I don't
   *  want * to do it. [JustinF]
   */
abstract class ECash_Models_Reference_Model extends DB_Models_ReferenceModel_1
{
    /**
     * Sets the database instance to use
     * @param DB_IConnection_1 $db
     * @return void
     */
    public function setDatabaseInstance(DB_IConnection_1 $db)
    {
        $this->db = $db;
    }

}

?>
