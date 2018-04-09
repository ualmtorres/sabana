<?php
require 'vendor/autoload.php';
require_once('config.php');

$connection = new MongoDB\Client;
$collection = $connection->sabana->medidasphp;

$dbconn = pg_connect("host=$host dbname=$dbname user=$user password=$password")
or die('No se ha podido conectar: ' . pg_last_error());

// Realizando una consulta SQL
$query = "select *
from 
(select
lahoraenminutos,
json_object_agg (lahoraensegundos, co2ext) as dataco2ext,
min( cast( co2ext as double precision)) as minco2ext,
max( cast( co2ext as double precision)) as maxco2ext,
avg( cast( co2ext as double precision)) as avgco2ext,
json_object_agg (lahoraensegundos, dirviento) as datadirviento,
min( cast( dirviento as double precision)) as mindirviento,
max( cast( dirviento as double precision)) as maxdirviento,
avg( cast( dirviento as double precision)) as avgdirviento

from (select 	to_char(to_timestamp(cast (thetime as double precision)) AT TIME ZONE 'UTC', 'YYYYMMDDHH24MI') as lahoraenminutos,
        to_char(to_timestamp(cast (thetime as double precision)) AT TIME ZONE 'UTC', 'YYYYMMDDHH24MISS') as lahoraensegundos,
*
from stage_sensor_data) aux
group by lahoraenminutos) t";
$result = pg_query($query) or die('La consulta fallo: ' . pg_last_error());

// Imprimiendo los resultados en HTML
while ($line = pg_fetch_object($result)) {
    $document = array( 
        'sensor' => 'co2ext', 
        'lahora' => $line->lahoraenminutos, 
        'data' => json_decode($line->dataco2ext), 
        'min' => (float) $line->minco2ext, 
        'max' => (float) $line->maxco2ext, 
        'avg' => (float) $line->avgco2ext
    );
    $collection->insertOne($document);

    $document = array( 
        'sensor' => 'dirviento', 
        'lahora' => $line->lahoraenminutos, 
        'data' => json_decode($line->datadirviento), 
        'min' => (float) $line->mindirviento, 
        'max' => (float) $line->maxdirviento, 
        'avg' => (float) $line->avgdirviento
    );
    $collection->insertOne($document);
}

// Liberando el conjunto de resultados
pg_free_result($result);

// Cerrando la conexión
pg_close($dbconn);

?>