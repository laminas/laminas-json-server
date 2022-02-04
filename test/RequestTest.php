<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Request;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_shift;
use function array_values;
use function var_export;

class RequestTest extends TestCase
{
    /** @var Request */
    protected $request;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testShouldHaveNoParamsByDefault(): void
    {
        $params = $this->request->getParams();
        self::assertEmpty($params);
    }

    public function testShouldBeAbleToAddAParamAsValueOnly(): void
    {
        $this->request->addParam('foo');
        $params = $this->request->getParams();
        self::assertCount(1, $params);
        $test = array_shift($params);
        self::assertEquals('foo', $test);
    }

    public function testShouldBeAbleToAddAParamAsKeyValuePair(): void
    {
        $this->request->addParam('bar', 'foo');
        $params = $this->request->getParams();
        self::assertCount(1, $params);
        self::assertArrayHasKey('foo', $params);
        self::assertEquals('bar', $params['foo']);
    }

    public function testInvalidKeysShouldBeIgnored(): void
    {
        $count = 0;
        foreach ([['foo', true], ['foo', new stdClass()], ['foo', []]] as $spec) {
            $this->request->addParam($spec[0], $spec[1]);
            self::assertNull($this->request->getParam('foo'));
            $params = $this->request->getParams();
            ++$count;
            self::assertCount($count, $params);
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
        self::assertSame($params, $test);
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
        self::assertSame($params, $test);
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
        self::assertEquals(array_values($params), array_values($test));
        self::assertArrayHasKey('foo', $test);
        self::assertArrayHasKey('baz', $test);
        self::assertContains('baz', $test);
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
        self::assertSame($params, $this->request->getParams());
    }

    public function testShouldBeAbleToRetrieveParamByKeyOrIndex(): void
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = $this->request->getParams();
        self::assertEquals('bar', $this->request->getParam('foo'), var_export($params, true));
        self::assertEquals('baz', $this->request->getParam(1), var_export($params, true));
        self::assertEquals('bat', $this->request->getParam('baz'), var_export($params, true));
    }

    public function testMethodShouldBeNullByDefault(): void
    {
        self::assertNull($this->request->getMethod());
    }

    public function testMethodErrorShouldBeFalseByDefault(): void
    {
        self::assertFalse($this->request->isMethodError());
    }

    public function testMethodAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setMethod('foo');
        self::assertEquals('foo', $this->request->getMethod());
    }

    public function testSettingMethodWithInvalidNameShouldSetError(): void
    {
        foreach (['1ad', 'abc-123', 'ad$$832r#@'] as $method) {
            $this->request->setMethod($method);
            self::assertNull($this->request->getMethod());
            self::assertTrue($this->request->isMethodError());
        }
    }

    public function testIdShouldBeNullByDefault(): void
    {
        self::assertNull($this->request->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setId('foo');
        self::assertEquals('foo', $this->request->getId());
    }

    public function testVersionShouldBeJSONRpcV1ByDefault(): void
    {
        self::assertEquals('1.0', $this->request->getVersion());
    }

    public function testVersionShouldBeLimitedToV1AndV2(): void
    {
        $this->testVersionShouldBeJSONRpcV1ByDefault();
        $this->request->setVersion('2.0');
        self::assertEquals('2.0', $this->request->getVersion());
        $this->request->setVersion('foo');
        self::assertEquals('1.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToLoadRequestFromJSONString(): void
    {
        $options = $this->getOptions();
        $json    = Json::encode($options);
        $this->request->loadJSON($json);

        self::assertEquals('foo', $this->request->getMethod());
        self::assertEquals('foobar', $this->request->getId());
        self::assertEquals($options['params'], $this->request->getParams());
    }

    public function testLoadingFromJSONShouldSetJSONRpcVersionWhenPresent(): void
    {
        $options            = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json               = Json::encode($options);
        $this->request->loadJSON($json);
        self::assertEquals('2.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToCastToJSON(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json = $this->request->toJSON();
        $this->validateJSON($json, $options);
    }

    public function testCastingToStringShouldCastToJSON(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json = $this->request->__toString();
        $this->validateJSON($json, $options);
    }

    /**
     * @group Laminas-6187
     */
    public function testMethodNamesShouldAllowDotNamespacing(): void
    {
        $this->request->setMethod('foo.bar');
        self::assertEquals('foo.bar', $this->request->getMethod());
    }

    public function testIsParseErrorSetOnMalformedJson(): void
    {
        $testJson = '{"id":1, "method": "test", "params:"[1,2,3]}';
        $this->request->loadJson($testJson);
        self::assertTrue($this->request->isParseError());
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
            'id'     => 'foobar',
        ];
    }

    public function validateJSON(string $json, array $options): void
    {
        $test = Json::decode($json, Json::TYPE_ARRAY);
        self::assertIsArray($test, var_export($json, true));

        self::assertArrayHasKey('id', $test);
        self::assertArrayHasKey('method', $test);
        self::assertArrayHasKey('params', $test);

        self::assertIsString($test['id']);
        self::assertIsString($test['method']);
        self::assertIsArray($test['params']);

        self::assertEquals($options['id'], $test['id']);
        self::assertEquals($options['method'], $test['method']);
        self::assertSame($options['params'], $test['params']);
    }
}
