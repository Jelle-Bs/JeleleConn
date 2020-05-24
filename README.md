# JeleleConn
### Smooth sql query handler
##### Use this class if you want an easier way to execute and get results for SQL based Databases.
- Using mysqli and stmt, for a secure query.
- Separate ini file for default db credential and settings.
- Possibility for object specific user, db and/or server!
- Just ask it for its credentials (if allowed in ini file).
- Use process() to automatically get the result right from the query.
- Or query() to have your own processing.
- Throws error when your query is not sql correct (with details).

> this is an product of [Jeleleforest](https://jeleleforest.nl?english)

#### Functions
- conn()
  - returns a connection to you objects db
- getCredentials()
  - returns the allowed credential
- query(query,paramTypes,paramValues)
  - stmt save execution of query using bind_param
  - returns mysqli_result(if available) or mysqli_stmt object

    - query: string with query (fist 6 letters contains sql action) (on variable place is a ?)
    > example: "SELECT * FROM User WHERE Email LIKE '%@gmail.com'";

    - paramTypes: string with for every param the type ( i{int} , d{double}, s{string}, b{blob} )
    > examples: "isd" for int, string double or "s" for only string

    - paramValues: string, int or array with the values (for the ? places) (array like = array(param1,param2,param3 ...))
    > examples: array(13,"Daniel",4.20) or "Daniel"

    > for more information see [PHP.net/mysqli_stmt::bind_param](https://www.php.net/manual/en/mysqli-stmt.bind-param.php)

- process(same arguments as query)
  - Processes the query results in an array by select or affected_rows by INSERT, UPDATE, DELETE
  > (or returns the query() results{mysqli_result or mysqli_stmt})
