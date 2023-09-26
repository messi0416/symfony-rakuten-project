<?php
namespace MiscBundle\Exception;

use BatchBundle\Exception\LeveledException;

/**
 * 業務例外。
 *
 * 各種業務的に想定されている例外を管理するのに使用する。
 *
 * @author a-jinno
 */
class BusinessException extends LeveledException
{
}