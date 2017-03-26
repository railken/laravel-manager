<?php

namespace Railken\Laravel\Manager;

use Railken\Laravel\Manager\ModelContract;
use Railken\Laravel\Manager\Exceptions\InvalidParamValueException;
use Railken\Laravel\Manager\Exceptions\MissingParamException;

abstract class ModelManager
{

	/**
	 * Construct
	 *
	 */
	public function __construct()
	{

	}

	/**
	 * Retrieve repository
	 *
	 * @return Railken\Laravel\Manager\RepositoryModel
	 */
	public function getRepository()
	{
		return $this->repository;
	}

	/**
	 * Find
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function find($params)
	{
		return $this->getRepository()->find($params);
	}

	/**
	 * Find where in
	 *
	 * @param array $params
	 *
	 * @return Collection ?
	 */
	public function findWhereIn(array $params)
	{
		return $this->getRepository()->findWhereIn($params);
	}

	/**
	 * Create a new ModelContract given array
	 *
	 * @param array $params
	 *
	 * @return Railken\Laravel\Manager\ModelContract
	 */
	public function create(array $params)
	{

		$entity = $this->getRepository()->newEntity();
		$this->update($entity, $params);
		$this->save($entity);

		return $entity;
	}

	/**
	 * Update a ModelContract given array
	 *
	 * @param array $params
	 *
	 * @return Railken\Laravel\Manager\ModelContract
	 */
	public function update(ModelContract $entity, array $params)
	{

		$this->fill($entity, $params);
		$this->save($entity);

		return $entity;
	}

	/**
	 * Fill entity ModelContract with array
	 *
	 * @param Railken\Laravel\Manager\ModelContract $entity
	 * @param array $params
	 *
	 * @return void
	 */
	abstract public function fill(ModelContract $entity, array $params);

	/**
	 * Convert entity to array
	 *
	 * @param  Railken\Laravel\Manager\ModelContract $entity
	 *
	 * @return array
	 */
	abstract public function toArray(ModelContract $entity);


	/**
	 * Remove multiple ModelContract
	 *
	 * @param array $entities
	 *
	 * @return void
	 */
	public function deleteMultiple($entities)
	{
		foreach ($entities as $entity) {
			$this->delete($entity);
		}
	}

	/**
	 * Remove a ModelContract
	 *
	 * @param Railken\Laravel\Manager\ModelContract $entity
	 *
	 * @return void
	 */
	public function delete(ModelContract $entity)
	{
		$entity->delete();
	}

	/**
	 * Save the entity
	 *
	 * @param  Railken\Laravel\Manager\ModelContract $entity
	 *
	 * @return ModelContract
	 */
	 public function save(ModelContract $entity)
	 {
		 $entity->save();
	 }

	 /**
	  * Throw an exception if a value is invalid
	  *
	  * @param string $name
		  * @param string $value
		  * @param mixed $accepted
	  *
	  * @return void
	  */
	public function throwExceptionInvalidParamValue($name, $value, $accepted)
	{
		if (is_array($accepted)) {
			if (!in_array($value, $accepted))
		        throw new InvalidParamValueException("Invalid value {$value} for param {$name}. Accepted: ".implode($accepted, ","));
		}
	}

	/**
	 * Throw an exception if a parameter is null
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	public function throwExceptionParamsNull($params)
	{
	    foreach($params as $name => $value) {
	        if($value == null) {
	            throw new MissingParamException("Missing parameter: {$name}");
	        }
	    }
	}

	/**
	 * Get only specific params
	 *
	 * @param array $params
	 * @param array $requested
	 *
	 * @return array
	*/
	public function getOnlyParams(array $params, array $requested)
	{
		return (array_intersect_key($params, array_flip($requested)));
	}
}
