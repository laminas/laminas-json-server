# Zend\\Json\\Server\\Response

The JSON-RPC response payload is encapsulated in the
`Zend\Json\Server\Response` object. This object allows you to set the return
value of the request, whether or not the response is an error, the request
identifier, the JSON-RPC specification version the response conforms to, and
optionally the service map.

The response object has the following methods available:

- `setResult($value)`: Set the response result.
- `getResult()`: Retrieve the response result.
- `setError(Zend\Json\Server\Error $error)`: Set an error object. If set, this
  will be used as the response when serializing to JSON.
- `getError()`: Retrieve the error object, if any.
- `isError()`: Whether or not the response is an error response.
- `setId($name)`: Set the request identifier (so the client may match the
  response with the original request).
- `getId()`: Retrieve the request identifier.
- `setVersion($version)`: Set the JSON-RPC version the response conforms to.
- `getVersion()`: Retrieve the JSON-RPC version the response conforms to.
- `toJson()`: Serialize the response to JSON. If the response is an error
  response, serializes the error object.
- `setServiceMap($serviceMap)`: Set the service map object for the response.
- `getServiceMap()`: Retrieve the service map object, if any.

An HTTP specific version is available via `Zend\Json\Server\Response\Http`.
This class will send the appropriate HTTP headers as well as serialize the
response as JSON.
