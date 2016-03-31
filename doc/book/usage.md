# Basic Usage

First, let's define a class we wish to expose via the JSON-RPC server. We'll
call the class `Calculator`, and define methods for `add`, `subtract`,
`multiply`, and `divide`:

```php
/**
 * Calculator - sample class to expose via JSON-RPC
 */
class Calculator
{
    /**
     * Return sum of two variables
     *
     * @param  int $x
     * @param  int $y
     * @return int
     */
    public function add($x, $y)
    {
        return $x + $y;
    }

    /**
     * Return difference of two variables
     *
     * @param  int $x
     * @param  int $y
     * @return int
     */
    public function subtract($x, $y)
    {
        return $x - $y;
    }

    /**
     * Return product of two variables
     *
     * @param  int $x
     * @param  int $y
     * @return int
     */
    public function multiply($x, $y)
    {
        return $x * $y;
    }

    /**
     * Return the division of two variables
     *
     * @param  int $x
     * @param  int $y
     * @return float
     */
    public function divide($x, $y)
    {
        return $x / $y;
    }
}
```

Note that each method has a docblock with entries indicating each parameter and
its type, as well as an entry for the return value. This is **absolutely
critical** when utilizing zend-json-server or any other server component in
Zend Framework, for that matter.

Now we'll create a script to handle the requests:

```php
$server = new Zend\Json\Server\Server();

// Indicate what functionality is available:
$server->setClass('Calculator');

// Handle the request:
$server->handle();
```

However, this will not address the issue of returning an SMD so that the
JSON-RPC client can autodiscover methods. That can be accomplished by
determining the HTTP request method, and then specifying some server
metadata:

```php
$server = new Zend\Json\Server\Server();
$server->setClass('Calculator');

if ('GET' == $_SERVER['REQUEST_METHOD']) {
    // Indicate the URL endpoint, and the JSON-RPC version used:
    $server->setTarget('/json-rpc.php')
           ->setEnvelope(Zend\Json\Server\Smd::ENV_JSONRPC_2);

    // Grab the SMD
    $smd = $server->getServiceMap();

    // Return the SMD to the client
    header('Content-Type: application/json');
    echo $smd;
    return;
}

$server->handle();
```

If utilizing the JSON-RPC server with Dojo toolkit, you will also need to set a
special compatibility flag to ensure that the two interoperate properly:

```php
$server = new Zend\Json\Server\Server();
$server->setClass('Calculator');

if ('GET' == $_SERVER['REQUEST_METHOD']) {
    $server->setTarget('/json-rpc.php')
           ->setEnvelope(Zend\Json\Server\Smd::ENV_JSONRPC_2);
    $smd = $server->getServiceMap();

    // Set Dojo compatibility:
    $smd->setDojoCompatible(true);

    header('Content-Type: application/json');
    echo $smd;
    return;
}

$server->handle();
```
