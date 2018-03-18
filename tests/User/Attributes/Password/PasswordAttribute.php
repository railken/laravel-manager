<?php

namespace Railken\Laravel\Manager\Tests\User\Attributes\Password;


use Railken\Laravel\Manager\Contracts\EntityContract;
use Railken\Laravel\Manager\ModelAttribute;
use Railken\Laravel\Manager\Traits\AttributeValidateTrait;
use Railken\Laravel\Manager\Tests\User\Attributes\Password\Exceptions as Exceptions;
use Railken\Laravel\Manager\Tokens;
use Respect\Validation\Validator as v;

class PasswordAttribute extends ModelAttribute
{

	/**
	 * Name attribute
	 *
	 * @var string
	 */
	protected $name = 'password';

    /**
     * Is the attribute required
     * This will throw not_defined exception for non defined value and non existent model
     *
     * @var boolean
     */
    protected $required = true;

    /**
     * Is the attribute unique 
     *
     * @var boolean
     */
    protected $unique = false;

    /**
     * List of all exceptions used in validation
     *
     * @var array
     */
    protected $exceptions = [
    	Tokens::NOT_DEFINED => Exceptions\UserPasswordNotDefinedException::class,
    	Tokens::NOT_VALID => Exceptions\UserPasswordNotValidException::class,
        Tokens::NOT_AUTHORIZED => Exceptions\UserPasswordNotAuthorizedException::class
    ];

    /**
     * List of all permissions
     */
    protected $permissions = [
        Tokens::PERMISSION_FILL => 'user.attributes.password.fill',
        Tokens::PERMISSION_SHOW => 'user.attributes.password.show'
    ];

    /**
     * Is a value valid ?
     *
     * @param EntityContract $entity
     * @param mixed $value
     *
     * @return boolean
     */
	public function valid(EntityContract $entity, $value)
	{
		return v::length(8, 255)->validate($value);
	}

}