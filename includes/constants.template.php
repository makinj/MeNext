<?php
/*
  values frequently used by other php files
  change here and they change everywhere
*/
  define('DB_HOST', '');//hostname goes here
  define('DB_USER', '');//DB username goes here
  define('DB_PASS', '');//DB password goes here
  define('DB_NAME', 'menext');//name of DB in MYSQL
  define('PRE_SALT', "");//for security(be random)
  define('POST_SALT', "");//see above
  define('API_CLIENT_KEY', "");//youtube API key linked to MeNext for browers goes here
  define('API_SERVER_KEY', "");//youtube API key linked to MENext for servers goes here
  //-----------------------do not change----------------------
  define('FULLY_PRIVATE', 0);//unjoined users are not allowed any access
  define('VIEW_ONLY', 1);//unjoined users can view the party
  define('FULLY_PUBLIC', 2);//unjoined user can do anything
?>
