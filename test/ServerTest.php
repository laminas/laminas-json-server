<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json;
use Laminas\Json\Server;
use Laminas\Json\Server\Error;
use Laminas\Json\Server\Request;
use Laminas\Json\Server\Response;
use Laminas\Server\Reflection\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

use function count;
use function get_class_methods;
use function ob_get_clean;
use function ob_start;
use function var_export;

class ServerTest extends TestCase
{
    /** @var Server\Server */
    protected $server;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->server = new Server\Server();
    }

    public function testShouldBeAbleToBindFunctionToServer(): void
    {
        $this->server->addFunction('strtolower');
        $methods = $this->server->getFunctions();
        self::assertTrue($methods->hasMethod('strtolower'));
    }

    public function testShouldBeAbleToBindCallbackToServer(): void
    {
        try {
            $this->server->addFunction([$this, 'setUp']);
        } catch (RuntimeException $e) {
            $this->markTestIncomplete('PHPUnit docblocks may be incorrect');
        }
        $methods = $this->server->getFunctions();
        self::assertTrue($methods->hasMethod('setUp'));
    }

    public function testShouldBeAbleToBindClassToServer(): void
    {
        $this->server->setClass(Server\Server::class);
        $test = $this->server->getFunctions();
        self::assertNotEmpty($test);
    }

    public function testBindingClassToServerShouldRegisterAllPublicMethods(): void
    {
        $this->server->setClass(Server\Server::class);
        $test    = $this->server->getFunctions();
        $methods = get_class_methods(Server\Server::class);
        foreach ($methods as $method) {
            if ('_' === $method[0]) {
                continue;
            }
            self::assertTrue(
                $test->hasMethod($method),
                'Testing for method ' . $method . ' against ' . var_export($test, true)
            );
        }
    }

    public function testShouldBeAbleToBindObjectToServer(): void
    {
        $object = new Server\Server();
        $this->server->setClass($object);
        $test = $this->server->getFunctions();
        self::assertNotEmpty($test);
    }

    public function testBindingObjectToServerShouldRegisterAllPublicMethods(): void
    {
        $object = new Server\Server();
        $this->server->setClass($object);
        $test    = $this->server->getFunctions();
        $methods = get_class_methods($object);
        foreach ($methods as $method) {
            if ('_' === $method[0]) {
                continue;
            }
            self::assertTrue(
                $test->hasMethod($method),
                'Testing for method ' . $method . ' against ' . var_export($test, true)
            );
        }
    }

    public function testShouldBeAbleToBindMultipleClassesAndObjectsToServer(): void
    {
        $this->server->setClass(Server\Server::class)
                     ->setClass(new Json\Json());
        $methods    = $this->server->getFunctions();
        $zjsMethods = get_class_methods(Server\Server::class);
        $zjMethods  = get_class_methods(Json\Json::class);
        self::assertGreaterThan(count($zjsMethods), count($methods));
        self::assertGreaterThan(count($zjMethods), count($methods));
    }

    public function testNamingCollisionsShouldResolveToLastRegisteredMethod(): void
    {
        $this->server->setClass(Request::class)
                     ->setClass(Response::class);
        $methods = $this->server->getFunctions();
        self::assertTrue($methods->hasMethod('toJson'));
        $toJson = $methods->getMethod('toJson');
        self::assertEquals(Response::class, $toJson->getCallback()->getClass());
    }

    public function testGetRequestShouldInstantiateRequestObjectByDefault(): void
    {
        $request = $this->server->getRequest();
        self::assertInstanceOf(Request::class, $request);
    }

    public function testShouldAllowSettingRequestObjectManually(): void
    {
        $orig = $this->server->getRequest();
        $new  = new Request();
        $this->server->setRequest($new);
        $test = $this->server->getRequest();
        self::assertSame($new, $test);
        self::assertNotSame($orig, $test);
    }

    public function testGetResponseShouldInstantiateResponseObjectByDefault(): void
    {
        $response = $this->server->getResponse();
        self::assertInstanceOf(Response::class, $response);
    }

    public function testShouldAllowSettingResponseObjectManually(): void
    {
        $orig = $this->server->getResponse();
        $new  = new Response();
        $this->server->setResponse($new);
        $test = $this->server->getResponse();
        self::assertSame($new, $test);
        self::assertNotSame($orig, $test);
    }

    public function testFaultShouldCreateErrorResponse(): void
    {
        $response = $this->server->getResponse();
        self::assertFalse($response->isError());
        $this->server->fault('error condition', -32000);
        self::assertTrue($response->isError());
        $error = $response->getError();
        self::assertEquals(-32000, $error->getCode());
        self::assertEquals('error condition', $error->getMessage());
    }

    public function testResponseShouldBeEmittedAutomaticallyByDefault(): void
    {
        self::assertFalse($this->server->getReturnResponse());
    }

    public function testShouldBeAbleToDisableAutomaticResponseEmission(): void
    {
        $this->testResponseShouldBeEmittedAutomaticallyByDefault();
        $this->server->setReturnResponse(true);
        self::assertTrue($this->server->getReturnResponse());
    }

    public function testShouldBeAbleToRetrieveSmdObject(): void
    {
        $smd = $this->server->getServiceMap();
        self::assertInstanceOf(Server\Smd::class, $smd);
    }

    public function testShouldBeAbleToSetArbitrarySmdMetadata(): void
    {
        $this->server->setTransport('POST')
                     ->setEnvelope('JSON-RPC-1.0')
                     ->setContentType('application/x-json')
                     ->setTarget('/foo/bar')
                     ->setId('foobar')
                     ->setDescription('This is a test service');

        self::assertEquals('POST', $this->server->getTransport());
        self::assertEquals('JSON-RPC-1.0', $this->server->getEnvelope());
        self::assertEquals('application/x-json', $this->server->getContentType());
        self::assertEquals('/foo/bar', $this->server->getTarget());
        self::assertEquals('foobar', $this->server->getId());
        self::assertEquals('This is a test service', $this->server->getDescription());
    }

    public function testSmdObjectRetrievedFromServerShouldReflectServerState(): void
    {
        $this->server->addFunction('strtolower')
                     ->setClass(Server\Server::class)
                     ->setTransport('POST')
                     ->setEnvelope('JSON-RPC-1.0')
                     ->setContentType('application/x-json')
                     ->setTarget('/foo/bar')
                     ->setId('foobar')
                     ->setDescription('This is a test service');
        $smd = $this->server->getServiceMap();
        self::assertEquals('POST', $this->server->getTransport());
        self::assertEquals('JSON-RPC-1.0', $this->server->getEnvelope());
        self::assertEquals('application/x-json', $this->server->getContentType());
        self::assertEquals('/foo/bar', $this->server->getTarget());
        self::assertEquals('foobar', $this->server->getId());
        self::assertEquals('This is a test service', $this->server->getDescription());

        $services = $smd->getServices();
        self::assertIsArray($services);
        self::assertNotEmpty($services);
        self::assertArrayHasKey('strtolower', $services);
        $methods = get_class_methods(Server\Server::class);
        foreach ($methods as $method) {
            if ('_' === $method[0]) {
                continue;
            }
            self::assertArrayHasKey($method, $services);
        }
    }

    public function testHandleValidMethodShouldWork(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->addFunction(__NAMESPACE__ . '\\TestAsset\\FooFunc')
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true, 'foo', 'bar'])
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());

        $request->setMethod(__NAMESPACE__ . '\\TestAsset\\FooFunc')
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());
    }

    public function testHandleValidMethodWithNULLParamValueShouldWork(): void
    {
        $this->server->setClass(__NAMESPACE__ . '\\TestAsset\\Foo')
                     ->addFunction(__NAMESPACE__ . '\\TestAsset\\FooFunc')
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true, null, 'bar'])
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());
    }

    public function testHandleValidMethodWithTooFewParamsShouldPassDefaultsOrNullsForMissingParams(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true])
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());
        $result = $response->getResult();
        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertEquals('two', $result[1], var_export($result, true));
        self::assertNull($result[2]);
    }

    public function testHandleValidMethodWithTooFewAssociativeParamsShouldPassDefaultsOrNullsForMissingParams(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(['one' => true])
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());
        $result = $response->getResult();
        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertEquals('two', $result[1], var_export($result, true));
        self::assertNull($result[2]);
    }

    public function testHandleValidMethodWithTooManyParamsShouldWork(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true, 'foo', 'bar', 'baz'])
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isError());
        $result = $response->getResult();
        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertEquals('foo', $result[1]);
        self::assertEquals('bar', $result[2]);
    }

    public function testHandleShouldAllowNamedParamsInAnyOrder1(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([
                    'three' => 3,
                    'two'   => 2,
                    'one'   => 1,
                ])
                ->setId('foo');
        $response = $this->server->handle();
        $result   = $response->getResult();

        self::assertIsArray($result);
        self::assertEquals(1, $result[0]);
        self::assertEquals(2, $result[1]);
        self::assertEquals(3, $result[2]);
    }

    public function testHandleShouldAllowNamedParamsInAnyOrder2(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([
                    'three' => 3,
                    'one'   => 1,
                    'two'   => 2,
                ])
                ->setId('foo');
        $response = $this->server->handle();
        $result   = $response->getResult();

        self::assertIsArray($result);
        self::assertEquals(1, $result[0]);
        self::assertEquals(2, $result[1]);
        self::assertEquals(3, $result[2]);
    }

    public function testHandleValidWithoutRequiredParamShouldReturnError(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([
                    'three' => 3,
                    'two'   => 2,
                ])
                ->setId('foo');
        $response = $this->server->handle();

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isError());
        self::assertEquals(Server\Error::ERROR_INVALID_PARAMS, $response->getError()->getCode());
    }

    public function testHandleRequestWithErrorsShouldReturnErrorResponse(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isError());
        self::assertEquals(Server\Error::ERROR_INVALID_REQUEST, $response->getError()->getCode());
    }

    public function testHandleRequestWithInvalidMethodShouldReturnErrorResponse(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bogus')
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isError());
        self::assertEquals(Server\Error::ERROR_INVALID_METHOD, $response->getError()->getCode());
    }

    public function testHandleRequestWithExceptionShouldReturnErrorResponse(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('baz')
                ->setId('foo');
        $response = $this->server->handle();
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isError());
        self::assertEquals(Server\Error::ERROR_OTHER, $response->getError()->getCode());
        self::assertEquals('application error', $response->getError()->getMessage());
    }

    public function testHandleShouldEmitResponseByDefault(): void
    {
        $this->server->setClass(TestAsset\Foo::class);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true, 'foo', 'bar'])
                ->setId('foo');
        ob_start();
        $this->server->handle();
        $buffer = ob_get_clean();

        $decoded = Json\Json::decode($buffer, Json\Json::TYPE_ARRAY);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('result', $decoded);
        self::assertArrayHasKey('id', $decoded);

        $response = $this->server->getResponse();
        self::assertEquals($response->getResult(), $decoded['result']);
        self::assertEquals($response->getId(), $decoded['id']);
    }

    public function testResponseShouldBeEmptyWhenRequestHasNoId(): void
    {
        $this->server->setClass(TestAsset\Foo::class);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([true, 'foo', 'bar']);
        ob_start();
        $this->server->handle();
        $buffer = ob_get_clean();

        self::assertEmpty($buffer);
    }

    public function testLoadFunctionsShouldLoadResultOfGetFunctions(): void
    {
        $this->server->setClass(TestAsset\Foo::class);
        $functions = $this->server->getFunctions();
        $server    = new Server\Server();
        $server->loadFunctions($functions);
        self::assertEquals($functions->toArray(), $server->getFunctions()->toArray());
    }

    /**
     * @group Laminas-4604
     */
    public function testAddFunctionAndClassThatContainsConstructor(): void
    {
        $bar = new TestAsset\Bar('unique');

        $this->server->addFunction([$bar, 'foo']);

        $request = $this->server->getRequest();
        $request->setMethod('foo')
            ->setParams([true, 'foo', 'bar'])
            ->setId('foo');
        ob_start();
        $this->server->handle();
        $buffer = ob_get_clean();

        $decoded = Json\Json::decode($buffer, Json\Json::TYPE_ARRAY);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('result', $decoded);
        self::assertArrayHasKey('id', $decoded);
        self::assertContains('unique', $decoded['result']);

        $response = $this->server->getResponse();
        self::assertEquals($response->getResult(), $decoded['result']);
        self::assertEquals($response->getId(), $decoded['id']);
    }

    /**
     * @group 3773
     */
    public function testHandleWithNamedParamsShouldSetMissingDefaults1(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([
                    'two' => 2,
                    'one' => 1,
                ])
                ->setId('foo');
        $response = $this->server->handle();
        $result   = $response->getResult();

        self::assertIsArray($result);
        self::assertEquals(1, $result[0]);
        self::assertEquals(2, $result[1]);
        self::assertEquals(null, $result[2]);
    }

    /**
     * @group 3773
     */
    public function testHandleWithNamedParamsShouldSetMissingDefaults2(): void
    {
        $this->server->setClass(TestAsset\Foo::class)
                     ->setReturnResponse(true);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams([
                    'three' => 3,
                    'one'   => 1,
                ])
                ->setId('foo');
        $response = $this->server->handle();
        $result   = $response->getResult();

        self::assertIsArray($result);
        self::assertEquals(1, $result[0]);
        self::assertEquals('two', $result[1]);
        self::assertEquals(3, $result[2]);
    }

    public function testResponseShouldBeInvalidWhenRequestHasLessRequiredParametersPassedWithoutKeys(): void
    {
        $server = $this->server;
        $server->setClass(TestAsset\FooParameters::class);
        $server->setReturnResponse(true);
        $request = $server->getRequest();
        $request->setMethod('bar')
                ->setParams([true]);
        $server->handle();

        $response = $server->getResponse();
        self::assertEquals(Error::ERROR_INVALID_PARAMS, $response->getError()->getCode());
    }

    public function testResponseShouldBeInvalidWhenRequestHasLessRequiredParametersPassedWithoutKeys1(): void
    {
        $server = $this->server;
        $server->setClass(TestAsset\FooParameters::class);
        $server->setReturnResponse(true);
        $request = $server->getRequest();
        $request->setMethod('baz')
                ->setParams([true]);
        $server->handle();
        $response = $server->getResponse();
        self::assertNotEmpty($response->getError());
        self::assertEquals(Error::ERROR_INVALID_PARAMS, $response->getError()->getCode());
    }
}
