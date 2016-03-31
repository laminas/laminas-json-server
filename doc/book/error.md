# Zend\\Json\\Server\\Error

JSON-RPC has a special format for reporting error conditions. All errors need
to provide, minimally, an error message and error code; optionally, they can
provide additional data, such as a backtrace.

Error codes are derived from those recommended by [the XML-RPC EPI project](http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php).
`Zend\Json\Server` appropriately assigns the code based on the error condition.
For application exceptions, the code '-32000' is used.

`Zend\Json\Server\Error` exposes the following methods:

- `setCode($code)`: Set the error code; if the code is not in the accepted
  XML-RPC error code range, -32000 will be assigned.
- `getCode()`: Retrieve the current error code.
- `setMessage($message)`: Set the error message.
- `getMessage()`: Retrieve the current error message.
- `setData($data)`: Set auxiliary data further qualifying the error, such as a
  backtrace.
- `getData()`: Retrieve any current auxiliary error data.
- `toArray()`: Cast the error to an array. The array will contain the keys
  'code', 'message', and 'data'.
- `toJson()`: Cast the error to a JSON-RPC error representation.
