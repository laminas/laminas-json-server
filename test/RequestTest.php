<?php

namespace LaminasTest\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testShouldHaveNoParamsByDefault(): void
    {
        $params = $this->request->getParams();
        $this->assertEmpty($params);
    }

    public function testShouldBeAbleToAddAParamAsValueOnly(): void
    {
        $this->request->addParam('foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $test = array_shift($params);
        $this->assertEquals('foo', $test);
    }

    public function testShouldBeAbleToAddAParamAsKeyValuePair(): void
    {
        $this->request->addParam('bar', 'foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $this->assertArrayHasKey('foo', $params);
        $this->assertEquals('bar', $params['foo']);
    }

    public function testInvalidKeysShouldBeIgnored(): void
    {
        $count = 0;
        foreach ([['foo', true], ['foo', new \stdClass], ['foo', []]] as $spec) {
            $this->request->addParam($spec[0], $spec[1]);
            $this->assertNull($this->request->getParam('foo'));
            $params = $this->request->getParams();
            ++$count;
            $this->assertCount($count, $params);
        }
    }

    public function testShouldBeAbleToAddMultipleIndexedParamsAtOnce(): void
    {
        $params = [
            'foo',
            'bar',
            'baz',
        ];
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMultipleNamedParamsAtOnce(): void
    {
        $params = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce(): void
    {
        $params = [
            'foo' => 'bar',
            'baz',
            'baz' => 'bat',
        ];
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertEquals(array_values($params), array_values($test));
        $this->assertArrayHasKey('foo', $test);
        $this->assertArrayHasKey('baz', $test);
        $this->assertContains('baz', $test);
    }

    public function testSetParamsShouldOverwriteParams(): void
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = [
            'one',
            'two',
            'three',
        ];
        $this->request->setParams($params);
        $this->assertSame($params, $this->request->getParams());
    }

    public function testShouldBeAbleToRetrieveParamByKeyOrIndex(): void
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = $this->request->getParams();
        $this->assertEquals('bar', $this->request->getParam('foo'), var_export($params, 1));
        $this->assertEquals('baz', $this->request->getParam(1), var_export($params, 1));
        $this->assertEquals('bat', $this->request->getParam('baz'), var_export($params, 1));
    }

    public function testMethodShouldBeNullByDefault(): void
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodErrorShouldBeFalseByDefault(): void
    {
        $this->assertFalse($this->request->isMethodError());
    }

    public function testMethodAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setMethod('foo');
        $this->assertEquals('foo', $this->request->getMethod());
    }

    public function testSettingMethodWithInvalidNameShouldSetError(): void
    {
        foreach (['1ad', 'abc-123', 'ad$$832r#@'] as $method) {
            $this->request->setMethod($method);
            $this->assertNull($this->request->getMethod());
            $this->assertTrue($this->request->isMethodError());
        }
    }

    public function testIdShouldBeNullByDefault(): void
    {
        $this->assertNull($this->request->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setId('foo');
        $this->assertEquals('foo', $this->request->getId());
    }

    public function testVersionShouldBeJSONRpcV1ByDefault(): void
    {
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testVersionShouldBeLimitedToV1AndV2(): void
    {
        $this->testVersionShouldBeJSONRpcV1ByDefault();
        $this->request->setVersion('2.0');
        $this->assertEquals('2.0', $this->request->getVersion());
        $this->request->setVersion('foo');
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToLoadRequestFromJSONString(): void
    {
        $options = $this->getOptions();
        $json    = Json::encode($options);
        $this->request->loadJSON($json);

        $this->assertEquals('foo', $this->request->getMethod());
        $this->assertEquals('foobar', $this->request->getId());
        $this->assertEquals($options['params'], $this->request->getParams());
    }

    public function testLoadingFromJSONShouldSetJSONRpcVersionWhenPresent(): void
    {
        $options = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json    = Json::encode($options);
        $this->request->loadJSON($json);
        $this->assertEquals('2.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToCastToJSON(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->toJSON();
        $this->validateJSON($json, $options);
    }

    public function testCastingToStringShouldCastToJSON(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->__toString();
        $this->validateJSON($json, $options);
    }

    /**
     * @group Laminas-6187
     */
    public function testMethodNamesShouldAllowDotNamespacing(): void
    {
        $this->request->setMethod('foo.bar');
        $this->assertEquals('foo.bar', $this->request->getMethod());
    }

    public function testIsParseErrorSetOnMalformedJson(): void
    {
        $testJson = '{"id":1, "method": "test", "params:"[1,2,3]}';
        $this->request->loadJson($testJson);
        $this->assertTrue($this->request->isParseError());
    }

    public function getOptions(): array
    {
        return [
            'method' => 'foo',
            'params' => [
                5,
                'four',
                true,
            ],
            'id'     => 'foobar'
        ];
    }

    public function validateJSON($json, array $options): void
    {
        $test = Json::decode($json, Json::TYPE_ARRAY);
        $this->assertIsArray($test, var_export($json, 1));

        $this->assertArrayHasKey('id', $test);
        $this->assertArrayHasKey('method', $test);
        $this->assertArrayHasKey('params', $test);

        $this->assertIsString($test['id']);
        $this->assertIsString($test['method']);
        $this->assertIsArray($test['params']);

        $this->assertEquals($options['id'], $test['id']);
        $this->assertEquals($options['method'], $test['method']);
        $this->assertSame($options['params'], $test['params']);
    }
}
