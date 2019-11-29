<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Max-Age: 604800");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include database class file
include "db.php";

// generate json web token
require "vendor/autoload.php";
use \Firebase\JWT\JWT;

class API
{
  protected $dbhost = 'localhost';
  protected $dbuser = 'root';
  protected $dbpass = '';
  protected $dbname = 'student_psychology_assessment';

  private $key = "student_psychology_assessment_by_tahir_khan";
  private $iss = "student_psychology_assessment";
  private $aud = "student_psychology_assessment";
  private $iat = 1356999524;
  private $nbf = 1357000000;

  private $access_token;

  protected $db;

  // Constructor Function
  public function __construct()
  {
    // Make a connection to Database
    $this->db = new db($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);

    // Clean $_REQUEST parameters
    if(!empty($_REQUEST))
    {
      foreach($_REQUEST as &$value)
      {
           $value = $this->clean_input($value);
      }
    }
  }

  // Destructor Function
  public function __destruct()
  {
    $this->db->close();
  }

  public function decode_access_token()
  {
    if(!empty($_REQUEST["access_token"])){
      $jwt  = $_REQUEST["access_token"];
      $data = "";

      $decoded = JWT::decode($jwt, $this->key, array('HS256'));
      $data = get_object_vars($decoded->data);

      if(!isset($_COOKIE["user_id"])) {
          setcookie("user_id", $data["user_id"], time() + (86400 * 30), "/");
      }
    }
    unset($data["user_password"]);
    return $this->response($data, 204, "Login Failed");
  }

  public function admin_login()
  {
    if($_REQUEST["username"] == "admin" && $_REQUEST["password"] == "pass")
    {
        $token = array(
               "iss"  =>  $this->iss,
               "aud"  =>  $this->aud,
               "iat"  =>  $this->iat,
               "nbf"  =>  $this->nbf,
               "data" =>  "admin_logged_in");

         $jwt = JWT::encode($token, $this->key);
        $this->response(array("access_token" => $jwt), "200", "Successfull Login");
    } else {
        $this->response(NULL, 204, "Login Failed");
    }
  }

  public function get_primary_key($table)
  {
    $primary_key = "";
    switch ($table)
    {
      case "users":
        $primary_key = "user_id";
        break;
      case "tests":
        $primary_key = "test_id";
        break;
      default:
        $primary_key = NULL;
    }

    return $primary_key;
  }

  public function init()
  {
    //print_r($_REQUEST);
    if(!empty($_REQUEST["action"]))
    {
      if($_REQUEST["action"] == "login" && !empty($_REQUEST["email"]) && !empty($_REQUEST["password"]))
      {
        $this->login();
      }
      elseif($_REQUEST["action"] == "access_token" && !empty($_REQUEST["access_token"]))
      {
        $this->decode_access_token();
      }
      elseif($_REQUEST["action"] == "login" && !empty($_REQUEST["username"]) && !empty($_REQUEST["password"]))
      {
        if(!empty($_REQUEST["username"]) && $_REQUEST["username"] == "admin")
        {
          $this->admin_login();
        }
      }
      elseif($_REQUEST["action"] == "register" && !empty($_REQUEST["email"]) && !empty($_REQUEST["password"]&& !empty($_REQUEST["name"]) && !empty($_REQUEST["age"]) && !empty($_REQUEST["gender"])))
      {
        // Register
        $this->register_user();
      } else {
        die($this->response("INIT", "400", "Bad input parameter"));
      }

    } elseif(!empty($_REQUEST["table"]) && !empty($_REQUEST["method"]))
    {
        if($_REQUEST["method"] == "GET")
        {
          $reqArray   = array();
          if(isset($_REQUEST["id"]))
          {
            $reqArray["id"] = $_REQUEST["id"];
          }

          $query      = $this->create_query($_REQUEST["table"], $_REQUEST["method"], $reqArray);
          $statement  = $this->db->query($query);
          $numRows    = $statement->numRows();
          if($numRows > 0)
          {
              if($numRows < 2)
              {
                $data = $statement->fetchArray();
                if(array_key_exists("user_password", $data))
                {
                  unset($data["user_password"]);
                }
              } else {

                $data = $statement->fetchAll();
                foreach(array_keys($data) as $key) {
                 unset($data[$key]["user_password"]);
                }
              }

              $this->response($data, "200", "Ok");
          } else {
              $this->response(NULL, 204,"No Record Found");
            }
        }
        elseif($_REQUEST["method"] == "POST" || $_REQUEST["method"] == "PUT")
        {
          $this->main();
        }
        elseif($_REQUEST["method"] == "DELETE")
        {
          $this->main();
        }
    } else {
          die($this->response("Not going to main func", "400", "Bad input parameter"));
    }
  }

  public function create_query($table, $method, $info)
  {
    $query = "";
    if(!empty($table) && !empty($method))
    {
      if($method == "POST" || $method == "GET" || $method == "PUT" || $method == "DELETE")
      {
        $primary_key = $this->get_primary_key($table);
        switch($method)
        {
          case "GET":

            if(!empty($_REQUEST["limit"]) && $_REQUEST["limit"] > 0)
            {
              $limit = "ORDER BY `$primary_key` DESC LIMIT ".$_REQUEST["limit"];
            } else {
              $limit = "";
            }

            if(empty($_REQUEST["id"]))
            {
              $query = "SELECT * FROM `$table` $limit";
            } else {
              $query = "SELECT * FROM `$table` WHERE `$primary_key` = '".$this->clean_input($info["id"])."' $limit";
            }

            break;

          case "POST":
            $required_keys  = "";
            $required_values = "";
            switch($table)
            {
              case "tests":
                $required_keys = "`test_name`, `test_desc`, `test_preview`, `test_thumbnail`, `test_questions`";
                $required_values = "'".$info['test_name']."','".$info['test_desc']."','".$info['test_preview']."','".$info['test_thumbnail']."','".$info['test_questions']."'";
                break;
              case "users":
                $required_keys = "`user_name`, `user_age`, `user_gender`, `user_email`, `user_password`";
                $required_values = "'".$info['user_name']."','".$info['user_age']."','".$info['user_gender']."','".$info['user_email']."','".$info['user_password']."'";
                break;
              case "test_taken":
                  $required_keys = "`test_score`, `test_id`, `user_id`";
                  $required_values = "'".$info['test_score']."','".$info['test_id']."','".$info['user_id']."'";
                  break;
            }

            if($table == "users" && !empty($info['user_name']) && !empty($info['user_age']) && !empty($info['user_gender']) && !empty($info['user_email']) && !empty($info['user_password']))
            {
              $query = "INSERT INTO `$table`($required_keys) VALUES ($required_values)";
            } elseif($table == "tests")
            {
              $query = "INSERT INTO `$table`($required_keys) VALUES ($required_values)";
            } elseif($table == "test_taken")
            {
              $query = "INSERT INTO `$table`($required_keys) VALUES ($required_values)";
            }else {
              $query = NULL;
            }
            break;

          case "PUT":
            $keys_to_update = array();
            if($table == "tests")
            {
              if(!empty($info["test_name"]))
              {
                array_push($keys_to_update, "`test_name`='".$info["test_name"]."'");

              } elseif(!empty($info["test_desc"]))
              {
                array_push($keys_to_update, "`test_desc`='".$info["test_desc"]."'");

              } elseif(!empty($info["test_thumbnail"]))
              {
                array_push($keys_to_update, "`test_thumbnail`='".$info["test_thumbnail"]."'");

              } elseif(!empty($info["test_questions"]))
              {
                array_push($keys_to_update, "`test_questions`='".$info["test_questions"]."'");
              }
            } elseif($table == "users")
            {
              if(!empty($info["user_name"]))
              {
                array_push($keys_to_update, "`user_name`='".$info["user_name"]."'");

              } elseif(!empty($info["user_age"]))
              {
                array_push($keys_to_update, "`user_age`='".$info["user_age"]."'");

              } elseif(!empty($info["user_gender"]))
              {
                array_push($keys_to_update, "`user_gender`='".$info["user_gender"]."'");

              } elseif(!empty($info["user_email"]))
              {
                array_push($keys_to_update, "`user_email`='".$info["user_email"]."'");
              }
              elseif(!empty($info["user_password"]))
              {
                array_push($keys_to_update, "`user_password`='".$info["user_password"]."'");
              }
            }

            if(sizeof($keys_to_update) == 0)
            {
              $query = NULL;
            } else {
              $keys_to_update = implode(',', $keys_to_update);
              $query = "UPDATE `$table` SET $keys_to_update WHERE `$primary_key` = '".$info["id"]."'";
            }
            break;

          case "DELETE":
            $query = "DELETE FROM `$table` WHERE `$primary_key` = $data[0] WHERE `id`='".$info["id"]."'";
            break;
        }
      } else {
        die($this->response(NULL, 400, "Invalid Method"));
      }
    } else {
      die($this->response(NULL, 400, "Incomplete Parameters"));
    }

    return $query;
  }

  // Main process function
  public function main()
  {
    $table  = $_REQUEST["table"];
    $method = $_REQUEST["method"];

    if(!empty($table) && !empty($method))
    {
      if($method == "POST" || $method == "GET" || $method == "PUT" || $method == "DELETE")
      {
        $primary_key = $this->get_primary_key($table);
        $query = "";
        switch($method)
        {
          case "GET":
            $query = "SELECT * FROM `$table` WHERE `$primary_key` = '".$this->clean_input($_REQUEST["id"])."'";
            break;

          case "POST":
            $required_keys  = "";
            $required_values = "";
            switch($table)
            {
              case "tests":
                $req_array = json_decode(file_get_contents('php://input'), true);
                $req_array["test_questions"] = "[".json_encode($req_array["test_questions"][0])."]";
                //print_r($req_array);
                $required_keys = "`test_name`, `test_desc`, `test_preview`,`test_thumbnail`, `test_questions`";
                $required_values = "'".$req_array['test_name']."','".$req_array['test_preview']."','".$req_array['test_desc']."','".$req_array['test_thumbnail']."','".$req_array['test_questions']."'";
                break;
              case "users":
                $required_keys = "`user_name`, `user_age`, `user_gender`, `user_email`, `user_password`";
                $required_value = "'".$_REQUEST['user_name']."','".$_REQUEST['user_age']."','".$_REQUEST['user_gender']."','".$_REQUEST['user_email']."','".$_REQUEST['user_password']."'";
                break;
              case "test_taken":
                  $req_array = json_decode(file_get_contents('php://input'), true);
                  print_r($req_array);
                  $required_keys = "`test_score`, `test_id`, `user_id`";
                  $required_value = "'".$_REQUEST['test_score']."','".$_REQUEST['test_id']."','".$_REQUEST['user_id']."'";
                  break;
            }
            //if(!empty($req_array['test_name']) && !empty($req_array['test_desc']) && !empty($req_array['test_thumbnail']) && !empty($req_array['test_questions']))
            //{
              //$query = "INSERT INTO `$table`($required_keys) VALUES ($required_values)";
            //} else {
              //$query = NULL;
            //}
            $query = "INSERT INTO `$table`($required_keys) VALUES ($required_values)";
            break;

          case "PUT":
            $keys_to_update = array();
            if($table == "tests")
            {
              if(!empty($_REQUEST["test_name"]))
              {
                array_push($keys_to_update, "`test_name`='".$_REQUEST["test_name"]."'");

              } elseif(!empty($_REQUEST["test_desc"]))
              {
                array_push($keys_to_update, "`test_desc`='".$_REQUEST["test_desc"]."'");

              } elseif(!empty($_REQUEST["test_thumbnail"]))
              {
                array_push($keys_to_update, "`test_thumbnail`='".$_REQUEST["test_thumbnail"]."'");

              } elseif(!empty($_REQUEST["test_questions"]))
              {
                array_push($keys_to_update, "`test_questions`='".$_REQUEST["test_questions"]."'");
              }
            } elseif($table == "users"){
              if(!empty($_REQUEST["user_name"]))
              {
                array_push($keys_to_update, "`user_name`='".$_REQUEST["user_name"]."'");

              } elseif(!empty($_REQUEST["user_age"]))
              {
                array_push($keys_to_update, "`user_age`='".$_REQUEST["user_age"]."'");

              } elseif(!empty($_REQUEST["user_gender"]))
              {
                array_push($keys_to_update, "`user_gender`='".$_REQUEST["user_gender"]."'");

              } elseif(!empty($_REQUEST["user_email"]))
              {
                array_push($keys_to_update, "`user_email`='".$_REQUEST["user_email"]."'");
              }
              elseif(!empty($_REQUEST["user_password"]))
              {
                array_push($keys_to_update, "`user_password`='".$_REQUEST["user_password"]."'");
              }
            }

            if(sizeof($keys_to_update) == 0)
            {
              $query = NULL;
            } else {
              $keys_to_update = implode(',', $keys_to_update);
              $query = "UPDATE `$table` SET $keys_to_update WHERE `$primary_key` = '".$_REQUEST["id"]."'";
            }
            break;

          case "DELETE":
            $query = "DELETE FROM `$table` WHERE `$primary_key` = '".$_REQUEST["id"]."'";
            break;
        }
        if($query == NULL)
        {
          die($this->response(NULL, "400", "Bad input parameter - NULL QUERY"));
        }

        $statement = $this->db->query($query);

        if($method == 'POST' || $method == 'PUT')
        {
          $affected_rows = $statement->affectedRows();
          if ($affected_rows > 0)
          {
            if($method == 'POST')
            {
              $this->response(array("affected_rows" => $statement->affectedRows()), "200", "Record Created");
            } elseif($method == 'PUT'){
              $this->response(array("affected_rows" => $statement->affectedRows()), "200", "Record Updated");
            }
          } else {
            if($method == 'POST')
            {
              $this->response(NULL, 204,"No Record Created");
            } elseif($method == 'PUT'){
              $this->response(NULL, 204,"No Record Updated");
            }

          }
        }
        elseif($method == 'GET')
        {
          if($statement->numRows() > 0)
          {
            $data = $statement->fetchArray();
            if($data["user_password"])
            {
              unset($data["user_password"]);
            }
            $this->response($data, "200", "Ok");
          } else {
            $this->response(NULL, 204,"No Record Found");
          }
        }
        elseif($method == 'DELETE')
        {
          if($statement->numRows() > 0)
          {
            $this->response(array("affected_rows" => $statement->numRows()), "200", "Ok");
          } else {
            $this->response(NULL, 204,"No Record Found");
          }
        }
      } else {
        die($this->response(NULL, 400,"Invalid Method"));
      }
    }
  }

  // login login
  public function login()
  {
      $email    = $this->clean_input($_REQUEST["email"]);
      $password = $_REQUEST["password"];

      $account  = $this->db->query('SELECT * FROM `users` WHERE `user_email` = ? AND `user_password` = ?', array($email, $password));
      if($account->numRows() > 0)
      {
          $token = array(
                 "iss"  =>  $this->iss,
                 "aud"  =>  $this->aud,
                 "iat"  =>  $this->iat,
                 "nbf"  =>  $this->nbf,
                 "data" =>  $account->fetchArray());

           $jwt = JWT::encode($token, $this->key);
          $this->response(array("access_token" => $jwt), "200", "Successfull Login");
      } else {
          $this->response(NULL, 204, "Login Failed");
      }
  }

  // Register function
  public function register_user()
  {
    $name     = $this->clean_input($_REQUEST["name"]);
    $email    = $this->clean_input($_REQUEST["email"]);
    $age      = $this->clean_input($_REQUEST["age"]);
    $gender   = $this->clean_input($_REQUEST["gender"]);
    $password = $_REQUEST["password"];

    $query = $this->create_query("users", "POST", array("user_name" => $name, "user_email" => $email, "user_age" => $age, "user_gender" => $gender, "user_password" => $password));

    $statement      = $this->db->query($query);
    $affected_rows  = $statement->affectedRows();
    if ($affected_rows > 0)
    {
      $this->response(array("affected_rows" => $statement->affectedRows()), "200", "Account Created");
    } else {
      die($this->response(NULL, "404", "Account Creation Failed"));
    }
  }

  // Output response in REST API JSON format
  public function response($information_arr, $response_code, $response_desc)
  {
		$response['response_data'] = $information_arr;
		$response['response_code'] = $response_code;
		$response['response_desc'] = $response_desc;

		$json_response = json_encode($response);
		echo $json_response;
	}

  // Cleanse the inputs
  public function clean_input($input)
  {
    return htmlspecialchars(strip_tags($input));
  }
}
$api = new API();
$api->init();
?>
