<?php

/**
 * Main Module responsible for starting our app
 * @namespace App
 */
namespace App;

use App\Http\Router;
use App\Http\Response;
use App\Http\Request;
use App\Controllers\Transaction;

/**
 * Registering Routes
 * GET /transaction Transaction::index()
 * POST /transaction Transaction::create()
 * DELETE /tasnaction Transaction::remove()
 * PUT /tranaction Transaction::update()
 */
$router = new Router();

$router->register("get", "transaction", function(){
    return Transaction::index();
});

$router->register("post", "transaction", function() { 
    return Transaction::create();
});

$router->register("delete", "transaction", function() { 
    return Transaction::remove();
});

$router->register("put", "transaction", function() {
    return Transaction::update();
});

$api = new Response($router);

/**
 * FOR DEBUGGING PURPOSES ONLY.
 * By Default Making Method as "GET".
 * Ignoring All Path and pointing to HardCoded "/tranaction"
 */
$method = ( isset($_SERVER["REQUEST_METHOD"]) )? $_SERVER["REQUEST_METHOD"]: "GET";

$api->handle(new Request($method, "transaction"));

// Holding Our Configurations.
Class Config
{
    public static $DB = "transactions.tsv";
    public static $key = "Random Secret Key";
    public static $method = "aes128";
}

/*********************
 * END OF Main Module
*********************/

/**
 * Controller Module
 */
namespace App\Controllers;

use \App\Services\CsvHandler;
use \App\Services\Json;
use \App\Services\Input;

Class Transaction
{
    public static function index()
    {
        if( CsvHandler::isFileWriteable() ) {
            return Json::response( CsvHandler::getAllRows() );
        }

        return Json::fail( "Unable to Open file" );
    }

    public static function remove()
    {
        $index = (int) Input::get( "index" );

        if( CsvHandler::isFileWriteable() && CsvHandler::deleteRow( $index ) )
            return Json::success();

        return Json::fail( "Unable to Delete Transaction from file" );
    }

    public static function create()
    {
        if( CsvHandler::isFileWriteable() 
            && CsvHandler::insertRow( Input::csvFormat() ) )

            return Json::redirect("success");

        return Json::redirect("error");
    }

    public static function update()
    {
        $csvRow = Input::csvFormat();
        $index = (int) Input::$index;

        if( CsvHandler::isFileWriteable() 
            && CsvHandler::deleteRow( $index ) 
            && CsvHandler::insertRow( $csvRow ) )

            return Json::success();

        return Json::fail("Unable to Update Transaction");
    }
}

/***************************
 * END OF Controller Module
****************************/

/**
 * Services Module
 * @service JSON Responsible for Handling JSON responses for the api.
 * @service Input Responsible for Handling CRUD input data.
 * @service CsvHandler Responsible for Handling CSV file.
 */
namespace App\Services;

use \App\Config;

Class Json
{
    public static function response($data = false)
    {
        header("Content-type: application/json");
        echo  json_encode($data);
    }

    public static function success()
    {
        header("Content-type: application/json");
        echo json_encode(array("success" => "ok" ));
    }

    public static function fail($reason = "Unknown Reason")
    {
        header("Content-type: application/json");
        echo json_encode(array("error" => $reason ));
    }

    public static function redirect($message)
    {
        header("Location: /?status=".$message);
    }
}

Class Input
{
    public static $index;
    public static function get($key)
    {
        $input = static::inputFormat();

        if($key == "CardNumber") 
            return openssl_encrypt ($input[$key], Config::$method, Config::$key);
        return $input[$key];
    }

    public static function csvFormat()
    {
        $input = static::inputFormat();
        $input["CardNumber"] = openssl_encrypt ($input["CardNumber"], Config::$method, Config::$key);

        return PHP_EOL.$input["Created"]."\t"
            . $input["Amount"]."\t"
            . $input["CardholderName"]."\t"
            . $input["CardNumber"]."\t"
            . $input["ExpiredM"]."\t"
            . $input["ExpiredY"]."\t"
            . $input["CVV"]."\t"
            . $input["Status"];
    }

    public static function inputFormat()
    {
        parse_str(file_get_contents("php://input"), $input);

        if( isset($input["data"]) ) {
            $input = json_decode( $input["data"], true );
            static::$index = $input["index"];
        }

        return $input;
    }
}

class CsvHandler
{
    public static $file,
        $fileName;

    public static $rows = [],
        $headers;

    public static function isFileWriteable()
    {
        return is_writable( Config::$DB );
    }

    public static function parse()
    {
        $file = fopen( Config::$DB, "r");
        static::$headers = array_map("trim", fgetcsv($file, 4096, "\t"));
        while (!feof($file)) {
            $row = array_map("trim", (array)fgetcsv($file, 4096, "\t"));
            if ( count(static::$headers) !== count($row) ) {
                continue;
            }
            
            $row = array_combine( static::$headers, $row );
            yield $row;
        }
        return;
    }

    public static function getAllRows($masked = true)
    {
        static::$rows = [];
        foreach (static::parse() as $row) {
            if($masked){
                $row["CardNumber"] = openssl_decrypt ($row["CardNumber"], Config::$method, Config::$key);
                $row["CardNumber"] = substr($row["CardNumber"], 0, 4)."-XXXX-XXXX-XXXX";
            }

            array_push(static::$rows, $row);
        }

        return static::$rows;
    }

    public static function deleteRow($rowNumber = 1)
    {
        $newCsv = "";
        foreach ( static::getAllRows(false) as $index => $row ) {
            if( $index == $rowNumber )
                continue;

            $newCsv .= implode(array_values($row), "\t")."\n";
        }

        $header = implode( static::$headers, "\t" )."\n";
        return file_put_contents( Config::$DB, $header.$newCsv );
    }

    public static function insertRow($row)
    {
        return file_put_contents( Config::$DB, $row, FILE_APPEND );
    }
}

/***************************
 * END OF Services Module
****************************/

/**
 * Http Module for handling HTTP API's.
 * @class Request Handling Request Parameters.
 * @class Response Responsible for matching URL to proper Router.
 * @class Router Holds Our Router Mapping.
 */
namespace App\Http;

class Request
{
    private $method;
    private $path;

    public function __construct($method, $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * Getting API method
     * @return string available HTTP methods
    **/
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Getting API Path
     * @return string url path
    **/
    public function getPath()
    {
        return $this->path;
    }
}

class Response
{
    private $router;

    function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * This will match the request url to it's controller.
     * @return Handler| Empry url path
    **/
    public function handle(Request $request)
    {
        $handler = $this->router->match($request);
        if (!$handler) {
            echo "Could not find your resource!\n";
            return;
        }

        return $handler();
    }
}

class Router
{
    private $routes = [
        "get" => [],
        "post" => [],
        "delete" => [],
        "put" => []
    ];

    public function register($method, $pattern, callable $handler)
    {
        $this->routes[$method][$pattern] = $handler;
        return $this;
    }

    public function match(Request $request)
    {
        $method = strtolower($request->getMethod());
        if (!isset($this->routes[$method])) {
            return false;
        }

        $path = $request->getPath();
        foreach ($this->routes[$method] as $pattern => $handler) {
            if ($pattern === $path) {
                return $handler;
            }
        }

        return false;
    }

}
