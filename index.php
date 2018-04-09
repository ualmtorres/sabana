<?php
require 'vendor/autoload.php';

$connection = new MongoDB\Client;
$collection = $connection->sabana->medidasphp;

// Instantiate the class responsible for implementing a micro application
$app = new \Phalcon\Mvc\Micro();
// Routes
$app->get('/api/sensor/{sensor}/time/{theTime}', 'getMeasuresBySensorAndTime');
$app->get('/api/sensor/{sensor}/fromtime/{fromTheTime}/totime/{toTheTime}', 'getMeasuresBySensorAndTimePeriod');
$app->get('/api/sensor/{sensor}/day/{theDay}', 'getMeasuresBySensorAndDay');
$app->get('/api/sensor/{sensor}/day/{theDay}/aggregated', 'getMeasuresBySensorAndDayAggregated');

$app->get('/say/date', 'currentDate');
$app->get('/say/hello/{name}', 'greeting');
$app->notFound('notFound');
// Handlers

function getMeasuresBySensorAndTime($sensor, $theTime) {  
  global $collection;
  
  $result = $collection->find(array('sensor' => $sensor, 'lahora' => $theTime )) or die ("Error al consultar");
 
  foreach ($result as $document) {
    $response = array(
      "sensor" => $sensor,
      "time" => $theTime,
      "min" => $document["min"],
      "max" => $document["max"],
      "avg" => $document["avg"]
    );

    header('Content-Type: application/json');
    return json_encode($response, JSON_NUMERIC_CHECK);
  }
}

function getMeasuresBySensorAndTimePeriod($sensor, $fromTheTime, $toTheTime) {  
  global $collection;
  $response = [];
  
  $result = $collection->find(['sensor' => $sensor, 'lahora' => ['$gte' => $fromTheTime, '$lte' => $toTheTime] ]) or die ("Error al consultar");

  foreach ($result as $document) {
    $line = array(
      "sensor" => $sensor,
      "time" => $document["lahora"],
      "min" => $document["min"],
      "max" => $document["max"],
      "avg" => $document["avg"]
    );

    $response[] = $line; // Push line into response
  }
  header('Content-Type: application/json');
  return json_encode($response, JSON_NUMERIC_CHECK);
}

function getMeasuresBySensorAndDay($sensor, $theDay) {  
  global $collection;
  $response = [];
  
  $result = $collection->find(['sensor' => $sensor, 'lahora' => ['$gte' => $theDay . '0000', '$lte' => $theDay . '2359'] ]) or die ("Error al consultar");

  foreach ($result as $document) {
    $line = array(
      "sensor" => $sensor,
      "time" => $document["lahora"],
      "min" => $document["min"],
      "max" => $document["max"],
      "avg" => $document["avg"]
    );

    $response[] = $line; // Push line into response
  }
  header('Content-Type: application/json');
  return json_encode($response, JSON_NUMERIC_CHECK);
}

function getMeasuresBySensorAndDayAggregated($sensor, $theDay) {  
  global $collection;

  $result = $collection->aggregate([['$match' => ['sensor' => $sensor,
                                                  'lahora' => ['$gte' => $theDay . '0000', '$lte' => $theDay . '2359']]
                                    ],
                                    ['$group' => ['_id' => null,
                                                  'min' => ['$min' => '$min'],
                                                  'max' => ['$max' => '$max'],
                                                  'avg' => ['$avg' => '$avg']
                                                  ]
                                    ]
                                                  
                                  ]) or die ("Error al consultar");

  foreach ($result as $document) {
    $response = array(
      "sensor" => $sensor,
      "time" => $theDay,
      "min" => $document["min"],
      "max" => $document["max"],
      "avg" => $document["avg"]
    );
  }
  header('Content-Type: application/json');
  return json_encode($response, JSON_NUMERIC_CHECK);
  
}

function currentDate() {
  echo date('Y-m-d');
}
function greeting($name) {
  $response = array("greeting" => "Hello $name");
  echo json_encode($response);
}
function notFound() {
    // Access to the global var $app
    global $app;
    
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo 'Oops, Not Found!!';
}
// Handle the request
$app->handle();
?>