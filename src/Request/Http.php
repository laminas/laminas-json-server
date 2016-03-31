<?php
/**
 * @link      http://github.com/zendframework/zend-json-server for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Json\Server\Request;

use Zend\Json\Server\Request as JsonRequest;

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
        $json = file_get_contents('php://input');
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
