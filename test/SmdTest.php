<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Exception\InvalidArgumentException;
use Laminas\Json\Server\Exception\RuntimeException;
use Laminas\Json\Server\Smd;
use Laminas\Json\Server\Smd\Service;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_shift;
use function array_values;
use function uniqid;

class SmdTest extends TestCase
{
    /**
     * @var Smd
     */
    protected $smd;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->smd = new Smd();
    }

    public function testTransportShouldDefaultToPost(): void
    {
        $this->assertSame('POST', $this->smd->getTransport());
    }

    public function testTransportAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->smd->setTransport('POST');
        $this->assertSame('POST', $this->smd->getTransport());
    }

    public function testTransportShouldBeLimitedToPost(): void
    {
        foreach (['GET', 'REST'] as $transport) {
            try {
                $this->smd->setTransport($transport);
                $this->fail('Invalid transport should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid transport', $e->getMessage());
            }
        }
    }

    public function testEnvelopeShouldDefaultToJSONRpcVersion1(): void
    {
        $this->assertSame(Smd::ENV_JSONRPC_1, $this->smd->getEnvelope());
    }

    public function testEnvelopeAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testEnvelopeShouldDefaultToJSONRpcVersion1();
        $this->smd->setEnvelope(Smd::ENV_JSONRPC_2);
        $this->assertSame(Smd::ENV_JSONRPC_2, $this->smd->getEnvelope());
        $this->smd->setEnvelope(Smd::ENV_JSONRPC_1);
        $this->assertSame(Smd::ENV_JSONRPC_1, $this->smd->getEnvelope());
    }

    public function testEnvelopeShouldBeLimitedToJSONRpcVersions(): void
    {
        foreach (['URL', 'PATH', 'JSON'] as $env) {
            try {
                $this->smd->setEnvelope($env);
                $this->fail('Invalid envelope type should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid envelope', $e->getMessage());
            }
        }
    }

    public function testContentTypeShouldDefaultToApplicationJSON(): void
    {
        $this->assertSame('application/json', $this->smd->getContentType());
    }

    public function testContentTypeAccessorsShouldWorkUnderNormalInput(): void
    {
        foreach (['text/json', 'text/plain', 'application/x-json'] as $type) {
            $this->smd->setContentType($type);
            $this->assertSame($type, $this->smd->getContentType());
        }
    }

    public function testContentTypeShouldBeLimitedToMimeFormatStrings(): void
    {
        foreach (['plain', 'json', 'foobar'] as $type) {
            try {
                $this->smd->setContentType($type);
                $this->fail('Invalid content type should raise exception');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid content type', $e->getMessage());
            }
        }
    }

    public function testTargetShouldDefaultToAnEmptyString(): void
    {
        $this->assertSame('', $this->smd->getTarget());
    }

    public function testTargetAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testTargetShouldDefaultToAnEmptyString();
        $this->smd->setTarget('foo');
        $this->assertSame('foo', $this->smd->getTarget());
    }

    public function testIdShouldDefaultToAnEmptyString(): void
    {
        $this->assertSame('', $this->smd->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testIdShouldDefaultToAnEmptyString();
        $this->smd->setId('foo');
        $this->assertSame('foo', $this->smd->getId());
    }

    public function testDescriptionShouldDefaultToAnEmptyString(): void
    {
        $this->assertSame('', $this->smd->getDescription());
    }

    public function testDescriptionAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testDescriptionShouldDefaultToAnEmptyString();
        $this->smd->setDescription('foo');
        $this->assertSame('foo', $this->smd->getDescription());
    }

    public function testDojoCompatibilityShouldBeDisabledByDefault(): void
    {
        $this->assertFalse($this->smd->isDojoCompatible());
    }

    public function testDojoCompatibilityFlagShouldBeMutable(): void
    {
        $this->testDojoCompatibilityShouldBeDisabledByDefault();
        $this->smd->setDojoCompatible(true);
        $this->assertTrue($this->smd->isDojoCompatible());
        $this->smd->setDojoCompatible(false);
        $this->assertFalse($this->smd->isDojoCompatible());
    }

    public function testServicesShouldBeEmptyByDefault(): void
    {
        $services = $this->smd->getServices();
        $this->assertIsArray($services);
        $this->assertEmpty($services);
    }

    public function testShouldBeAbleToUseServiceObjectToAddService(): void
    {
        $service = new Smd\Service('foo');
        $this->smd->addService($service);
        $this->assertSame($service, $this->smd->getService('foo'));
    }

    public function testShouldBeAbleToUseArrayToAddService(): void
    {
        $service = [
            'name' => 'foo',
        ];
        $this->smd->addService($service);
        $foo = $this->smd->getService('foo');
        $this->assertInstanceOf(Service::class, $foo);
        $this->assertSame('foo', $foo->getName());
    }

    public function testAddingServiceWithExistingServiceNameShouldThrowException(): void
    {
        $service = new Smd\Service('foo');
        $this->smd->addService($service);
        $test    = new Smd\Service('foo');
        try {
            $this->smd->addService($test);
            $this->fail('Adding service with existing service name should throw exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('already register', $e->getMessage());
        }
    }

    public function testAttemptingToRegisterInvalidServiceShouldThrowException(): void
    {
        foreach (['foo', false, 1, 1.0] as $service) {
            try {
                $this->smd->addService($service);
                $this->fail('Attempt to register invalid service should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid service', $e->getMessage());
            }
        }
    }

    public function testShouldBeAbleToAddManyServicesAtOnceWithArrayOfServiceObjects(): void
    {
        $one   = new Smd\Service('one');
        $two   = new Smd\Service('two');
        $three = new Smd\Service('three');
        $services = [$one, $two, $three];
        $this->smd->addServices($services);
        $test = $this->smd->getServices();
        $this->assertSame($services, array_values($test));
    }

    public function testShouldBeAbleToAddManyServicesAtOnceWithArrayOfArrays(): void
    {
        $services = [
            ['name' => 'one'],
            ['name' => 'two'],
            ['name' => 'three'],
        ];
        $this->smd->addServices($services);
        $test = $this->smd->getServices();
        $this->assertSame(['one', 'two', 'three'], array_keys($test));
    }

    public function testShouldBeAbleToAddManyServicesAtOnceWithMixedArrayOfObjectsAndArrays(): void
    {
        $two = new Smd\Service('two');
        $services = [
            ['name' => 'one'],
            $two,
            ['name' => 'three'],
        ];
        $this->smd->addServices($services);
        $test = $this->smd->getServices();
        $this->assertSame(['one', 'two', 'three'], array_keys($test));
        $this->assertSame($two, $test['two']);
    }

    public function testSetServicesShouldOverwriteExistingServices(): void
    {
        $this->testShouldBeAbleToAddManyServicesAtOnceWithMixedArrayOfObjectsAndArrays();
        $five = new Smd\Service('five');
        $services = [
            ['name' => 'four'],
            $five,
            ['name' => 'six'],
        ];
        $this->smd->setServices($services);
        $test = $this->smd->getServices();
        $this->assertSame(['four', 'five', 'six'], array_keys($test));
        $this->assertSame($five, $test['five']);
    }

    public function testShouldBeAbleToRetrieveServiceByName(): void
    {
        $this->testShouldBeAbleToUseServiceObjectToAddService();
    }

    public function testShouldBeAbleToRemoveServiceByName(): void
    {
        $this->testShouldBeAbleToUseServiceObjectToAddService();
        $this->assertTrue($this->smd->removeService('foo'));
        $this->assertFalse($this->smd->getService('foo'));
    }

    public function testShouldBeAbleToCastToArray(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $service = $this->smd->toArray();
        $this->validateServiceArray($service, $options);
    }

    public function testShouldBeAbleToCastToDojoArray(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $smd = $this->smd->toDojoArray();

        $this->assertIsArray($smd);

        $this->assertArrayHasKey('SMDVersion', $smd);
        $this->assertArrayHasKey('serviceType', $smd);
        $this->assertArrayHasKey('methods', $smd);

        $this->assertSame('.1', $smd['SMDVersion']);
        $this->assertSame('JSON-RPC', $smd['serviceType']);
        $methods = $smd['methods'];
        $this->assertCount(2, $methods);

        $foo = array_shift($methods);
        $this->assertArrayHasKey('name', $foo);
        $this->assertArrayHasKey('serviceURL', $foo);
        $this->assertArrayHasKey('parameters', $foo);
        $this->assertSame('foo', $foo['name']);
        $this->assertSame($this->smd->getTarget(), $foo['serviceURL']);
        $this->assertIsArray($foo['parameters']);
        $this->assertCount(1, $foo['parameters']);

        $bar = array_shift($methods);
        $this->assertArrayHasKey('name', $bar);
        $this->assertArrayHasKey('serviceURL', $bar);
        $this->assertArrayHasKey('parameters', $bar);
        $this->assertSame('bar', $bar['name']);
        $this->assertSame($this->smd->getTarget(), $bar['serviceURL']);
        $this->assertIsArray($bar['parameters']);
        $this->assertCount(1, $bar['parameters']);
    }

    public function testShouldBeAbleToRenderAsJSON(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $json = $this->smd->toJSON();
        $smd  = Json::decode($json, Json::TYPE_ARRAY);
        $this->validateServiceArray($smd, $options);
    }

    public function testToStringImplementationShouldProxyToJSON(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $json = $this->smd->__toString();
        $smd  = Json::decode($json, Json::TYPE_ARRAY);
        $this->validateServiceArray($smd, $options);
    }

    public function getOptions(): array
    {
        return [
            'target'   => '/test/me',
            'id'       => '/test/me',
            'services' => [
                [
                    'name'   => 'foo',
                    'params' => [
                        ['type' => 'boolean'],
                    ],
                    'return' => 'boolean',
                ],
                [
                    'name'   => 'bar',
                    'params' => [
                        ['type' => 'integer'],
                    ],
                    'return' => 'string',
                ],
            ],
        ];
    }

    public function validateServiceArray(array $smd, array $options): void
    {
        $this->assertIsArray($smd);

        $this->assertArrayHasKey('SMDVersion', $smd);
        $this->assertArrayHasKey('target', $smd);
        $this->assertArrayHasKey('id', $smd);
        $this->assertArrayHasKey('transport', $smd);
        $this->assertArrayHasKey('envelope', $smd);
        $this->assertArrayHasKey('contentType', $smd);
        $this->assertArrayHasKey('services', $smd);

        $this->assertSame(Smd::SMD_VERSION, $smd['SMDVersion']);
        $this->assertSame($options['target'], $smd['target']);
        $this->assertSame($options['id'], $smd['id']);
        $this->assertSame($this->smd->getTransport(), $smd['transport']);
        $this->assertSame($this->smd->getEnvelope(), $smd['envelope']);
        $this->assertSame($this->smd->getContentType(), $smd['contentType']);
        $services = $smd['services'];
        $this->assertCount(2, $services);
        $this->assertArrayHasKey('foo', $services);
        $this->assertArrayHasKey('bar', $services);
    }

    /**
     * @group Laminas-5624
     */
    public function testSetOptionsShouldAccommodateToArrayOutput(): void
    {
        $smdSource = new Smd();
        $smdSource->setContentType('application/json');
        $smdSource->setDescription('description');
        $smdSource->setEnvelope(Smd::ENV_JSONRPC_1);
        $smdSource->setId(uniqid());
        $smdSource->setTarget('http://foo');
        $smdSource->setTransport('POST');
        $smdSource->setServices([
            ['name' => 'foo'],
        ]);

        $smdDestination = new Smd();
        // prior to fix the following resulted in:
        // .. Laminas\Json\Server\Exception\InvalidArgumentException
        // ... : SMD service description requires a name; none provided
        $smdDestination->setOptions($smdSource->toArray());

        $this->assertSame(
            $smdSource->getContentType(),
            $smdDestination->getContentType()
        );
        $this->assertSame(
            $smdSource->getDescription(),
            $smdDestination->getDescription()
        );
        $this->assertSame(
            $smdSource->getEnvelope(),
            $smdDestination->getEnvelope()
        );
        $this->assertSame(
            $smdSource->getId(),
            $smdDestination->getId()
        );
        $this->assertSame(
            $smdSource->getTarget(),
            $smdDestination->getTarget()
        );
        $this->assertSame(
            $smdSource->getTransport(),
            $smdDestination->getTransport()
        );
        $this->assertEquals(
            $smdSource->getServices(),
            $smdDestination->getServices()
        );
    }
}
