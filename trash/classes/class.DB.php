<?php


class DB
{

  private $_db;

  public function __construct($db=NULL)
  {
    if(is_object($db))
    {
      $this->_db = $db;
    }
    else
    {
      require($_SERVER['DOCUMENT_ROOT']."/includes/base.php");
      $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME;
      $this->_db = new PDO($dsn, DB_USER, DB_PASS);
    }
  }
}

?>