# Zend\\Json\\Server\\Request

The JSON-RPC request environment is encapsulated in the
`Zend\Json\Server\Request` object. This object allows you to set necessary
portions of the JSON-RPC request, including the request ID, parameters, and
JSON-RPC specification version. It has the ability to load itself via JSON or
a set of options, and can render itself as JSON via the `toJson()` method.

The request object has the following methods available:

- `setOptions(array $options)`: Specify object configuration. `$options` may
  contain keys matching any 'set' method: `setParams()`, `setMethod()`,
  `setId()`, and `setVersion()`.
- `addParam($value, $key = null)`: Add a parameter to use with the method call.
  Parameters can be just the values, or can optionally include the parameter
  name.
- `addParams(array $params)`: Add multiple parameters at once; proxies to
  `addParam()`.
- `setParams(array $params)`: Set all parameters at once; overwrites any
  existing parameters.
- `getParam($index)`: Retrieve a parameter by position or name.
- `getParams()`: Retrieve all parameters at once.
- `setMethod($name)`: Set the method to call.
- `getMethod()`: Retrieve the method that will be called.
- `isMethodError()`: Determine whether or not the request is malformed and
  would result in an error.
- `setId($name)`: Set the request identifier (used by the client to match
  requests to responses).
- `getId()`: Retrieve the request identifier.
- `setVersion($version)`: Set the JSON-RPC specification version the request
  conforms to. May be either '1.0' or '2.0'.
- `getVersion()`: Retrieve the JSON-RPC specification version used by the
  request.
- `loadJson($json)`: Load the request object from a JSON string.
- `toJson()`: Render the request as a JSON string.

An HTTP specific version is available via `Zend\Json\Server\Request\Http`. This
class will retrieve the request via `php://input`, and allows access to the raw
JSON via the `getRawJson()` method.
