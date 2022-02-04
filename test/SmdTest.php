<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server;

use Laminas\Json;
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
    /** @var Smd */
    protected $smd;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->smd = new Smd();
    }

    public function testTransportShouldDefaultToPost(): void
    {
        self::assertEquals('POST', $this->smd->getTransport());
    }

    public function testTransportAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->smd->setTransport('POST');
        self::assertEquals('POST', $this->smd->getTransport());
    }

    public function testTransportShouldBeLimitedToPost(): void
    {
        foreach (['GET', 'REST'] as $transport) {
            try {
                $this->smd->setTransport($transport);
                $this->fail('Invalid transport should throw exception');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid transport', $e->getMessage());
            }
        }
    }

    public function testEnvelopeShouldDefaultToJSONRpcVersion1(): void
    {
        self::assertEquals(Smd::ENV_JSONRPC_1, $this->smd->getEnvelope());
    }

    public function testEnvelopeAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testEnvelopeShouldDefaultToJSONRpcVersion1();
        $this->smd->setEnvelope(Smd::ENV_JSONRPC_2);
        self::assertEquals(Smd::ENV_JSONRPC_2, $this->smd->getEnvelope());
        $this->smd->setEnvelope(Smd::ENV_JSONRPC_1);
        self::assertEquals(Smd::ENV_JSONRPC_1, $this->smd->getEnvelope());
    }

    public function testEnvelopeShouldBeLimitedToJSONRpcVersions(): void
    {
        foreach (['URL', 'PATH', 'JSON'] as $env) {
            try {
                $this->smd->setEnvelope($env);
                $this->fail('Invalid envelope type should throw exception');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid envelope', $e->getMessage());
            }
        }
    }

    public function testContentTypeShouldDefaultToApplicationJSON(): void
    {
        self::assertEquals('application/json', $this->smd->getContentType());
    }

    public function testContentTypeAccessorsShouldWorkUnderNormalInput(): void
    {
        foreach (['text/json', 'text/plain', 'application/x-json'] as $type) {
            $this->smd->setContentType($type);
            self::assertEquals($type, $this->smd->getContentType());
        }
    }

    public function testContentTypeShouldBeLimitedToMimeFormatStrings(): void
    {
        foreach (['plain', 'json', 'foobar'] as $type) {
            try {
                $this->smd->setContentType($type);
                $this->fail('Invalid content type should raise exception');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid content type', $e->getMessage());
            }
        }
    }

    public function testTargetShouldDefaultToNull(): void
    {
        self::assertNull($this->smd->getTarget());
    }

    public function testTargetAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testTargetShouldDefaultToNull();
        $this->smd->setTarget('foo');
        self::assertEquals('foo', $this->smd->getTarget());
    }

    public function testIdShouldDefaultToNull(): void
    {
        self::assertNull($this->smd->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testIdShouldDefaultToNull();
        $this->smd->setId('foo');
        self::assertEquals('foo', $this->smd->getId());
    }

    public function testDescriptionShouldDefaultToNull(): void
    {
        self::assertNull($this->smd->getDescription());
    }

    public function testDescriptionAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->testDescriptionShouldDefaultToNull();
        $this->smd->setDescription('foo');
        self::assertEquals('foo', $this->smd->getDescription());
    }

    public function testDojoCompatibilityShouldBeDisabledByDefault(): void
    {
        self::assertFalse($this->smd->isDojoCompatible());
    }

    public function testDojoCompatibilityFlagShouldBeMutable(): void
    {
        $this->testDojoCompatibilityShouldBeDisabledByDefault();
        $this->smd->setDojoCompatible(true);
        self::assertTrue($this->smd->isDojoCompatible());
        $this->smd->setDojoCompatible(false);
        self::assertFalse($this->smd->isDojoCompatible());
    }

    public function testServicesShouldBeEmptyByDefault(): void
    {
        $services = $this->smd->getServices();
        self::assertIsArray($services);
        self::assertEmpty($services);
    }

    public function testShouldBeAbleToUseServiceObjectToAddService(): void
    {
        $service = new Smd\Service('foo');
        $this->smd->addService($service);
        self::assertSame($service, $this->smd->getService('foo'));
    }

    public function testShouldBeAbleToUseArrayToAddService(): void
    {
        $service = [
            'name' => 'foo',
        ];
        $this->smd->addService($service);
        $foo = $this->smd->getService('foo');
        self::assertInstanceOf(Service::class, $foo);
        self::assertEquals('foo', $foo->getName());
    }

    public function testAddingServiceWithExistingServiceNameShouldThrowException(): void
    {
        $service = new Smd\Service('foo');
        $this->smd->addService($service);
        $test = new Smd\Service('foo');
        try {
            $this->smd->addService($test);
            $this->fail('Adding service with existing service name should throw exception');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('already register', $e->getMessage());
        }
    }

    public function testAttemptingToRegisterInvalidServiceShouldThrowException(): void
    {
        foreach (['foo', false, 1, 1.0] as $service) {
            try {
                $this->smd->addService($service);
                $this->fail('Attempt to register invalid service should throw exception');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid service', $e->getMessage());
            }
        }
    }

    public function testShouldBeAbleToAddManyServicesAtOnceWithArrayOfServiceObjects(): void
    {
        $one      = new Smd\Service('one');
        $two      = new Smd\Service('two');
        $three    = new Smd\Service('three');
        $services = [$one, $two, $three];
        $this->smd->addServices($services);
        $test = $this->smd->getServices();
        self::assertSame($services, array_values($test));
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
        self::assertSame(['one', 'two', 'three'], array_keys($test));
    }

    public function testShouldBeAbleToAddManyServicesAtOnceWithMixedArrayOfObjectsAndArrays(): void
    {
        $two      = new Smd\Service('two');
        $services = [
            ['name' => 'one'],
            $two,
            ['name' => 'three'],
        ];
        $this->smd->addServices($services);
        $test = $this->smd->getServices();
        self::assertSame(['one', 'two', 'three'], array_keys($test));
        self::assertEquals($two, $test['two']);
    }

    public function testSetServicesShouldOverwriteExistingServices(): void
    {
        $this->testShouldBeAbleToAddManyServicesAtOnceWithMixedArrayOfObjectsAndArrays();
        $five     = new Smd\Service('five');
        $services = [
            ['name' => 'four'],
            $five,
            ['name' => 'six'],
        ];
        $this->smd->setServices($services);
        $test = $this->smd->getServices();
        self::assertSame(['four', 'five', 'six'], array_keys($test));
        self::assertEquals($five, $test['five']);
    }

    public function testShouldBeAbleToRetrieveServiceByName(): void
    {
        $this->testShouldBeAbleToUseServiceObjectToAddService();
    }

    public function testShouldBeAbleToRemoveServiceByName(): void
    {
        $this->testShouldBeAbleToUseServiceObjectToAddService();
        self::assertTrue($this->smd->removeService('foo'));
        self::assertFalse($this->smd->getService('foo'));
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

        self::assertIsArray($smd);

        self::assertArrayHasKey('SMDVersion', $smd);
        self::assertArrayHasKey('serviceType', $smd);
        self::assertArrayHasKey('methods', $smd);

        self::assertEquals('.1', $smd['SMDVersion']);
        self::assertEquals('JSON-RPC', $smd['serviceType']);
        $methods = $smd['methods'];
        self::assertCount(2, $methods);

        $foo = array_shift($methods);
        self::assertArrayHasKey('name', $foo);
        self::assertArrayHasKey('serviceURL', $foo);
        self::assertArrayHasKey('parameters', $foo);
        self::assertEquals('foo', $foo['name']);
        self::assertEquals($this->smd->getTarget(), $foo['serviceURL']);
        self::assertIsArray($foo['parameters']);
        self::assertCount(1, $foo['parameters']);

        $bar = array_shift($methods);
        self::assertArrayHasKey('name', $bar);
        self::assertArrayHasKey('serviceURL', $bar);
        self::assertArrayHasKey('parameters', $bar);
        self::assertEquals('bar', $bar['name']);
        self::assertEquals($this->smd->getTarget(), $bar['serviceURL']);
        self::assertIsArray($bar['parameters']);
        self::assertCount(1, $bar['parameters']);
    }

    public function testShouldBeAbleToRenderAsJSON(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $json = $this->smd->toJSON();
        $smd  = Json\Json::decode($json, Json\Json::TYPE_ARRAY);
        $this->validateServiceArray($smd, $options);
    }

    public function testToStringImplementationShouldProxyToJSON(): void
    {
        $options = $this->getOptions();
        $this->smd->setOptions($options);
        $json = $this->smd->__toString();
        $smd  = Json\Json::decode($json, Json\Json::TYPE_ARRAY);
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
        self::assertIsArray($smd);

        self::assertArrayHasKey('SMDVersion', $smd);
        self::assertArrayHasKey('target', $smd);
        self::assertArrayHasKey('id', $smd);
        self::assertArrayHasKey('transport', $smd);
        self::assertArrayHasKey('envelope', $smd);
        self::assertArrayHasKey('contentType', $smd);
        self::assertArrayHasKey('services', $smd);

        self::assertEquals(Smd::SMD_VERSION, $smd['SMDVersion']);
        self::assertEquals($options['target'], $smd['target']);
        self::assertEquals($options['id'], $smd['id']);
        self::assertEquals($this->smd->getTransport(), $smd['transport']);
        self::assertEquals($this->smd->getEnvelope(), $smd['envelope']);
        self::assertEquals($this->smd->getContentType(), $smd['contentType']);
        $services = $smd['services'];
        self::assertCount(2, $services);
        self::assertArrayHasKey('foo', $services);
        self::assertArrayHasKey('bar', $services);
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

        self::assertEquals(
            $smdSource->getContentType(),
            $smdDestination->getContentType()
        );
        self::assertEquals(
            $smdSource->getDescription(),
            $smdDestination->getDescription()
        );
        self::assertEquals(
            $smdSource->getEnvelope(),
            $smdDestination->getEnvelope()
        );
        self::assertEquals(
            $smdSource->getId(),
            $smdDestination->getId()
        );
        self::assertEquals(
            $smdSource->getTarget(),
            $smdDestination->getTarget()
        );
        self::assertEquals(
            $smdSource->getTransport(),
            $smdDestination->getTransport()
        );
        self::assertEquals(
            $smdSource->getServices(),
            $smdDestination->getServices()
        );
    }
}
