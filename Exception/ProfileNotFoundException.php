<?php

namespace LaxCorp\ProfileAdminBundle\Exception;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @inheritdoc
 */
class ProfileNotFoundException extends NoSuchPropertyException implements ExceptionInterface
{

}
