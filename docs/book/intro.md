# Introduction

zend-json-server is a [JSON-RPC](http://groups.google.com/group/json-rpc/) server implementation.
It supports both the [JSON-RPC version 1 specification](http://json-rpc.org/wiki/specification) as
well as the [version 2 specification](http://www.jsonrpc.org/specification); additionally, it
provides a PHP implementation of the [Service Mapping Description (SMD)
specification](http://www.jsonrpc.org/specification) for providing service metadata to service
consumers.

JSON-RPC is a lightweight Remote Procedure Call protocol that utilizes JSON for its messaging
envelopes. This JSON-RPC implementation follows PHP's
[SoapServer](http://www.php.net/manual/en/class.soapserver.php) API. This means, in a typical
situation, you will simply:

- Instantiate the server object.
- Attach one or more functions and/or classes/objects to the server object.
- `handle()` the request.

zend-json-server utilizes [Zend\Server\Reflection](http://framework.zend.com/manual/current/en/modules/zend.server.reflection.html)
to perform reflection on any attached classes or functions, and uses that
information to build both the SMD and enforce method call signatures. As such,
it is imperative that any attached functions and/or class methods have full
PHP docblocks documenting, minimally:

- All parameters and their expected variable types
- The return value variable type

zend-json-server listens for POST requests only at this time; fortunately, most
JSON-RPC client implementations in the wild at the time of this writing will
only POST requests as it is. This makes it simple to utilize the same server
end point to both handle requests as well as to deliver the service SMD, as is
shown in the next example.
