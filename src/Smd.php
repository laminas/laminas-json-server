<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Json\Server;

use Laminas\Json\Json;
use Laminas\Json\Server\Exception\InvalidArgumentException;
use Laminas\Json\Server\Exception\RuntimeException;

use function array_key_exists;
use function compact;
use function in_array;
use function is_array;
use function method_exists;
use function preg_match;
use function ucfirst;

class Smd
{
    const ENV_JSONRPC_1 = 'JSON-RPC-1.0';
    const ENV_JSONRPC_2 = 'JSON-RPC-2.0';
    const SMD_VERSION   = '2.0';

    /**
     * Content type.
     *
     * @var string
     */
    protected $contentType = 'application/json';

    /**
     * Content type regex.
     *
     * @var string
     */
    protected $contentTypeRegex = '#[a-z]+/[a-z][a-z-]+#i';

    /**
     * Service description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Generate Dojo-compatible SMD?
     *
     * @var bool
     */
    protected $dojoCompatible = false;

    /**
     * Current envelope.
     *
     * @var string
     */
    protected $envelope = self::ENV_JSONRPC_1;

    /**
     * Allowed envelope types.
     *
     * @var array
     */
    protected $envelopeTypes = [
        self::ENV_JSONRPC_1,
        self::ENV_JSONRPC_2,
    ];

    /**
     * Service id.
     *
     * @var string
     */
    protected $id = '';

    /**
     * Services offered.
     *
     * @var array
     */
    protected $services = [];

    /**
     * Service target.
     *
     * @var string
     */
    protected $target = '';

    /**
     * Global transport.
     *
     * @var string
     */
    protected $transport = 'POST';

    /**
     * Allowed transport types.
     *
     * @var array
     */
    protected $transportTypes = ['POST'];

    /**
     * Set object state via options.
     *
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options) : self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * Set transport.
     *
     * @param  string $transport
     * @return self
      * @throws InvalidArgumentException
     */
    public function setTransport(string $transport) : self
    {
        if (! in_array($transport, $this->transportTypes)) {
            throw new InvalidArgumentException("Invalid transport '{$transport}' specified");
        }

        $this->transport = $transport;
        return $this;
    }

    /**
     * Get transport.
     *
     * @return string
     */
    public function getTransport() : string
    {
        return $this->transport;
    }

    /**
     * Set envelope.
     *
     * @param  string $envelopeType
     * @return self
     * @throws InvalidArgumentException
     */
    public function setEnvelope(string $envelopeType) : self
    {
        if (! in_array($envelopeType, $this->envelopeTypes)) {
            throw new InvalidArgumentException("Invalid envelope type '{$envelopeType}'");
        }

        $this->envelope = $envelopeType;
        return $this;
    }

    /**
     * Retrieve envelope.
     *
     * @return string
     */
    public function getEnvelope() : string
    {
        return $this->envelope;
    }

    /**
     * Set content type
     *
     * @param  string $type
     * @return self
     * @throws InvalidArgumentException
     */
    public function setContentType(string $type) : self
    {
        if (! preg_match($this->contentTypeRegex, $type)) {
            throw new InvalidArgumentException("Invalid content type '{$type}' specified");
        }

        $this->contentType = $type;
        return $this;
    }

    /**
     * Retrieve content type
     *
     * Content-Type of response; default to application/json.
     *
     * @return string
     */
    public function getContentType() : string
    {
        return $this->contentType;
    }

    /**
     * Set service target.
     *
     * @param  string $target
     * @return self
     */
    public function setTarget(string $target) : self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Retrieve service target.
     *
     * @return string
     */
    public function getTarget() : string
    {
        return $this->target;
    }

    /**
     * Set service ID.
     *
     * @param  string $id
     * @return self
     */
    public function setId(string $id) : self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get service id.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Set service description.
     *
     * @param  string $description
     * @return self
     */
    public function setDescription(string $description) : self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get service description.
     *
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * Indicate whether or not to generate Dojo-compatible SMD.
     *
     * @param  bool $flag
     * @return self
     */
    public function setDojoCompatible(bool $flag) : self
    {
        $this->dojoCompatible = $flag;
        return $this;
    }

    /**
     * Is this a Dojo compatible SMD?
     *
     * @return bool
     */
    public function isDojoCompatible() : bool
    {
        return $this->dojoCompatible;
    }

    /**
     * Add Service.
     *
     * @param Smd\Service|array $service
     * @return self
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function addService($service) : self
    {
        if (is_array($service)) {
            $service = new Smd\Service($service);
        }

        if (! $service instanceof Smd\Service) {
            throw new InvalidArgumentException('Invalid service passed to addService()');
        }

        $name = $service->getName();

        if (array_key_exists($name, $this->services)) {
            throw new RuntimeException('Attempt to register a service already registered detected');
        }

        $this->services[$name] = $service;
        return $this;
    }

    /**
     * Add many services.
     *
     * @param  array $services
     * @return self
     */
    public function addServices(array $services) : self
    {
        foreach ($services as $service) {
            $this->addService($service);
        }

        return $this;
    }

    /**
     * Overwrite existing services with new ones.
     *
     * @param  array $services
     * @return self
     */
    public function setServices(array $services) : self
    {
        $this->services = [];
        return $this->addServices($services);
    }

    /**
     * Get service object.
     *
     * @param  string $name
     * @return bool|Smd\Service
     */
    public function getService(string $name)
    {
        if (! array_key_exists($name, $this->services)) {
            return false;
        }

        return $this->services[$name];
    }

    /**
     * Return services.
     *
     * @return array
     */
    public function getServices() : array
    {
        return $this->services;
    }

    /**
     * Remove service.
     *
     * @param  string $name
     * @return bool
     */
    public function removeService(string $name) : bool
    {
        if (! array_key_exists($name, $this->services)) {
            return false;
        }

        unset($this->services[$name]);
        return true;
    }

    /**
     * Cast to array.
     *
     * @return array
     */
    public function toArray() : array
    {
        if ($this->isDojoCompatible()) {
            return $this->toDojoArray();
        }

        $description = $this->getDescription();
        $transport   = $this->getTransport();
        $envelope    = $this->getEnvelope();
        $contentType = $this->getContentType();
        $SMDVersion  = static::SMD_VERSION;
        $service     = compact('transport', 'envelope', 'contentType', 'SMDVersion', 'description');

        if (null !== ($target = $this->getTarget())) {
            $service['target'] = $target;
        }
        if (null !== ($id = $this->getId())) {
            $service['id'] = $id;
        }

        $services = $this->getServices();
        if (empty($services)) {
            return $service;
        }

        $service['services'] = [];
        foreach ($services as $name => $svc) {
            $svc->setEnvelope($envelope);
            $service['services'][$name] = $svc->toArray();
        }
        $service['methods'] = $service['services'];

        return $service;
    }

    /**
     * Export to DOJO-compatible SMD array
     *
     * @return array
     */
    public function toDojoArray() : array
    {
        $SMDVersion  = '.1';
        $serviceType = 'JSON-RPC';
        $service     = compact('SMDVersion', 'serviceType');
        $target      = $this->getTarget();
        $services    = $this->getServices();

        if (empty($services)) {
            return $service;
        }

        $service['methods'] = [];
        foreach ($services as $name => $svc) {
            $method = [
                'name'       => $name,
                'serviceURL' => $target,
            ];

            $params = [];
            foreach ($svc->getParams() as $param) {
                $params[] = [
                    'name' => array_key_exists('name', $param) ? $param['name'] : $param['type'],
                    'type' => $param['type'],
                ];
            }

            if (! empty($params)) {
                $method['parameters'] = $params;
            }

            $service['methods'][] = $method;
        }

        return $service;
    }

    /**
     * Cast to JSON.
     *
     * @return string
     */
    public function toJson() : string
    {
        return Json::encode($this->toArray());
    }

    /**
     * Cast to string (JSON)
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toJson();
    }
}
