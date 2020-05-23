<?php

class JeleleConn{

  private $username; // The user that will be used for the query
  private $password; // The  password the user
  private $dbname; // The name of the database
  private $servername; // The adress of the database
  private const ini = "JeleleConn.ini"; // the path to the ini file


  public function __construct($username = NULL,$password = NULL,$dbname = NULL,$servername = NULL){//see JeleleConn.ini
    $config = parse_ini_file(self::ini,true)["credentials"];
    $this->username = ($username == NULL ? $config["username"] : $username);
    $this->password = ($password == NULL ? $config["password"] : $password);
    $this->dbname = ($dbname == NULL ? $config["dbname"] : $dbname);
    $this->servername = ($servername == NULL ? $config["servername"] : $servername);
  }

  public function conn(){//creates a mysqli connection to this objects credentials
    return new mysqli($this->servername, $this->username, $this->password, $this->dbname);
  }

  public function getCredentials(){//this returns if JeleleConn.ini allows it to get these credential in an array
    $config = parse_ini_file(self::ini,true)["settings"];
    if($config["AllowCredentialsRequest"]){// return all
      $return = array("username" => $this->username, "password" => $this->password, "dbname" => $this->dbname, "servername" => $this->servername);
    }
    else{ // return all those that are allowed
      $return = array();
      if($config["AllowUsernameRequest"])  { $return["username"] = $this->username;}
      if($config["AllowPasswordRequest"])  { $return["password"] = $this->password;}
      if($config["AllowDBnameRequest"])    { $return["dbname"] = $this->dbname;}
      if($config["AllowServernameRequest"]){ $return["servername"] = $this->servername;}
    }
      return (!empty($return) ? $return :"You are not allowed to get any credentials, this is restricted in the JeleleConn.ini" );
  }


  public function query($query,$paramTypes = "no", $paramValues = "no"){//executes a stmt query and returns mysqli_stmt or if availible mysqli_result (or error)

    if(($paramTypes == "no" && $paramValues != "no")|| ($paramTypes != "no" && $paramValues == "no")){// check if paramTypes and paramValues have both any or none params
      $error = "Fatal error in jeleleConn->query in \$paramTypes or \$paramValues was passed no but <b><u>not</b></u> in the other one";
      trigger_error($error);
      return $error;
    }
    elseif(!($paramTypes === "no" && $paramValues === "no")){ // will execute if everything but the default no is given
      if(empty($paramTypes) || !is_string($paramTypes) || !preg_match('/^[idsb]++$/',$paramTypes)){ // checks if paramTypes is an filled string with any of i d s b as paramTypes for stmt->bind_param
        $error = "Fatal error in jeleleConn->query \$paramTypes is <b><u>not</b></u> one of paramTypes: i d s b! For more detail see <a href=https://www.php.net/manual/en/mysqli-stmt.bind-param.php'> https://www.php.net/manual/en/mysqli-stmt.bind-param.php</a>";
        trigger_error($error);
        return $error;
      }

      if(empty($paramValues)){ // checks if paramValues is not empy
        $error = "Fatal error in jeleleConn->query \$paramValues is <u><b>not</u></b> an (filled) array or string!";
        trigger_error($error);
        return $error;
      }
    }

    $conn = $this->conn(); //connects to db
    if($stmt = $conn->prepare($query)){} // prepares query
    else{ // gives error if prepare was not accepted
      $query = htmlspecialchars($query);
      $stmtError = htmlspecialchars($conn->error);
      $error = "Fatal error in jeleleConn->query \$query could <u><b>not</u></b> be prepared! <b><u>SQL error: </b>  $stmtError.</u> <b>Check query:</b> $query";
      trigger_error($error);
      return $error;
    }

    if(!($paramTypes == "no" && $paramValues == "no")){ // checks if params are given
      $bind_param = "\$stmt->bind_param('$paramTypes'"; //start of $bind_param
      if(is_array($paramValues)){ // checks if one or more(array) of paramValues is given
        $i = 1;
        foreach ($paramValues as $param) { // adds the param to the $bind_param
          $param = htmlspecialchars($param);
          eval("\$param$i = \$param;");
          $bind_param .= ",\$param$i";
          $i++;
        }
      }
      elseif ($paramValues){ // adds the param to the $bind_param
        $param = htmlspecialchars($paramValues);
        $bind_param .= ",\$param";
      }
      $bind_param .= ");"; // finishes $bind_param
      eval($bind_param); // binds params
    }

    if($result = $stmt->execute()){ // executes query
      if($result = $stmt->get_result()){ // if results are availible grab them and return them
        $conn->close();
        return $result;
      }
      else{ // else give back the mysqli_stmt obj
        $conn->close();
        return $stmt;
      }
    }
    else{ // gets the sql error that probbaly occured
      $error = $stmt->error;
      $conn->close();
      trigger_error($error);
      return $error;
    }
  }

  public function process($query,$paramTypes = "no", $paramValues = "no"){// this function will execute $this->query() and process the data gives array result of SELECT or affected_rows of UPDATE INSERT or DELETE (or returns $this->query result on error)

    $errorStart = "Fatal error in jeleleConn->process"; // defines start of error
    $result = $this->query($query,$paramTypes, $paramValues); // executes query

    if(is_a($result,"mysqli_stmt") || is_a($result,"mysqli_result")){//check no error is given from $this->query so the next actions can be done
      $action = substr($query,0,6); // first sql keyword to define what kind of result is needed
      switch (strtoupper($action)) { // if this switch does not find a match it wil return the query result to process it youself
        case 'SELECT': // if SELECT get array form query
          $rows = array();
          while($array = $result->fetch_assoc()){ $rows[] = $array;}
          if(!empty($rows)){ return $rows;} // checks if any data had come in
          else{ $error = "$errorStart No data was availible for fetch";}
          break;

        case 'UPDATE'://falling through
        case 'DELETE': //falling through
        case 'INSERT': // if INSERT, UPDATE or DELETE get number of affected_rows
          if($rows = $result->affected_rows){ return $rows;}
          else{ $error = "$errorStart No data on affected rows";}
          break;

        default: // keyword is not suporrted
        $error = "$errorStart the first words <b><u>didn't match</b></u> any supported mysql keywords( we found: $action)! Use: SELECT, UDATE, INSET, DELETE (or any uncapitlised version).";
          break;
      }
    }
    else{
      $error = "$errorStart The result of jeleleConn->query was <b><u>not</b></u> an mysqli_stmt or mysqli_result obj! See return for error details.";
    }

    if(isset($error)){ // handels errors if occured
      trigger_error($error);
      return $result;
    }

  }

}
 ?>
