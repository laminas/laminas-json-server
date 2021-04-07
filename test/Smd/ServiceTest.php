<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

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
    /**
     * @var Service
     */
    protected $service;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
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
        $this->assertSame('_getMyProperty', $this->service->getName());
    }

    public function testSettingNameShouldThrowExceptionWhenContainingInvalidFormatStartingWithRpc(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid name');
        $this->service->setName('rpc.Foo');
    }

    // @codingStandardsIgnoreStart
    public function testSettingNameShouldNotThrowExceptionWhenContainingInvalidFormatStartingWithRpcWithoutPeriodChar(): void
    {
        $this->service->setName('rpcFoo');
        $this->assertSame('rpcFoo', $this->service->getName());
    }
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    public function testSettingNameShouldNotThrowExceptionWhenContainingInvalidFormatStartingWithRpcInsensitiveCase(): void
    {
        $this->service->setName('RpcFoo');
        $this->assertSame('RpcFoo', $this->service->getName());
    }
    // @codingStandardsIgnoreEnd

    public function testSettingNameShouldNotThrowExceptionWhenContainingValidFormatContainingRpc(): void
    {
        $this->service->setName('_rpcFoo');
        $this->assertSame('_rpcFoo', $this->service->getName());

        $this->service->setName('MyRpcFoo');
        $this->assertSame('MyRpcFoo', $this->service->getName());
    }

    public function testNameAccessorsShouldWorkWithNormalInput(): void
    {
        $this->assertSame('foo', $this->service->getName());
        $this->service->setName('bar');
        $this->assertSame('bar', $this->service->getName());
    }

    public function testTransportShouldDefaultToPost(): void
    {
        $this->assertSame('POST', $this->service->getTransport());
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
        $this->assertSame('POST', $this->service->getTransport());
    }

    public function testTargetShouldBeNullInitially(): void
    {
        $this->assertNull($this->service->getTarget());
    }

    public function testTargetAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testTargetShouldBeNullInitially();
        $this->service->setTarget('foo');
        $this->assertSame('foo', $this->service->getTarget());
    }

    public function testEnvelopeShouldBeJSONRpc1CompliantByDefault(): void
    {
        $this->assertSame(Smd::ENV_JSONRPC_1, $this->service->getEnvelope());
    }

    public function testEnvelopeShouldOnlyComplyWithJSONRpc1And2(): void
    {
        $this->testEnvelopeShouldBeJSONRpc1CompliantByDefault();
        $this->service->setEnvelope(Smd::ENV_JSONRPC_2);
        $this->assertSame(Smd::ENV_JSONRPC_2, $this->service->getEnvelope());
        $this->service->setEnvelope(Smd::ENV_JSONRPC_1);
        $this->assertSame(Smd::ENV_JSONRPC_1, $this->service->getEnvelope());
        try {
            $this->service->setEnvelope('JSON-P');
            $this->fail('Should not be able to set non-JSON-RPC spec envelopes');
        } catch (Exception\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid envelope', $e->getMessage());
        }
    }

    public function testShouldHaveNoParamsByDefault(): void
    {
        $params = $this->service->getParams();
        $this->assertEmpty($params);
    }

    public function testShouldBeAbleToAddParamsByTypeOnly(): void
    {
        $this->service->addParam('integer');
        $params = $this->service->getParams();
        $this->assertCount(1, $params);
        $param = array_shift($params);
        $this->assertSame('integer', $param['type']);
    }

    public function testParamsShouldAcceptArrayOfTypes(): void
    {
        $type   = ['integer', 'string'];
        $this->service->addParam($type);
        $params = $this->service->getParams();
        $param  = array_shift($params);
        $test   = $param['type'];
        $this->assertIsArray($test);
        $this->assertSame($type, $test);
    }

    public function testInvalidParamTypeShouldThrowException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param type');
        $this->service->addParam(new stdClass);
    }

    public function testShouldBeAbleToOrderParams(): void
    {
        $this->service->addParam('integer', [], 4)
                      ->addParam('string')
                      ->addParam('boolean', [], 3);
        $params = $this->service->getParams();

        $this->assertCount(3, $params);

        $param = array_shift($params);
        $this->assertSame('string', $param['type'], var_export($params, true));
        $param = array_shift($params);
        $this->assertSame('boolean', $param['type'], var_export($params, true));
        $param = array_shift($params);
        $this->assertSame('integer', $param['type'], var_export($params, true));
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
        $this->assertSame('foo', $param['name']);
        $this->assertFalse($param['optional']);
        $this->assertSame(1, $param['default']);
        $this->assertSame('Foo parameter', $param['description']);
    }

    public function testShouldBeAbleToAddMultipleParamsAtOnce(): void
    {
        $this->service->addParams([
            ['type' => 'integer', 'order' => 4],
            ['type' => 'string', 'name' => 'foo'],
            ['type' => 'boolean', 'order' => 3],
        ]);
        $params = $this->service->getParams();

        $this->assertCount(3, $params);
        $param = array_shift($params);
        $this->assertSame('string', $param['type']);
        $this->assertSame('foo', $param['name']);

        $param = array_shift($params);
        $this->assertSame('boolean', $param['type']);

        $param = array_shift($params);
        $this->assertSame('integer', $param['type']);
    }

    public function testSetparamsShouldOverwriteExistingParams(): void
    {
        $this->testShouldBeAbleToAddMultipleParamsAtOnce();
        $params = $this->service->getParams();
        $this->assertCount(3, $params);

        $this->service->setParams([
            ['type' => 'string'],
            ['type' => 'integer'],
        ]);
        $test = $this->service->getParams();
        $this->assertNotEquals($params, $test);
        $this->assertCount(2, $test);
    }

    public function testReturnShouldBeNullByDefault(): void
    {
        $this->assertNull($this->service->getReturn());
    }

    public function testReturnAccessorsShouldWorkWithNormalInput(): void
    {
        $this->testReturnShouldBeNullByDefault();
        $this->service->setReturn('integer');
        $this->assertSame('integer', $this->service->getReturn());
    }

    public function testReturnAccessorsShouldAllowArrayOfTypes(): void
    {
        $this->testReturnShouldBeNullByDefault();
        $type = ['integer', 'string'];
        $this->service->setReturn($type);
        $this->assertSame($type, $this->service->getReturn());
    }

    public function testInvalidReturnTypeShouldThrowException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param type');
        $this->service->setReturn(new stdClass);
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

        $this->assertArrayHasKey('foo', $smd);
        $this->assertIsArray($smd['foo']);

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
        $this->assertArrayHasKey('transport', $smd);
        $this->assertSame('POST', $smd['transport']);

        $this->assertArrayHasKey('envelope', $smd);
        $this->assertSame(Smd::ENV_JSONRPC_2, $smd['envelope']);

        $this->assertArrayHasKey('parameters', $smd);
        $params = $smd['parameters'];
        $this->assertCount(3, $params);
        $param = array_shift($params);
        $this->assertSame('boolean', $param['type']);
        $param = array_shift($params);
        $this->assertSame('array', $param['type']);
        $param = array_shift($params);
        $this->assertSame('object', $param['type']);

        $this->assertArrayHasKey('returns', $smd);
        $this->assertSame('boolean', $smd['returns']);
    }
}
