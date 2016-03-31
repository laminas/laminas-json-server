# Zend\\Json\\Server\\Server

`Zend\Json\Server\Server` is the core class in the JSON-RPC offering; it
handles all requests and returns the response payload. It has the following
methods:

- `addFunction($function)`: Specify a userland function to attach to the server.
- `setClass($class)`: Specify a class or object to attach to the server; all
  public methods of that item will be exposed as JSON-RPC methods.
- `fault($fault = null, $code = 404, $data = null)`: Create and return a
  `Zend\Json\Server\Error` object.
- `handle($request = false)`: Handle a JSON-RPC request; optionally, pass a
  `Zend\Json\Server\Request` object to utilize (creates one by default).
- `getFunctions()`: Return a list of all attached methods.
- `setRequest(Zend\Json\Server\Request $request)`: Specify a request object for
  the server to utilize.
- `getRequest()`: Retrieve the request object used by the server.
- `setResponse(Zend\Json\Server\Response $response)`: Set the response object
  for the server to utilize.
- `getResponse()`: Retrieve the response object used by the server.
- `setAutoEmitResponse($flag)`: Indicate whether the server should
  automatically emit the response and all headers; by default, this is `TRUE`.
- `autoEmitResponse()`: Determine if auto-emission of the response is enabled.
  `getServiceMap()`: Retrieve the service map description in the form of a
  `Zend\Json\Server\Smd` object
