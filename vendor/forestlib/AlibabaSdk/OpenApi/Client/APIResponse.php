<?php
namespace forestlib\AlibabaSdk\OpenApi\Client;

class APIResponse
{
  /**
   * status code
   *
   * @var int
   */
  public $statusCode;

  /**
   * result of api, the type base on the calling API defination.
   */
  public $result;

  /**
   * exception if there are some error when calling API.
   */
  public $exception;

  /**
   * respponse charset
   *
   * @var String
   */
  public $charset = "UTF-8";

  /**
   * response encoding
   *
   * @var String
   */
  public $encoding;

  /**
   * the response wrapper, maybe API return addtional information(e.g.
   * calling cost time), the wrapper include those information.
   */
  public $responseWrapper;
}
