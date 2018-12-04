<?php

namespace LaxCorp\ProfileAdminBundle\Exception;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @inheritdoc
 */
class ClientNotFoundException extends NoSuchPropertyException implements ExceptionInterface
{
}
