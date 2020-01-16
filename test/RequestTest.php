<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp() : void
    {
        $this->request = new Request();
    }

    public function testShouldHaveNoParamsByDefault()
    {
        $params = $this->request->getParams();
        $this->assertEmpty($params);
    }

    public function testShouldBeAbleToAddAParamAsValueOnly()
    {
        $this->request->addParam('foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $test = array_shift($params);
        $this->assertEquals('foo', $test);
    }

    public function testShouldBeAbleToAddAParamAsKeyValuePair()
    {
        $this->request->addParam('bar', 'foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $this->assertArrayHasKey('foo', $params);
        $this->assertEquals('bar', $params['foo']);
    }

    public function testInvalidKeysShouldBeIgnored()
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

    public function testShouldBeAbleToAddMultipleIndexedParamsAtOnce()
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

    public function testShouldBeAbleToAddMultipleNamedParamsAtOnce()
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

    public function testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce()
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

    public function testSetParamsShouldOverwriteParams()
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

    public function testShouldBeAbleToRetrieveParamByKeyOrIndex()
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = $this->request->getParams();
        $this->assertEquals('bar', $this->request->getParam('foo'), var_export($params, 1));
        $this->assertEquals('baz', $this->request->getParam(1), var_export($params, 1));
        $this->assertEquals('bat', $this->request->getParam('baz'), var_export($params, 1));
    }

    public function testMethodShouldBeNullByDefault()
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodErrorShouldBeFalseByDefault()
    {
        $this->assertFalse($this->request->isMethodError());
    }

    public function testMethodAccessorsShouldWorkUnderNormalInput()
    {
        $this->request->setMethod('foo');
        $this->assertEquals('foo', $this->request->getMethod());
    }

    public function testSettingMethodWithInvalidNameShouldSetError()
    {
        foreach (['1ad', 'abc-123', 'ad$$832r#@'] as $method) {
            $this->request->setMethod($method);
            $this->assertNull($this->request->getMethod());
            $this->assertTrue($this->request->isMethodError());
        }
    }

    public function testIdShouldBeNullByDefault()
    {
        $this->assertNull($this->request->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput()
    {
        $this->request->setId('foo');
        $this->assertEquals('foo', $this->request->getId());
    }

    public function testVersionShouldBeJSONRpcV1ByDefault()
    {
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testVersionShouldBeLimitedToV1AndV2()
    {
        $this->testVersionShouldBeJSONRpcV1ByDefault();
        $this->request->setVersion('2.0');
        $this->assertEquals('2.0', $this->request->getVersion());
        $this->request->setVersion('foo');
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToLoadRequestFromJSONString()
    {
        $options = $this->getOptions();
        $json    = Json::encode($options);
        $this->request->loadJSON($json);

        $this->assertEquals('foo', $this->request->getMethod());
        $this->assertEquals('foobar', $this->request->getId());
        $this->assertEquals($options['params'], $this->request->getParams());
    }

    public function testLoadingFromJSONShouldSetJSONRpcVersionWhenPresent()
    {
        $options = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json    = Json::encode($options);
        $this->request->loadJSON($json);
        $this->assertEquals('2.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToCastToJSON()
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->toJSON();
        $this->validateJSON($json, $options);
    }

    public function testCastingToStringShouldCastToJSON()
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->__toString();
        $this->validateJSON($json, $options);
    }

    /**
     * @group Laminas-6187
     */
    public function testMethodNamesShouldAllowDotNamespacing()
    {
        $this->request->setMethod('foo.bar');
        $this->assertEquals('foo.bar', $this->request->getMethod());
    }

    public function testIsParseErrorSetOnMalformedJson()
    {
        $testJson = '{"id":1, "method": "test", "params:"[1,2,3]}';
        $this->request->loadJson($testJson);
        $this->assertTrue($this->request->isParseError());
    }

    public function getOptions()
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

    public function validateJSON($json, array $options)
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
