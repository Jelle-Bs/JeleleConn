# JeleleConn
### Smooth sql query handler
##### Use this class if you want an easier way to execute and get results for SQL based Databases.
- Using mysqli and stmt, for a secure query.
- Separate ini file for default db credential and settings.
- Automagicly gets paramValues types.
- Will trow an error if number of params and ? in the query don't match up
- Possibility for object specific user, db and/or server!
- Just ask it for its credentials (if allowed in ini file).
- Use process() to automatically get the result right from the query.
- Or query() to have your own processing.
- Throws error when your query is not sql correct (with details).
- Returns false on SELECT resulting in no rows.

> this is a product of [Jeleleforest](https://jeleleforest.nl?english)

#### Functions
- conn()
  - returns a connection to your objects db
- getCredentials()
  - returns the allowed credential
- getTypes($paramValues)
  - returns all the param types in single string ( for stmt->bind_param )
- query(query, paramValues)
  - stmt safe execution of query using bind_param
  - returns mysqli_result(if available) or mysqli_stmt object

    - query: string with query (fist 6 letters contains sql action) (on variable place is a ?)
    > example: "SELECT * FROM User WHERE Email=?";

    - paramValues: string, int or array with the values (for the ? places) (array like = array(param1,param2,param3 ...))
    - If no params are needed simply don't use this argument
    > examples: "mail@example" or array(13,"Daniel",4.20)

- process(same arguments as query)
  - Uses query() for the execution
  - Processes the query() results and returns an array by select(false on zero rows) or affected_rows by INSERT, UPDATE, DELETE
  > SELECT array like array(0=> array(1strow), 1=> array(2ndrow), 3=> array(3rdrow), ...)
  > row arrays are a mysqli associative array (constructed with SELECT fields)

  > (or returns the query() results {mysqli_result or mysqli_stmt} on error)
