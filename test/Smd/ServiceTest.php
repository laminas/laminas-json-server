<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server\Smd;

use Laminas\Json\Json;
use Laminas\Json\Server\Exception;
use Laminas\Json\Server\Smd;
use Laminas\Json\Server\Smd\Service;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_shift;
use function var_export;

class ServiceTest extends TestCase
{
    /** @var Service */
    protected $service;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->service = new Service('foo');
    }

    public function testConstructorShouldThrowExceptionWhenNoNameSetWhenNullProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a name');
        new Service(null);
    }

    public function testConstructorShouldThrowExceptionWhenNoNameSetWhenArrayProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a name');
        new Service(null);
    }

    public function testSettingNameShouldThrowExceptionWhenContainingInvalidFormatStartingWithInt(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid name');
        $this->service->setName('0ab-?');
    }

    public function testSettingNameShouldNotThrowExceptionWhenContainingValidFormatStartingWithUnderscore(): void
    {
        $this->service->setName('_getMyProperty');
        self::assertEquals('_getMyProperty', $this->service->getName());
    }

    public function testSettingNameShouldThrowExceptionWhenContainingInvalidFormatStartingWithRpc(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid name');
        $this->service->setName('rpc.Foo');
    }

    // phpcs:ignore
    public function testSettingNameShouldThrowExceptionWhenContainingInvalidFormatStartingWithRpcWithoutPeriodChar(): void
    {
        $this->service->setName('rpcFoo');
        self::assertEquals('rpcFoo', $this->service->getName());
    }

    // phpcs:ignore
    public function testSettingNameShouldNotThrowExceptionWhenContainingInvalidFormatStartingWithRpcInsensitiveCase(): void
    {
        $this->service->setName('RpcFoo');
        self::assertEquals('RpcFoo', $this->service->getName());
    }

    public function testSettingNameShouldNotThrowExceptionWhenContainingValidFormatContainingRpc(): void
    {
        $this->service->setName('_rpcFoo');
        self::assertEquals('_rpcFoo', $this->service->getName());

        $this->service->setName('MyRpcFoo');
        self::assertEquals('MyRpcFoo', $this->service->getName());
    }

    public function testNameAccessorsShouldWorkWithNormalInput(): void
    {
        self::assertEquals('foo', $this->service->getName());
        $this->service->setName('bar');
        self::assertEquals('bar', $this->service->getName());
    }

    public function testTransportShouldDefaultToPost(): void
    {
        self::assertEquals('POST', $this->service->getTransport());
    }

    public function testSettingTransportThrowsExceptionWhenSetToGet(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid transport');
        $this->service->setTransport('GET');
    }

    public function testSettingTransportThrowsExceptionWhenSetToRest(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid transport');
        $this->service->setTransport('REST');
    }

    public function testTransportAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->service->setTransport('POST');
        self::assertEquals('POST', $this->service->getTransport());
    }

    public function testTargetShouldBeNullInitially(): void
    {
        self::assertNull($this->service->getTarget());
    }

    public function testTargetAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testTargetShouldBeNullInitially();
        $this->service->setTarget('foo');
        self::assertEquals('foo', $this->service->getTarget());
    }

    public function testTargetAccessorsShouldNormalizeToString(): void
    {
        $this->testTargetShouldBeNullInitially();
        $this->service->setTarget(123);
        $value = $this->service->getTarget();
        self::assertIsString($value);
        self::assertEquals((string) 123, $value);
    }

    public function testEnvelopeShouldBeJSONRpc1CompliantByDefault(): void
    {
        self::assertEquals(Smd::ENV_JSONRPC_1, $this->service->getEnvelope());
    }

    public function testEnvelopeShouldOnlyComplyWithJSONRpc1And2(): void
    {
        $this->testEnvelopeShouldBeJSONRpc1CompliantByDefault();
        $this->service->setEnvelope(Smd::ENV_JSONRPC_2);
        self::assertEquals(Smd::ENV_JSONRPC_2, $this->service->getEnvelope());
        $this->service->setEnvelope(Smd::ENV_JSONRPC_1);
        self::assertEquals(Smd::ENV_JSONRPC_1, $this->service->getEnvelope());
        try {
            $this->service->setEnvelope('JSON-P');
            $this->fail('Should not be able to set non-JSON-RPC spec envelopes');
        } catch (Exception\InvalidArgumentException $e) {
            self::assertStringContainsString('Invalid envelope', $e->getMessage());
        }
    }

    public function testShouldHaveNoParamsByDefault(): void
    {
        $params = $this->service->getParams();
        self::assertEmpty($params);
    }

    public function testShouldBeAbleToAddParamsByTypeOnly(): void
    {
        $this->service->addParam('integer');
        $params = $this->service->getParams();
        self::assertCount(1, $params);
        $param = array_shift($params);
        self::assertEquals('integer', $param['type']);
    }

    public function testParamsShouldAcceptArrayOfTypes(): void
    {
        $type = ['integer', 'string'];
        $this->service->addParam($type);
        $params = $this->service->getParams();
        $param  = array_shift($params);
        $test   = $param['type'];
        self::assertIsArray($test);
        self::assertEquals($type, $test);
    }

    public function testInvalidParamTypeShouldThrowException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param type');
        $this->service->addParam(new stdClass());
    }

    public function testShouldBeAbleToOrderParams(): void
    {
        $this->service->addParam('integer', [], 4)
                      ->addParam('string')
                      ->addParam('boolean', [], 3);
        $params = $this->service->getParams();

        self::assertCount(3, $params);

        $param = array_shift($params);
        self::assertEquals('string', $param['type'], var_export($params, true));
        $param = array_shift($params);
        self::assertEquals('boolean', $param['type'], var_export($params, true));
        $param = array_shift($params);
        self::assertEquals('integer', $param['type'], var_export($params, true));
    }

    public function testShouldBeAbleToAddArbitraryParamOptions(): void
    {
        $this->service->addParam(
            'integer',
            [
                'name'        => 'foo',
                'optional'    => false,
                'default'     => 1,
                'description' => 'Foo parameter',
            ]
        );
        $params = $this->service->getParams();
        $param  = array_shift($params);
        self::assertEquals('foo', $param['name']);
        self::assertFalse($param['optional']);
        self::assertEquals(1, $param['default']);
        self::assertEquals('Foo parameter', $param['description']);
    }

    public function testShouldBeAbleToAddMultipleParamsAtOnce(): void
    {
        $this->service->addParams([
            ['type' => 'integer', 'order' => 4],
            ['type' => 'string', 'name' => 'foo'],
            ['type' => 'boolean', 'order' => 3],
        ]);
        $params = $this->service->getParams();

        self::assertCount(3, $params);
        $param = array_shift($params);
        self::assertEquals('string', $param['type']);
        self::assertEquals('foo', $param['name']);

        $param = array_shift($params);
        self::assertEquals('boolean', $param['type']);

        $param = array_shift($params);
        self::assertEquals('integer', $param['type']);
    }

    public function testSetparamsShouldOverwriteExistingParams(): void
    {
        $this->testShouldBeAbleToAddMultipleParamsAtOnce();
        $params = $this->service->getParams();
        self::assertCount(3, $params);

        $this->service->setParams([
            ['type' => 'string'],
            ['type' => 'integer'],
        ]);
        $test = $this->service->getParams();
        self::assertNotEquals($params, $test);
        self::assertCount(2, $test);
    }

    public function testReturnShouldBeNullByDefault(): void
    {
        self::assertNull($this->service->getReturn());
    }

    public function testReturnAccessorsShouldWorkWithNormalInput(): void
    {
        $this->testReturnShouldBeNullByDefault();
        $this->service->setReturn('integer');
        self::assertEquals('integer', $this->service->getReturn());
    }

    public function testReturnAccessorsShouldAllowArrayOfTypes(): void
    {
        $this->testReturnShouldBeNullByDefault();
        $type = ['integer', 'string'];
        $this->service->setReturn($type);
        self::assertEquals($type, $this->service->getReturn());
    }

    public function testInvalidReturnTypeShouldThrowException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param type');
        $this->service->setReturn(new stdClass());
    }

    public function testToArrayShouldCreateSmdCompatibleHash(): void
    {
        $this->setupSmdValidationObject();
        $smd = $this->service->toArray();
        $this->validateSmdArray($smd);
    }

    public function testTojsonShouldEmitJSON(): void
    {
        $this->setupSmdValidationObject();
        $json = $this->service->toJSON();
        $smd  = Json::decode($json, Json::TYPE_ARRAY);

        self::assertArrayHasKey('foo', $smd);
        self::assertIsArray($smd['foo']);

        $this->validateSmdArray($smd['foo']);
    }

    public function setupSmdValidationObject(): void
    {
        $this->service->setName('foo')
                      ->setTransport('POST')
                      ->setTarget('/foo')
                      ->setEnvelope(Smd::ENV_JSONRPC_2)
                      ->addParam('boolean')
                      ->addParam('array')
                      ->addParam('object')
                      ->setReturn('boolean');
    }

    public function validateSmdArray(array $smd): void
    {
        self::assertArrayHasKey('transport', $smd);
        self::assertEquals('POST', $smd['transport']);

        self::assertArrayHasKey('envelope', $smd);
        self::assertEquals(Smd::ENV_JSONRPC_2, $smd['envelope']);

        self::assertArrayHasKey('parameters', $smd);
        $params = $smd['parameters'];
        self::assertCount(3, $params);
        $param = array_shift($params);
        self::assertEquals('boolean', $param['type']);
        $param = array_shift($params);
        self::assertEquals('array', $param['type']);
        $param = array_shift($params);
        self::assertEquals('object', $param['type']);

        self::assertArrayHasKey('returns', $smd);
        self::assertEquals('boolean', $smd['returns']);
    }
}
