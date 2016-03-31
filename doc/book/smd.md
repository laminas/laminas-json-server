# SMD: Service Mapping Description

SMD stands for Service Mapping Description, a JSON schema that defines how a
client can interact with a particular web service. At the time of this writing,
the [specification](http://www.jsonrpc.org/specification) has not yet been
formally ratified, but it is in use already within Dojo Toolkit as well as
other JSON-RPC consumer clients.

At its most basic, a Service Mapping Description indicates the method of
transport (`POST`, `GET`, TCP/IP, etc), the request envelope type (usually
based on the protocol of the server), the target URL of the service provider,
and a map of services available. In the case of JSON-RPC, the service map is a
list of available methods, which each method documenting the available
parameters and their types, as well as the expected return value type.

`Zend\Json\Server\Smd` provides an object-oriented way to build service maps.
At its most basic, you pass it metadata describing the service using mutators,
and specify services (methods and functions).

The service descriptions themselves are typically instances of
`Zend\Json\Server\Smd\Service`; you can also pass all information as an array
to the various service mutators in `Zend\Json\Server\Smd`, and it will
instantiate a service for you. The service objects contain information such as
the name of the service (typically the function or method name), the parameters
(names, types, and position), and the return value type. Optionally, each
service can have its own target and envelope, though this functionality is
rarely used.

`Zend\Json\Server\Server` actually does all of this behind the scenes for you,
by using reflection on the attached classes and functions; you should create
your own service maps only if you need to provide custom functionality that
class and function introspection cannot offer.

Methods available in `Zend\Json\Server\Smd` include:

- `setOptions(array $options)`: Setup an SMD object from an array of options.
  All mutators (methods beginning with 'set') can be used as keys.
- `setTransport($transport)`: Set the transport used to access the service;
  only POST is currently supported.
- `getTransport()`: Get the current service transport.
- `setEnvelope($envelopeType)`: Set the request envelope that should be used to
  access the service.  Currently, supports the constants
  `Zend\Json\Server\Smd::ENV_JSONRPC_1` and
  `Zend\Json\Server\Smd::ENV_JSONRPC_2`.
- `getEnvelope()`: Get the current request envelope.
- `setContentType($type)`: Set the content type requests should use (by
  default, this is 'application/json').
- `getContentType()`: Get the current content type for requests to the service.
- `setTarget($target)`: Set the URL endpoint for the service.
- `getTarget()`: Get the URL endpoint for the service.
- `setId($id)`: Typically, this is the URL endpoint of the service (same as the
  target).
- `getId()`: Retrieve the service ID (typically the URL endpoint of the
  service).
- `setDescription($description)`: Set a service description (typically
  narrative information describing the purpose of the service).
- `getDescription()`: Get the service description.
- `setDojoCompatible($flag)`: Set a flag indicating whether or not the SMD is
  compatible with Dojo toolkit. When `TRUE`, the generated JSON SMD will be
  formatted to comply with the format that Dojo's JSON-RPC client expects.
- `isDojoCompatible()`: Returns the value of the Dojo compatibility flag
  (`FALSE`, by default).
- `addService($service)`: Add a service to the map. May be an array of
  information to pass to the constructor of `Zend\Json\Server\Smd\Service`, or
  an instance of that class.
- `addServices(array $services)`: Add multiple services at once.
- `setServices(array $services)`: Add multiple services at once, overwriting
  any previously set services.
- `getService($name)`: Get a service by its name.
- `getServices()`: Get all attached services.
- `removeService($name)`: Remove a service from the map.
- `toArray()`: Cast the service map to an array.
- `toDojoArray()`: Cast the service map to an array compatible with Dojo
  Toolkit.
- `toJson()`: Cast the service map to a JSON representation.

`Zend\Json\Server\Smd\Service` has the following methods:

- `setOptions(array $options)`: Set object state from an array. Any mutator
  (methods beginning with 'set') may be used as a key and set via this method.
- `setName($name)`: Set the service name (typically, the function or method
  name).
- `getName()`: Retrieve the service name.
- `setTransport($transport)`: Set the service transport (currently, only
  transports supported by `Zend\Json\Server\Smd` are allowed).
- `getTransport()`: Retrieve the current transport.
- `setTarget($target)`: Set the URL endpoint of the service (typically, this
  will be the same as the overall SMD to which the service is attached).
- `getTarget()`: Get the URL endpoint of the service.
- `setEnvelope($envelopeType)`: Set the service envelope (currently, only
  envelopes supported by `Zend\Json\Server\Smd` are allowed).
- `getEnvelope()`: Retrieve the service envelope type.
  `addParam($type, array $options = array(), $order = null)`: Add a parameter
  to the service. By default, only the parameter type is necessary. However,
  you may also specify the order, as well as options such as:
    - **name**: the parameter name
    - **optional**: whether or not the parameter is optional
    - **default**: a default value for the parameter
    - **description**: text describing the parameter
- `addParams(array $params)`: Add several parameters at once; each param should
  be an assoc array containing minimally the key 'type' describing the
  parameter type, and optionally the key 'order'; any other keys will be passed
  as `$options` to `addOption()`.
- `setParams(array $params)`: Set many parameters at once, overwriting any
  existing parameters.
- `getParams()`: Retrieve all currently set parameters.
- `setReturn($type)`: Set the return value type of the service.
- `getReturn()`: Get the return value type of the service.
- `toArray()`: Cast the service to an array.
- `toJson()`: Cast the service to a JSON representation.
