<?php

declare(strict_types=1);

namespace Laminas\Json\Server;

use Laminas\Json\Json;

use function is_bool;
use function is_float;
use function is_numeric;
use function is_scalar;
use function is_string;

class Error
{
    public const ERROR_PARSE           = -32700;
    public const ERROR_INVALID_REQUEST = -32600;
    public const ERROR_INVALID_METHOD  = -32601;
    public const ERROR_INVALID_PARAMS  = -32602;
    public const ERROR_INTERNAL        = -32603;
    public const ERROR_OTHER           = -32000;

    /**
     * Current code
     *
     * @var int
     */
    protected $code = self::ERROR_OTHER;

    /**
     * Error data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Error message
     *
     * @var string
     */
    protected $message;

    /**
     * @param  string $message
     * @param  int $code
     * @param  mixed $data
     */
    public function __construct($message = null, $code = self::ERROR_OTHER, $data = null)
    {
        $this->setMessage($message)
             ->setCode($code)
             ->setData($data);
    }

    /**
     * Set error code.
     *
     * If the error code is 0, it will be set to -32000 (ERROR_OTHER).
     *
     * @param  int $code
     * @return self
     */
    public function setCode($code)
    {
        if (! is_scalar($code) || is_bool($code) || is_float($code)) {
            return $this;
        }

        if (is_string($code) && ! is_numeric($code)) {
            return $this;
        }

        $code = (int) $code;

        if (0 === $code) {
            $this->code = self::ERROR_OTHER;
            return $this;
        }

        $this->code = $code;
        return $this;
    }

    /**
     * Get error code
     *
     * @return int|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set error message
     *
     * @param  string $message
     * @return self
     */
    public function setMessage($message)
    {
        if (! is_scalar($message)) {
            return $this;
        }

        $this->message = (string) $message;
        return $this;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set error data
     *
     * @param  mixed $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get error data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Cast error to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'code'    => $this->getCode(),
            'message' => $this->getMessage(),
            'data'    => $this->getData(),
        ];
    }

    /**
     * Cast error to JSON
     *
     * @return string
     */
    public function toJson()
    {
        return Json::encode($this->toArray());
    }

    /**
     * Cast to string (JSON)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
