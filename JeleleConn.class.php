<?php

class JeleleConn{

  private $username; // The user that will be used for the query
  private $password; // The  password the user
  private $dbname; // The name of the database
  private $servername; // The address of the database
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

  public function getTypes(...$paramValues){// this returns the types of the paramValues passed in (for stmt->bind_param)
    $paramTypes = "";
    foreach ($paramValues as $param) { // does for every given param a type check
      if(is_int($param) || is_bool($param)){ $paramTypes .= "i";}
      elseif(is_string($param)){ $paramTypes .= "s";}
      elseif(is_double($param)){ $paramTypes .= "d";}
      else{ $paramTypes .= "b";}
    }
    return $paramTypes;
  }

  public function query($query, ...$paramValues){//executes a stmt query and returns mysqli_stmt or if availible mysqli_result (or error)

    $QM = substr_count($query,"?"); // checks how many ? are in the query (should be nuber of params)
    if($QM != count($paramValues)){ // checks ? and parammeters match count
      if(count($paramValues) > $QM){ // too many params error
          $error = "Fatal error in jeleleConn->query too <b><u>many</u></b> number of params are given";
          trigger_error($error);
          return $error;
      }
      elseif(count($paramValues) < $QM){ // too  few many params error
        $error = "Fatal error in jeleleConn->query too <b><u>few</u></b> number of params are given";
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
    if($QM > 0){ // checks if params are needed
      $paramTypes = $this->getTypes(...$paramValues); // gets the paramTypes associated with the paramTypes
      $stmt->bind_param($paramTypes,...$paramValues); // binds the params to the query
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

  public function process($query, ...$paramValues){// this function will execute $this->query() and process the data gives array result of SELECT or affected_rows of UPDATE INSERT or DELETE (or returns $this->query result on error)

    $errorStart = "Fatal error in jeleleConn->process"; // defines start of error
    $result = $this->query($query,...$paramValues); // executes query

    if(is_a($result,"mysqli_stmt") || is_a($result,"mysqli_result")){//check no error is given from $this->query so the next actions can be done
      $action = substr($query,0,6); // first sql keyword to define what kind of result is needed
      switch (strtoupper($action)) { // if this switch does not find a match it wil return the query result to process it youself
        case 'SELECT': // if SELECT get array form query
          $rows = array();
          while($array = $result->fetch_assoc()){ $rows[] = $array;}
          if(!empty($rows)){ return $rows;} // checks if any data had come in
          else{ return false;}
          break;

        case 'UPDATE'://falling through
        case 'DELETE': //falling through
        case 'INSERT': // if INSERT, UPDATE or DELETE get number of affected_rows
          if($rows = $result->affected_rows){ return $rows;}
          else{ return false;}
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
