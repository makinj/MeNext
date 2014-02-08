<?php
  require_once('constants.php');  
  function connect(){
    global $dbhost, $dbuser, $dbpass, $dbname;

    try {
      $tmp = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    } catch (PDOException $e) {
      try {
        $tmp = new PDO("mysql:host=$dbhost", $dbuser, $dbpass);
        header("Location: ../setup.php");
        exit;
      } catch (PDOException $e2) {
        echo 'Connection failed: ' . $e2->getMessage();
        exit;
      }
    }
    return $tmp;
  }

  echo"sup";
  $db=connect();
  try{
    $result = $db->query("show tables;");
  }
  catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
  }
  while ($row = $result->fetch(PDO::FETCH_NUM)) {
    var_dump($row[0]);
  }
?>