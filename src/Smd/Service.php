<?php

declare(strict_types=1);

namespace Laminas\Json\Server\Smd;

use Laminas\Json\Json;
use Laminas\Json\Server\Exception\InvalidArgumentException;
use Laminas\Json\Server\Smd;

use function array_key_exists;
use function array_keys;
use function array_search;
use function get_class_methods;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function ksort;
use function preg_match;
use function sprintf;
use function strtolower;
use function ucfirst;

/**
 * Create Service Mapping Description for a method
 *
 * @todo Revised method regex to allow NS; however, should SMD be revised to
 *     strip PHP NS instead when attaching functions?
 */
class Service
{
    /** @var string */
    protected $envelope = Smd::ENV_JSONRPC_1;

    protected string $name = '';

    /** @var null|string|array */
    protected $return;

    /** @var string|null */
    protected $target;

    /** @var string */
    protected $transport = 'POST';

    /**
     * Allowed envelope types.
     */
    protected array $envelopeTypes = [
        Smd::ENV_JSONRPC_1,
        Smd::ENV_JSONRPC_2,
    ];

    /**
     * Regex for names.
     *
     * @link http://php.net/manual/en/language.oop5.basic.php
     * @link http://www.jsonrpc.org/specification#request_object
     *
     * @var string
     */
    protected $nameRegex = '/^(?!^rpc\.)[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\.\\\]*$/';

    /**
     * Parameter option types.
     *
     * @var array
     */
    protected $paramOptionTypes = [
        'name'        => 'is_string',
        'optional'    => 'is_bool',
        'default'     => null,
        'description' => 'is_string',
    ];

    /**
     * Service params.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Mapping of parameter types to JSON-RPC types.
     *
     * @var array
     */
    protected $paramMap = [
        'any'     => 'any',
        'arr'     => 'array',
        'array'   => 'array',
        'assoc'   => 'object',
        'bool'    => 'boolean',
        'boolean' => 'boolean',
        'dbl'     => 'float',
        'double'  => 'float',
        'false'   => 'boolean',
        'float'   => 'float',
        'hash'    => 'object',
        'integer' => 'integer',
        'int'     => 'integer',
        'mixed'   => 'any',
        'nil'     => 'null',
        'null'    => 'null',
        'object'  => 'object',
        'string'  => 'string',
        'str'     => 'string',
        'struct'  => 'object',
        'true'    => 'boolean',
        'void'    => 'null',
    ];

    /**
     * Allowed transport types.
     *
     * @var array
     */
    protected $transportTypes = [
        'POST',
    ];

    /**
     * @param string|array $spec
     * @throws InvalidArgumentException If no name provided.
     */
    public function __construct($spec)
    {
        if (is_string($spec)) {
            $this->setName($spec);
        } elseif (is_array($spec)) {
            $this->setOptions($spec);
        }

        if ('' === $this->getName()) {
            throw new InvalidArgumentException('SMD service description requires a name; none provided');
        }
    }

    /**
     * Set object state.
     *
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            if ('options' === strtolower($key)) {
                continue;
            }

            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * Set service name.
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function setName(string $name)
    {
        if (! preg_match($this->nameRegex, $name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid name "%s" provided for service; must follow PHP method naming conventions',
                $name
            ));
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Retrieve name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Transport.
     *
     * Currently limited to POST.
     *
     * @param  string $transport
     * @return self
     * @throws InvalidArgumentException
     */
    public function setTransport($transport)
    {
        if (! in_array($transport, $this->transportTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid transport "%s"; please select one of (%s)',
                $transport,
                implode(', ', $this->transportTypes)
            ));
        }

        $this->transport = $transport;
        return $this;
    }

    /**
     * Get transport.
     *
     * @return string
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Set service target.
     *
     * @param  string  $target
     * @return self
     */
    public function setTarget($target)
    {
        $this->target = (string) $target;
        return $this;
    }

    /**
     * Get service target.
     *
     * @return null|string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set envelope type.
     *
     * @param  string $envelopeType
     * @return self
     * @throws InvalidArgumentException
     */
    public function setEnvelope($envelopeType)
    {
        if (! in_array($envelopeType, $this->envelopeTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid envelope type "%s"; please specify one of (%s)',
                $envelopeType,
                implode(', ', $this->envelopeTypes)
            ));
        }

        $this->envelope = $envelopeType;
        return $this;
    }

    /**
     * Get envelope type.
     *
     * @return string
     */
    public function getEnvelope()
    {
        return $this->envelope;
    }

    /**
     * Add a parameter to the service.
     *
     * @param string|array $type
     * @param array $options
     * @param int|null $order
     * @return self
     * @throws InvalidArgumentException
     */
    public function addParam($type, array $options = [], $order = null)
    {
        if (! is_string($type) && ! is_array($type)) {
            throw new InvalidArgumentException('Invalid param type provided');
        }

        if (is_string($type)) {
            $type = $this->validateParamType($type);
        }

        if (is_array($type)) {
            foreach ($type as $key => $paramType) {
                $type[$key] = $this->validateParamType($paramType);
            }
        }

        $paramOptions = ['type' => $type];
        foreach ($options as $key => $value) {
            if (in_array($key, array_keys($this->paramOptionTypes))) {
                if (null !== ($callback = $this->paramOptionTypes[$key])) {
                    if (! $callback($value)) {
                        continue;
                    }
                }
                $paramOptions[$key] = $value;
            }
        }

        $this->params[] = [
            'param' => $paramOptions,
            'order' => $order,
        ];

        return $this;
    }

    /**
     * Add params.
     *
     * Each param should be an array, and should include the key 'type'.
     *
     * @param array $params
     * @return self
     */
    public function addParams(array $params)
    {
        ksort($params);

        foreach ($params as $options) {
            if (! is_array($options)) {
                continue;
            }

            if (! array_key_exists('type', $options)) {
                continue;
            }

            $type  = $options['type'];
            $order = array_key_exists('order', $options) ? $options['order'] : null;
            $this->addParam($type, $options, $order);
        }

        return $this;
    }

    /**
     * Overwrite all parameters.
     *
     * @param array $params
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = [];
        return $this->addParams($params);
    }

    /**
     * Get all parameters.
     *
     * Returns all params in specified order.
     *
     * @return array
     */
    public function getParams()
    {
        $params = [];
        $index  = 0;

        foreach ($this->params as $param) {
            if (null === $param['order']) {
                if (array_search($index, array_keys($params), true)) {
                    ++$index;
                }

                $params[$index] = $param['param'];
                ++$index;
                continue;
            }

            $params[$param['order']] = $param['param'];
        }

        ksort($params);
        return $params;
    }

    /**
     * Set return type.
     *
     * @param string|array $type
     * @return self
     * @throws InvalidArgumentException
     */
    public function setReturn($type)
    {
        if (! is_string($type) && ! is_array($type)) {
            throw new InvalidArgumentException("Invalid param type provided ('" . gettype($type) . "')");
        }

        if (is_string($type)) {
            $type = $this->validateParamType($type, true);
        }

        if (is_array($type)) {
            foreach ($type as $key => $returnType) {
                $type[$key] = $this->validateParamType($returnType, true);
            }
        }

        $this->return = $type;
        return $this;
    }

    /**
     * Get return type.
     *
     * @return null|string|array
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * Cast service description to array.
     *
     * @return array
     */
    public function toArray()
    {
        $envelope   = $this->getEnvelope();
        $target     = $this->getTarget();
        $transport  = $this->getTransport();
        $parameters = $this->getParams();
        $returns    = $this->getReturn();
        $name       = $this->getName();

        if (empty($target)) {
            return [
                'envelope'   => $envelope,
                'transport'  => $transport,
                'name'       => $name,
                'parameters' => $parameters,
                'returns'    => $returns,
            ];
        }

        return [
            'envelope'   => $envelope,
            'target'     => $target,
            'transport'  => $transport,
            'name'       => $name,
            'parameters' => $parameters,
            'returns'    => $returns,
        ];
    }

    /**
     * Return JSON encoding of service.
     *
     * @return string
     */
    public function toJson()
    {
        return Json::encode([
            $this->getName() => $this->toArray(),
        ]);
    }

    /**
     * Cast to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Validate parameter type.
     *
     * @param string $type
     * @param bool $isReturn
     * @return string
     * @throws InvalidArgumentException
     */
    protected function validateParamType($type, $isReturn = false)
    {
        if (! is_string($type)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid param type provided ("%s")',
                $type
            ));
        }

        if (! array_key_exists($type, $this->paramMap)) {
            $type = 'object';
        }

        $paramType = $this->paramMap[$type];
        if (! $isReturn && ('null' === $paramType)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid param type provided ("%s")',
                $type
            ));
        }

        return $paramType;
    }
}
