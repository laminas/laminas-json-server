<?php

declare(strict_types=1);

namespace Laminas\Json\Server\Request;

use Laminas\Json\Server\Request as JsonRequest;

use function file_get_contents;

class Http extends JsonRequest
{
    /**
     * Raw JSON pulled from POST body
     *
     * @var string
     */
    protected $rawJson;

    /**
     * Pull JSON request from raw POST body and use to populate request.
     */
    public function __construct()
    {
        $json          = file_get_contents('php://input');
        $this->rawJson = $json;
        if (! empty($json)) {
            $this->loadJson($json);
        }
    }

    /**
     * Get JSON from raw POST body
     *
     * @return string
     */
    public function getRawJson()
    {
        return $this->rawJson;
    }
}
