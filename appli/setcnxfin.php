<?php
require 'configuration.php';
require 'bridgette_bdd.php';
securityInt('idtournoi');
setCnxFin(securityInt('table'));
http_response_code(204);
