<?php
require 'vendor/autoload.php';
require_once('config.php');

$dbconn = pg_connect("host=$host dbname=$dbname user=$user password=$password")
or die('No se ha podido conectar: ' . pg_last_error());

// Realizando una consulta SQL
$query = "delete
from stage_sensor_data";

$result = pg_query($query) or die('La consulta fallo: ' . pg_last_error());

pg_query("COMMIT") or die("Transaction commit failed\n");

// Liberando el conjunto de resultados
pg_free_result($result);

// Cerrando la conexión
pg_close($dbconn);
?>