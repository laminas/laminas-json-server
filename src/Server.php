<?php
/**
 * @link      http://github.com/zendframework/zend-json-server for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Json\Server;

use Exception as PhpException;
use ReflectionFunction;
use ReflectionMethod;
use Zend\Server\AbstractServer;
use Zend\Server\Definition;
use Zend\Server\Method;
use Zend\Server\Reflection;

class Server extends AbstractServer
{
    /**#@+
     * Version Constants
     */
    const VERSION_1 = '1.0';
    const VERSION_2 = '2.0';
    /**#@-*/

    /**
     * Flag: whether or not to auto-emit the response
     *
     * @var bool
     */
    protected $returnResponse = false;

    /**
     * Inherited from Zend\Server\AbstractServer.
     *
     * Flag; allow overwriting existing methods when creating server definition.
     *
     * @var bool
     */
    protected $overwriteExistingMethods = true;

    /**
     * Request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * Response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * SMD object.
     *
     * @var Smd
     */
    protected $serviceMap;

    /**
     * SMD class accessors.
     *
     * @var array
     */
    protected $smdMethods;

    /**
     * Attach a function or callback to the server.
     *
     * @param  callable $function Valid PHP callback
     * @param  string $namespace Ignored
     * @return Server
     * @throws Exception\InvalidArgumentException If function invalid or not callable.
     */
    public function addFunction($function, $namespace = '')
    {
        if (! is_callable($function)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects the first argument to be callable; received %s',
                __METHOD__,
                (is_object($function) ? get_class($function) : gettype($function))
            ));
        }

        $argv = null;
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $class = null;
        if (! is_array($function)) {
            $method = Reflection::reflectFunction($function, $argv, $namespace);
        } else {
            $class  = array_shift($function);
            $action = array_shift($function);
            $reflection = Reflection::reflectClass($class, $argv, $namespace);
            $methods = $reflection->getMethods();
            $found   = false;
            foreach ($methods as $method) {
                if ($action == $method->getName()) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $this->fault('Method not found', Error::ERROR_INVALID_METHOD);
                return $this;
            }
        }

        $definition = $this->_buildSignature($method, $class);
        $this->addMethodServiceMap($definition);

        return $this;
    }

    /**
     * Register a class with the server.
     *
     * @param  string $class
     * @param  string $namespace Ignored
     * @param  mixed $argv Ignored
     * @return Server
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $reflection = Reflection::reflectClass($class, $argv, $namespace);

        foreach ($reflection->getMethods() as $method) {
            $definition = $this->_buildSignature($method, $class);
            $this->addMethodServiceMap($definition);
        }

        return $this;
    }

    /**
     * Indicate fault response.
     *
     * @param  string $fault
     * @param  int $code
     * @param  mixed $data
     * @return Error
     */
    public function fault($fault = null, $code = 404, $data = null)
    {
        $error = new Error($fault, $code, $data);
        $this->getResponse()->setError($error);
        return $error;
    }

    /**
     * Handle request.
     *
     * @param  Request $request
     * @return null|Response
     * @throws Exception\InvalidArgumentException
     */
    public function handle($request = false)
    {
        if ((false !== $request) && ! $request instanceof Request) {
            throw new Exception\InvalidArgumentException('Invalid request type provided; cannot handle');
        }

        if ($request) {
            $this->setRequest($request);
        }

        // Handle request
        $this->handleRequest();

        // Get response
        $response = $this->getReadyResponse();

        // Emit response?
        if (! $this->returnResponse) {
            echo $response;
            return;
        }

        // or return it?
        return $response;
    }

    /**
     * Load function definitions
     *
     * @param  array|Definition $definition
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public function loadFunctions($definition)
    {
        if (! is_array($definition) && (! $definition instanceof Definition)) {
            throw new Exception\InvalidArgumentException('Invalid definition provided to loadFunctions()');
        }

        foreach ($definition as $key => $method) {
            $this->table->addMethod($method, $key);
            $this->addMethodServiceMap($method);
        }
    }

    /**
     * Cache/persist server (unused)
     */
    public function setPersistence($mode)
    {
    }

    /**
     * Set request object
     *
     * @param  Request $request
     * @return self
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get JSON-RPC request object.
     *
     * Lazy-loads an instance if none previously available.
     *
     * @return Request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->setRequest(new Request\Http());
        }

        return $this->request;
    }

    /**
     * Set response object.
     *
     * @param  Response $response
     * @return self
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get response object.
     *
     * Lazy-loads an instance if none previously available.
     *
     * @return Response
     */
    public function getResponse()
    {
        if (null === $this->response) {
            $this->setResponse(new Response\Http());
        }

        return $this->response;
    }

    /**
     * Set return response flag.
     *
     * If true, {@link handle()} will return the response instead of
     * automatically sending it back to the requesting client.
     *
     * The response is always available via {@link getResponse()}.
     *
     * @param  bool $flag
     * @return self
     */
    public function setReturnResponse($flag = true)
    {
        $this->returnResponse = (bool) $flag;
        return $this;
    }

    /**
     * Retrieve return response flag.
     *
     * @return bool
     */
    public function getReturnResponse()
    {
        return $this->returnResponse;
    }

    /**
     * Overload to accessors of SMD object.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (! preg_match('/^(set|get)/', $method, $matches)) {
            return;
        }

        if (! in_array($method, $this->getSmdMethods())) {
            return;
        }

        if ('set' == $matches[1]) {
            $value = array_shift($args);
            $this->getServiceMap()->$method($value);
            return $this;
        }

        return $this->getServiceMap()->$method();
    }

    /**
     * Retrieve SMD object.
     *
     * Lazy loads an instance if not previously set.
     *
     * @return Smd
     */
    public function getServiceMap()
    {
        if (null === $this->serviceMap) {
            $this->serviceMap = new Smd();
        }
        return $this->serviceMap;
    }

    /**
     * Add service method to service map.
     *
     * @param  Method\Definition $method
     * @return void
     */
    protected function addMethodServiceMap(Method\Definition $method)
    {
        $serviceInfo = [
            'name'   => $method->getName(),
            'return' => $this->getReturnType($method),
        ];

        $params = $this->getParams($method);
        $serviceInfo['params'] = $params;
        $serviceMap = $this->getServiceMap();

        if (false !== $serviceMap->getService($serviceInfo['name'])) {
            $serviceMap->removeService($serviceInfo['name']);
        }

        $serviceMap->addService($serviceInfo);
    }

    // @codingStandardsIgnoreStart
    /**
     * Translate PHP type to JSON type.
     *
     * Method defined in AbstractServer as abstract and implemented here.
     *
     * @param  string $type
     * @return string
     */
    protected function _fixType($type)
    {
        return $type;
    }
    // @codingStandardsIgnoreEnd

    /**
     * Get default params from signature.
     *
     * @param  array $args
     * @param  array $params
     * @return array
     */
    protected function getDefaultParams(array $args, array $params)
    {
        if (false === $this->isAssociative($args)) {
            $params = array_slice($params, count($args));
        }

        foreach ($params as $param) {
            if (isset($args[$param['name']]) || ! array_key_exists('default', $param)) {
                continue;
            }

            $args[$param['name']] = $param['default'];
        }

        return $args;
    }

    /**
     * Check whether array is associative or not.
     *
     * @param array $array
     * @return bool
     */
    private function isAssociative(array $array)
    {
        $keys = array_keys($array);
        return ($keys != array_keys($keys));
    }

    /**
     * Get method param type.
     *
     * @param  Method\Definition $method
     * @return string|array
     */
    protected function getParams(Method\Definition $method)
    {
        $params = [];
        foreach ($method->getPrototypes() as $prototype) {
            foreach ($prototype->getParameterObjects() as $key => $parameter) {
                if (! isset($params[$key])) {
                    $params[$key] = [
                        'type'     => $parameter->getType(),
                        'name'     => $parameter->getName(),
                        'optional' => $parameter->isOptional(),
                    ];

                    if (null !== ($default = $parameter->getDefaultValue())) {
                        $params[$key]['default'] = $default;
                    }

                    $description = $parameter->getDescription();

                    if (! empty($description)) {
                        $params[$key]['description'] = $description;
                    }

                    continue;
                }

                $newType = $parameter->getType();

                if (is_array($params[$key]['type'])
                    && in_array($newType, $params[$key]['type'])
                ) {
                    continue;
                }

                if (! is_array($params[$key]['type'])
                    && $params[$key]['type'] == $newType
                ) {
                    continue;
                }

                if (! is_array($params[$key]['type'])) {
                    $params[$key]['type'] = (array) $params[$key]['type'];
                }

                array_push($params[$key]['type'], $parameter->getType());
            }
        }

        return $params;
    }

    /**
     * Set response state.
     *
     * @return Response
     */
    protected function getReadyResponse()
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();
        $response->setServiceMap($this->getServiceMap());

        if (null !== ($id = $request->getId())) {
            $response->setId($id);
        }

        if (null !== ($version = $request->getVersion())) {
            $response->setVersion($version);
        }

        return $response;
    }

    /**
     * Get method return type.
     *
     * @param  Method\Definition $method
     * @return string|array
     */
    protected function getReturnType(Method\Definition $method)
    {
        $return = [];
        foreach ($method->getPrototypes() as $prototype) {
            $return[] = $prototype->getReturnType();
        }

        if (1 == count($return)) {
            return $return[0];
        }

        return $return;
    }

    /**
     * Retrieve list of allowed SMD methods for proxying.
     *
     * Lazy-loads the list on first retrieval.
     *
     * @return array
     */
    protected function getSmdMethods()
    {
        if (null !== $this->smdMethods) {
            return $this->smdMethods;
        }

        $this->smdMethods = [];

        foreach (get_class_methods(Smd::class) as $method) {
            if (! preg_match('/^(set|get)/', $method)) {
                continue;
            }

            if (strstr($method, 'Service')) {
                continue;
            }

            $this->smdMethods[] = $method;
        }

        return $this->smdMethods;
    }

    /**
     * Internal method for handling request.
     *
     * @return void
     */
    protected function handleRequest()
    {
        $request = $this->getRequest();

        if ($request->isParseError()) {
            return $this->fault('Parse error', Error::ERROR_PARSE);
        }

        if (! $request->isMethodError() && null === $request->getMethod()) {
            return $this->fault('Invalid Request', Error::ERROR_INVALID_REQUEST);
        }

        if ($request->isMethodError()) {
            return $this->fault('Invalid Request', Error::ERROR_INVALID_REQUEST);
        }

        $method = $request->getMethod();
        if (! $this->table->hasMethod($method)) {
            return $this->fault('Method not found', Error::ERROR_INVALID_METHOD);
        }

        $params        = $request->getParams();
        $invokable     = $this->table->getMethod($method);
        $serviceMap    = $this->getServiceMap();
        $service       = $serviceMap->getService($method);
        $serviceParams = $service->getParams();

        if (count($params) < count($serviceParams)) {
            $params = $this->getDefaultParams($params, $serviceParams);
        }

        // Make sure named parameters are passed in correct order.
        if (is_string(key($params))) {
            $callback = $invokable->getCallback();
            if ('function' == $callback->getType()) {
                $reflection = new ReflectionFunction($callback->getFunction());
            } else {
                $reflection = new ReflectionMethod(
                    $callback->getClass(),
                    $callback->getMethod()
                );
            }

            $orderedParams = [];
            foreach ($reflection->getParameters() as $refParam) {
                if (array_key_exists($refParam->getName(), $params)) {
                    $orderedParams[$refParam->getName()] = $params[$refParam->getName()];
                    continue;
                }

                if ($refParam->isOptional()) {
                    $orderedParams[$refParam->getName()] = null;
                    continue;
                }

                return $this->fault('Invalid params', Error::ERROR_INVALID_PARAMS);
            }

            $params = $orderedParams;
        }

        try {
            $result = $this->_dispatch($invokable, $params);
        } catch (PhpException $e) {
            return $this->fault($e->getMessage(), $e->getCode(), $e);
        }

        $this->getResponse()->setResult($result);
    }
}
