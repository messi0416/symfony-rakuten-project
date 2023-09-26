<?php
namespace forestlib\AlibabaSdk\OpenApi\Client\Serialize;

interface Serializer
{
  public function supportedContentType();

  public function serialize($serializer);
}
