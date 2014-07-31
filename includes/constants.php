<?php
/*
  values frequently used by other php files
  change here and they change everywhere
*/
  define('DB_HOST', 'localhost');//hostname
  define('DB_USER', 'root');//DB username
  define('DB_PASS', '');//DB password
  define('DB_NAME', 'menext');//name of DB in MYSQL
  define('PRE_SALT', "5W4GG#d0pe&&*");//for security(be random)
  define('POST_SALT', "UILU#do114b111zzz@#)");//see above
  define('API_CLIENT_KEY', "AIzaSyCIDavzeJA_rfK90XD3O2o5JRyIMOFyUvM");//youtube API key linked to MeNext for browers
  define('API_SERVER_KEY', "AIzaSyCEv0gqKmUOHgMKP-xf0BMke-VGYd-zWKQ");//youtube API key linked to MENext for servers
  define('FULLY_PRIVATE', 0);//unjoined users are not allowed any access
  define('VIEW_ONLY', 1);//unjoined users can view the party
  define('FULLY_PUBLIC', 2);//unjoined user can do anything
?>
