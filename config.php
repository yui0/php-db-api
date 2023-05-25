<?php
// Copyright © 2023 Yuichiro Nakada

return [
 'database' => 'data/data.db',
 'algorithm' => 'HS512',
 'secret' => 'secret key is here',
 'auth_table' => [ // login required
   ['table' => 'users', 'method' => 'GET,POST,DELETE'],
   ['table' => 'auth', 'method' => 'GET,PUT,POST,DELETE'],
 ],
];

?>
