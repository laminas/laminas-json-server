<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Json\Server;

use Laminas\Json\Exception\RuntimeException;
use Laminas\Json\Json;

use function get_class_methods;
use function in_array;
use function is_array;
use function ucfirst;

class Response
{
    /**
     * Response error
     *
     * @var null|Error
     */
    protected $error;

    /**
     * Request ID
     *
     * @var string
     */
    protected $id;

    /**
     * Result
     *
     * @var mixed
     */
    protected $result;

    /**
     * Service map
     *
     * @var Smd
     */
    protected $serviceMap;

    /**
     * JSON-RPC version
     *
     * @var null|string
     */
    protected $version;

    /**
     * @var mixed
     */
    protected $args;

    /**
     * Set response state.
     *
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        // re-produce error state
        if (isset($options['error']) && is_array($options['error'])) {
            $error = $options['error'];
            $errorData = isset($error['data']) ? $error['data'] : null;
            $options['error'] = new Error($error['message'], $error['code'], $errorData);
        }

        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods, true)) {
                $this->$method($value);
                continue;
            }

            if ('jsonrpc' === $key) {
                $this->setVersion($value);
                continue;
            }
        }
        return $this;
    }

    /**
     * Set response state based on JSON.
     *
     * @param  string $json
     * @return void
     * @throws Exception\RuntimeException
     */
    public function loadJson(string $json): void
    {
        try {
            $options = Json::decode($json, Json::TYPE_ARRAY);
        } catch (RuntimeException $e) {
            throw new Exception\RuntimeException(
                'json is not a valid response; array expected',
                $e->getCode(),
                $e
            );
        }

        if (! is_array($options)) {
            throw new Exception\RuntimeException('json is not a valid response; array expected');
        }

        $this->setOptions($options);
    }

    /**
     * Set result.
     *
     * @param  mixed $value
     * @return self
     */
    public function setResult($value): self
    {
        $this->result = $value;
        return $this;
    }

    /**
     * Get result.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set result error
     *
     * RPC error, if response results in fault.
     *
     * @param  mixed $error
     * @return self
     */
    public function setError(?Error $error = null): self
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get response error
     *
     * @return null|Error
     */
    public function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * Is the response an error?
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->getError() instanceof Error;
    }

    /**
     * Set request ID
     *
     * @param  mixed $name
     * @return self
     */
    public function setId($name): self
    {
        $this->id = $name;
        return $this;
    }

    /**
     * Get request ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set JSON-RPC version.
     *
     * @param  string|null $version
     * @return self
     */
    public function setVersion(?string $version): self
    {
        if ('2.0' === $version) {
            $this->version = '2.0';
            return $this;
        }

        $this->version = null;
        return $this;
    }

    /**
     * Retrieve JSON-RPC version
     *
     * @return null|string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Cast to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        $response = ['id' => $this->getId()];

        if ($this->isError()) {
            $response['error'] = $this->getError()->toArray();
        } else {
            $response['result'] = $this->getResult();
        }

        if (null !== ($version = $this->getVersion())) {
            $response['jsonrpc'] = $version;
        }

        return Json::encode($response);
    }

    /**
     * Retrieve args.
     *
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Set args.
     *
     * @param mixed $args
     * @return self
     */
    public function setArgs($args): self
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Set service map object.
     *
     * @param  Smd|null $serviceMap
     * @return self
     */
    public function setServiceMap(?Smd $serviceMap): self
    {
        $this->serviceMap = $serviceMap;
        return $this;
    }

    /**
     * Retrieve service map.
     *
     * @return Smd|null
     */
    public function getServiceMap(): ?Smd
    {
        return $this->serviceMap;
    }

    /**
     * Cast to string (JSON).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
