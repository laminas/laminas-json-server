<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use Laminas\Http\Response as HttpResponse;
use Laminas\Json\Json;
use Laminas\Json\Server\Client;
use Laminas\Json\Server\Error;
use Laminas\Json\Server\Exception;
use Laminas\Json\Server\Request;
use Laminas\Json\Server\Response;
use PHPUnit\Framework\TestCase;

use function count;
use function implode;
use function strlen;

class ClientTest extends TestCase
{
    /** @var TestAdapter */
    protected $httpAdapter;

    /** @var HttpClient */
    protected $httpClient;

    /** @var Client */
    protected $jsonClient;

    /** @var HttpClient */
    protected $mockedHttpClient;

    public function setUp(): void
    {
        $this->httpAdapter = new TestAdapter();
        $this->httpClient  = new HttpClient(
            'http://foo',
            ['adapter' => $this->httpAdapter]
        );

        $this->jsonClient = new Client('http://foo');
        $this->jsonClient->setHttpClient($this->httpClient);
    }

    public function testGettingDefaultHttpClient(): void
    {
        $jsonClient = new Client('http://foo');
        $httpClient = $jsonClient->getHttpClient();
        self::assertInstanceOf(HttpClient::class, $httpClient);
        self::assertSame($httpClient, $jsonClient->getHttpClient());
    }

    public function testSettingAndGettingHttpClient(): void
    {
        $jsonClient = new Client('http://foo');
        self::assertNotSame($this->httpClient, $jsonClient->getHttpClient());

        $jsonClient->setHttpClient($this->httpClient);
        self::assertSame($this->httpClient, $jsonClient->getHttpClient());
    }

    public function testSettingHttpClientViaConstructor(): void
    {
        $jsonClient = new Client('http://foo', $this->httpClient);
        $httpClient = $jsonClient->getHttpClient();
        self::assertSame($this->httpClient, $httpClient);
    }

    public function testLastRequestAndResponseAreInitiallyNull(): void
    {
        self::assertNull($this->jsonClient->getLastRequest());
        self::assertNull($this->jsonClient->getLastResponse());
    }

    public function testLastRequestAndResponseAreSetAfterRpcMethodCall(): void
    {
        $this->setServerResponseTo(true);
        $this->jsonClient->call('foo');

        self::assertInstanceOf(Request::class, $this->jsonClient->getLastRequest());
        self::assertInstanceOf(Response::class, $this->jsonClient->getLastResponse());
    }

    public function testSuccessfulRpcMethodCallWithNoParameters(): void
    {
        $expectedMethod = 'foo';
        $expectedReturn = 7;

        $this->setServerResponseTo($expectedReturn);
        self::assertSame($expectedReturn, $this->jsonClient->call($expectedMethod));

        $request  = $this->jsonClient->getLastRequest();
        $response = $this->jsonClient->getLastResponse();

        self::assertSame($expectedMethod, $request->getMethod());
        self::assertSame([], $request->getParams());
        self::assertSame($expectedReturn, $response->getResult());
        self::assertFalse($response->isError());
    }

    public function testSuccessfulRpcMethodCallWithParameters(): void
    {
        $expectedMethod = 'foobar';
        $expectedParams = [1, 1.1, true, 'foo' => 'bar'];
        $expectedReturn = [7, false, 'foo' => 'bar'];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->jsonClient->call($expectedMethod, $expectedParams);
        self::assertSame($expectedReturn, $actualReturn);

        $request  = $this->jsonClient->getLastRequest();
        $response = $this->jsonClient->getLastResponse();

        self::assertSame($expectedMethod, $request->getMethod());
        $params = $request->getParams();
        self::assertSame(count($expectedParams), count($params));
        self::assertSame($expectedParams[0], $params[0]);
        self::assertSame($expectedParams[1], $params[1]);
        self::assertSame($expectedParams[2], $params[2]);
        self::assertSame($expectedParams['foo'], $params['foo']);

        self::assertSame($expectedReturn, $response->getResult());
        self::assertFalse($response->isError());
    }

    public function testRpcMethodCallThrowsOnHttpFailure(): void
    {
        $status  = 404;
        $message = 'Not Found';
        $body    = 'oops';

        $response = $this->makeHttpResponseFrom($body, $status, $message);
        $this->httpAdapter->setResponse($response);

        $this->expectException(Exception\HttpException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($status);
        $this->jsonClient->call('foo');
    }

    public function testRpcMethodCallThrowsOnJsonRpcFault(): void
    {
        $code    = -32050;
        $message = 'foo';

        $error    = new Error($message, $code);
        $response = new Response();
        $response->setError($error);
        $json = $response->toJson();

        $response = $this->makeHttpResponseFrom($json);
        $this->httpAdapter->setResponse($response);

        $this->expectException(Exception\ErrorException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);
        $this->jsonClient->call('foo');
    }

    public function testSettingUriOnHttpClientIsNotOverwrittenByJsonRpcClient(): void
    {
        $changedUri = 'http://bar:80/';
        // Overwrite: http://foo:80
        $this->setServerResponseTo(null);
        $this->jsonClient->getHttpClient()->setUri($changedUri);
        $this->jsonClient->call('foo');
        $uri = $this->jsonClient->getHttpClient()->getUri()->toString();

        self::assertEquals($changedUri, $uri);
    }

    public function testSettingNoHttpClientUriForcesClientToSetUri(): void
    {
        $baseUri           = 'http://foo:80/';
        $this->httpAdapter = new TestAdapter();
        $this->httpClient  = new HttpClient(null, ['adapter' => $this->httpAdapter]);

        $this->jsonClient = new Client($baseUri);
        $this->jsonClient->setHttpClient($this->httpClient);

        $this->setServerResponseTo(null);
        self::assertNull($this->jsonClient->getHttpClient()->getRequest()->getUriString());
        $this->jsonClient->call('foo');
        $uri = $this->jsonClient->getHttpClient()->getUri();

        self::assertEquals($baseUri, $uri->toString());
    }

    public function testCustomHttpClientUserAgentIsNotOverridden(): void
    {
        self::assertFalse(
            $this->httpClient->getHeader('User-Agent'),
            'UA is null if no request was made'
        );
        $this->setServerResponseTo(null);
        self::assertNull($this->jsonClient->call('method'));
        self::assertSame(
            'Laminas_Json_Server_Client',
            $this->httpClient->getHeader('User-Agent'),
            'If no custom UA is set, set Laminas_Json_Server_Client'
        );

        $expectedUserAgent = 'Laminas_Json_Server_Client (custom)';
        $this->httpClient->setHeaders(['User-Agent' => $expectedUserAgent]);

        $this->setServerResponseTo(null);
        self::assertNull($this->jsonClient->call('method'));
        self::assertSame($expectedUserAgent, $this->httpClient->getHeader('User-Agent'));
    }

    /**
     * @group 5956
     */
    public function testScalarServerResponseThrowsException(): void
    {
        $response = $this->makeHttpResponseFrom('false');
        $this->httpAdapter->setResponse($response);
        $this->expectException(Exception\ExceptionInterface::class);
        $this->jsonClient->call('foo');
    }

    // Helpers

    /**
     * @param mixed $nativeVars
     */
    public function setServerResponseTo($nativeVars): void
    {
        $response = $this->getServerResponseFor($nativeVars);
        $this->httpAdapter->setResponse($response);
    }

    public function testClientShouldSetDefaultAcceptAndContentTypeHeadersOnRequest(): void
    {
        $request  = new Request();
        $response = new HttpResponse();
        $response->setContent(Json::encode(['test' => 'test']));
        $testAdapter = new TestAdapter();
        $testAdapter->setResponse($response);
        $jsonClient = new Client('http://foo');
        $jsonClient->getHttpClient()->setAdapter($testAdapter);
        $jsonClient->doRequest($request);
        self::assertSame('application/json-rpc', $jsonClient->getHttpClient()->getHeader('Content-Type'));
        self::assertSame('application/json-rpc', $jsonClient->getHttpClient()->getHeader('Accept'));
    }

    public function testClientShouldNotOverwriteAcceptAndContentTypeHeadersIfAlreadyPresentInRequest(): void
    {
        $request  = new Request();
        $response = new HttpResponse();
        $response->setContent(Json::encode(['test' => 'test']));
        $testAdapter = new TestAdapter();
        $testAdapter->setResponse($response);

        $httpClient = new HttpClient();
        $httpClient->setHeaders([
            'Content-Type' => 'application/jsonrequest',
            'Accept'       => 'application/jsonrequest',
        ]);

        $jsonClient = new Client('http://foo', $httpClient);
        $jsonClient->getHttpClient()->setAdapter($testAdapter);
        $jsonClient->doRequest($request);
        self::assertSame('application/jsonrequest', $jsonClient->getHttpClient()->getHeader('Content-Type'));
        self::assertSame('application/jsonrequest', $jsonClient->getHttpClient()->getHeader('Accept'));
    }

    /**
     * @param mixed $nativeVars
     */
    public function getServerResponseFor($nativeVars): string
    {
        $response = new Response();
        $response->setResult($nativeVars);
        $json = $response->toJson();

        return $this->makeHttpResponseFrom($json);
    }

    public function makeHttpResponseFrom(string $data, int $status = 200, string $message = 'OK'): string
    {
        $headers = [
            "HTTP/1.1 $status $message",
            "Status: $status",
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
        ];
        return implode("\r\n", $headers) . "\r\n\r\n$data\r\n\r\n";
    }

    /**
     * @param mixed $nativeVars
     */
    public function makeHttpResponseFor($nativeVars): HttpResponse
    {
        $response = $this->getServerResponseFor($nativeVars);
        return HttpResponse::fromString($response);
    }

    public function mockHttpClient(): void
    {
        $this->mockedHttpClient = $this->createMock(HttpClient::class);
        $this->jsonClient->setHttpClient($this->mockedHttpClient);
    }
}
