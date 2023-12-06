<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Error;
use Laminas\Json\Server\Exception\RuntimeException;
use Laminas\Json\Server\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private Response $response;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function testResultShouldBeNullByDefault(): void
    {
        self::assertNull($this->response->getResult());
    }

    public function testResultAccessorsShouldWorkWithNormalInput(): void
    {
        foreach ([true, 'foo', 2, 2.0, [], ['foo' => 'bar']] as $result) {
            $this->response->setResult($result);
            self::assertEquals($result, $this->response->getResult());
        }
    }

    public function testResultShouldNotBeErrorByDefault(): void
    {
        self::assertFalse($this->response->isError());
    }

    public function testSettingErrorShouldMarkRequestAsError(): void
    {
        $error = new Error();
        $this->response->setError($error);
        self::assertTrue($this->response->isError());
    }

    public function testShouldBeAbleToRetrieveErrorObject(): void
    {
        $error = new Error();
        $this->response->setError($error);
        self::assertSame($error, $this->response->getError());
    }

    public function testErrorAccesorsShouldWorkWithNullInput(): void
    {
        $this->response->setError(null);
        self::assertNull($this->response->getError());
        self::assertFalse($this->response->isError());
    }

    public function testIdShouldBeNullByDefault(): void
    {
        self::assertNull($this->response->getId());
    }

    public function testIdAccessorsShouldWorkWithNormalInput(): void
    {
        $this->response->setId('foo');
        self::assertEquals('foo', $this->response->getId());
    }

    public function testVersionShouldBeNullByDefault(): void
    {
        self::assertNull($this->response->getVersion());
    }

    public function testVersionShouldBeLimitedToV2(): void
    {
        $this->response->setVersion('2.0');
        self::assertEquals('2.0', $this->response->getVersion());
        foreach (['a', 1, '1.0', true] as $version) {
            $this->response->setVersion($version);
            self::assertNull($this->response->getVersion());
        }
    }

    public function testShouldBeAbleToLoadResponseFromJSONString(): void
    {
        $options = $this->getOptions();
        $json    = Json::encode($options);
        $this->response->loadJSON($json);

        self::assertEquals('foobar', $this->response->getId());
        self::assertEquals($options['result'], $this->response->getResult());
    }

    public function testLoadingFromJSONShouldSetJSONRpcVersionWhenPresent(): void
    {
        $options            = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json               = Json::encode($options);
        $this->response->loadJSON($json);
        self::assertEquals('2.0', $this->response->getVersion());
    }

    public function testResponseShouldBeAbleToCastToJSON(): void
    {
        $this->response->setResult(true)
                       ->setId('foo')
                       ->setVersion('2.0');
        $json = $this->response->toJSON();
        $test = Json::decode($json, Json::TYPE_ARRAY);

        self::assertIsArray($test);
        self::assertArrayHasKey('result', $test);
        self::assertArrayNotHasKey('error', $test, "'error' may not coexist with 'result'");
        self::assertArrayHasKey('id', $test);
        self::assertArrayHasKey('jsonrpc', $test);

        self::assertTrue($test['result']);
        self::assertEquals($this->response->getId(), $test['id']);
        self::assertEquals($this->response->getVersion(), $test['jsonrpc']);
    }

    public function testResponseShouldCastErrorToJSONIfIsError(): void
    {
        $error = new Error();
        $error->setCode(Error::ERROR_INTERNAL)
              ->setMessage('error occurred');
        $this->response->setId('foo')
                       ->setResult(true)
                       ->setError($error);
        $json = $this->response->toJSON();
        $test = Json::decode($json, Json::TYPE_ARRAY);

        self::assertIsArray($test);
        self::assertArrayNotHasKey('result', $test, "'result' may not coexist with 'error'");
        self::assertArrayHasKey('error', $test);
        self::assertArrayHasKey('id', $test);
        self::assertArrayNotHasKey('jsonrpc', $test);

        self::assertEquals($this->response->getId(), $test['id']);
        self::assertEquals($error->getCode(), $test['error']['code']);
        self::assertEquals($error->getMessage(), $test['error']['message']);
    }

    public function testCastToStringShouldCastToJSON(): void
    {
        $this->response->setResult(true)
                       ->setId('foo');
        $json = $this->response->__toString();
        $test = Json::decode($json, Json::TYPE_ARRAY);

        self::assertIsArray($test);
        self::assertArrayHasKey('result', $test);
        self::assertArrayNotHasKey('error', $test, "'error' may not coexist with 'result'");
        self::assertArrayHasKey('id', $test);
        self::assertArrayNotHasKey('jsonrpc', $test);

        self::assertTrue($test['result']);
        self::assertEquals($this->response->getId(), $test['id']);
    }

    /**
     * @param string $json
     * @group 5956
     * @dataProvider provideScalarJSONResponses
     */
    public function testLoadingScalarJSONResponseShouldThrowException($json): void
    {
        $this->expectException(RuntimeException::class);
        $this->response->loadJson($json);
    }

    /**
     * @return string[][]
     */
    public static function provideScalarJSONResponses(): array
    {
        return [[''], ['true'], ['null'], ['3'], ['"invalid"']];
    }

    public function getOptions(): array
    {
        return [
            'result' => [
                5,
                'four',
                true,
            ],
            'id'     => 'foobar',
        ];
    }

    /**
     * @see https://github.com/zendframework/zend-json-server/pull/2
     */
    public function testValueOfZeroForOptionsKeyShouldNotBeInterpretedAsVersionKey(): void
    {
        $this->response->setOptions([
            0 => '2.0',
        ]);
        self::assertNull($this->response->getVersion());
    }

    /**
     * Assert that error data can be omitted
     *
     * @see https://www.jsonrpc.org/specification#response_object
     */
    public function testSetOptionsAcceptsErrorWithEmptyDate(): void
    {
        $this->response->setOptions([
            'error' => [
                'code'    => 0,
                'message' => 'Lorem Ipsum',
            ],
        ]);
        self::assertInstanceOf(Error::class, $this->response->getError());
        self::assertEquals(-32000, $this->response->getError()->getCode());
        self::assertEquals('Lorem Ipsum', $this->response->getError()->getMessage());
        self::assertEquals(null, $this->response->getError()->getData());
    }
}
