<?php
#session_start();
header("Content-Type: text/xml");

echo "<" . "?xml version='1.0' encoding='UTF-8' standalone='no' ?" . ">";

if ( !isset($_REQUEST['secret_key']) || $_REQUEST['secret_key'] != 'spectrum_web_responder_secret_key' ) {
 forward(133);
 exit;
}

if ( isset($_REQUEST['NmsAni']) && $_REQUEST['NmsAni'] != '' ) {
 $ani = $_REQUEST['NmsAni'];
 $calling_npa = substr($ani, 0, 3);
} else {
 $ani = 'default';
 $calling_npa = 'default';
}

if ( isset($_REQUEST['NmsDnis']) && $_REQUEST['NmsDnis'] != '' ) {
 $dnis = $_REQUEST['NmsDnis'];
 $our_did = $dnis;
} else {
 $dnis = 'default';
 $our_did = 'default';
}

# Set up all your routes as in $routes[did][calling_npa]

$routes['default']['default'] = 100;
$routes['default'][214] = 101;
$routes['default'][415] = 102;
$routes['default'][972] = 103;

$routes[4694292500]['default'] = 200;
$routes[4694292500][214] = 201;
$routes[4694292500][415] = 202;
$routes[4694292500][972] = 203;

$routes[4694292501]['default'] = 300;
$routes[4694292501][214] = 301;
$routes[4694292501][415] = 301;
$routes[4694292501][972] = 303;

if ( isset($routes[$our_did][$calling_npa]) ) {
 $location = $routes[$our_did][$calling_npa];
} elseif ( isset($routes[$our_did]['default'])  ) {
 $location = $routes[$our_did]['default'];
} else {
 $location = $routes['default']['default'];
}

forward($location);

# Output the Forward element
function forward($location)
{
  echo "<Forward >$location</Forward>";
}

?>
