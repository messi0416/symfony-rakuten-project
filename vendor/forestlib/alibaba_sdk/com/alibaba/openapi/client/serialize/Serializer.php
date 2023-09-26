<?php
interface Serializer
{
	public function supportedContentType();
	public function serialize($serializer);
}