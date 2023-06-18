<?php
// Copyright © 2023 Yuichiro Nakada

return [
 'database' => 'data/data.db',
 'algorithm' => 'HS512',
 'secret' => 'secret key is here',
 'use_jwt' => false,
 'records' => 'records',
 'noauth' => [ // all table is required login
   ['table' => 'login', 'method' => 'POST'], // always need
   ['table' => 'users', 'method' => 'PUT,POST'], // FIXME: POST
   ['table' => 'population', 'method' => 'GET,PUT,POST,DELETE'],
 ],
 'auth' => [ // access right required
   ['table' => 'users', 'method' => 'GET,DELETE', 'user' => 'admin'],
 ],
];

?>
