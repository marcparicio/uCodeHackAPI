<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');

include_once './include/Config.php';

require './vendor/slim/slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
/* Usando GET para consultar los autos */
$app->post('/lel', function () use ($app){
	//$param['perfil'] = $app->request->post('perfil');
	$response = array();
	$nombre_archivo="logs.txt";
	if(isset($_FILES['image'])){
		$response['msg']= "true";
	}else{
		$response['msg']= "false";
	}
	$target_dir = "img/";
	$target_file = $target_dir . basename($_FILES["image"]["name"]);
	if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $command = escapeshellcmd('test.py');
		$output = shell_exec($command);
	    //$image = @file_get_contents("/img/JPEG_20180310_121929_680701030.jpg");
		$path = 'img/nike.jpg';
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = base64_encode($data);
	    //$response->write($image);
	    echo json_encode($base64);
    	//echo $response->withHeader('Content-Type', FILEINFO_MIME_TYPE);
    }
	 if($archivo = fopen($nombre_archivo, "a"))
    {
        if(fwrite($archivo, date("d m Y H:m:s"). " ". json_encode($base64). "\n"))
        {
            echo "Se ha ejecutado correctamente";
        }
        else
        {
            echo "Ha habido un problema al crear el archivo";
        }
 
        fclose($archivo);
    }

	//$output = exec('test.py');
	//echo $output;
	//echoResponse(200, $response);
 
});
$app->get('/auto', function () {

$response = array();

$autos = array(
array('make'=>'Toyota', 'model'=>'Corolla', 'year'=>'2006', 'MSRP'=>'18,000'),
array('make'=>'Nissan', 'model'=>'Sentra', 'year'=>'2010', 'MSRP'=>'22,000')
);

$response["error"] = false;
$response["message"] = "Autos cargados: " . count($autos); //podemos usar count() para conocer el total de valores de un array
$response["autos"] = $autos;

echoResponse(200, $response);
});

/* Usando POST para crear un auto */

$app->post('/auto', 'authenticate', function() use ($app) {
// check for required params
verifyRequiredParams(array('make', 'model', 'year', 'msrp'));

$response = array();
//capturamos los parametros recibidos y los almacxenamos como un nuevo array
$param['make'] = $app->request->post('make');
$param['model'] = $app->request->post('model');
$param['year'] = $app->request->post('year');
$param['msrp'] = $app->request->post('msrp');

/* Podemos inicializar la conexion a la base de datos si queremos hacer uso de esta para procesar los parametros con DB */
//$db = new DbHandler();

/* Podemos crear un metodo que almacene el nuevo auto, por ejemplo: */
//$auto = $db->createAuto($param);

if ( is_array($param) ) {
$response["error"] = false;
$response["message"] = "Auto creado satisfactoriamente!";
$response["auto"] = $param;
} else {
$response["error"] = true;
$response["message"] = "Error al crear auto. Por favor intenta nuevamente.";
}
echoResponse(201, $response);
});

/* corremos la aplicación */
$app->run();

/*********************** USEFULL FUNCTIONS **************************************/

/**
* Verificando los parametros requeridos en el metodo o endpoint
*/
function verifyRequiredParams($required_fields) {
$error = false;
$error_fields = "";
$request_params = array();
$request_params = $_REQUEST;
// Handling PUT request params
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
$app = \Slim\Slim::getInstance();
parse_str($app->request()->getBody(), $request_params);
}
foreach ($required_fields as $field) {
if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
$error = true;
$error_fields .= $field . ', ';
}
}

if ($error) {
// Required field(s) are missing or empty
// echo error json and stop the app
$response = array();
$app = \Slim\Slim::getInstance();
$response["error"] = true;
$response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
echoResponse(400, $response);

$app->stop();
}
}

/**
* Validando parametro email si necesario; un Extra ;)
*/
function validateEmail($email) {
$app = \Slim\Slim::getInstance();
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$response["error"] = true;
$response["message"] = 'Email address is not valid';
echoResponse(400, $response);

$app->stop();
}
}

/**
* Mostrando la respuesta en formato json al cliente o navegador
* @param String $status_code Http response code
* @param Int $response Json response
*/
function echoResponse($status_code, $response) {
	$app = \Slim\Slim::getInstance();
	// Http response code
	$app->status($status_code);

	// setting response content type to json
	$app->contentType('application/json');

	echo json_encode($response);
}

/**
* Agregando un leyer intermedio e autenticación para uno o todos los metodos, usar segun necesidad
* Revisa si la consulta contiene un Header "Authorization" para validar
*/
function authenticate(\Slim\Route $route) {
	// Getting request headers
	$headers = apache_request_headers();
	$response = array();
	$app = \Slim\Slim::getInstance();

	// Verifying Authorization Header
	if (isset($headers['Authorization'])) {
	//$db = new DbHandler(); //utilizar para manejar autenticacion contra base de datos

	// get the api key
	$token = $headers['Authorization'];

	// validating api key
	if (!($token == API_KEY)) { //API_KEY declarada en Config.php

	// api key is not present in users table
	$response["error"] = true;
	$response["message"] = "Acceso denegado. Token inválido";
	echoResponse(401, $response);

	$app->stop(); //Detenemos la ejecución del programa al no validar

	} else {
	//procede utilizar el recurso o metodo del llamado
	}
	} else {
	// api key is missing in header
	$response["error"] = true;
	$response["message"] = "Falta token de autorización";
	echoResponse(400, $response);

	$app->stop();
	}
}
?>