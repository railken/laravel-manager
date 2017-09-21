<?php

namespace Railken\Laravel\Manager;

use Railken\Laravel\Manager\ModelContract;
use Railken\Laravel\Manager\Exceptions\InvalidParamValueException;
use Railken\Laravel\Manager\Exceptions\MissingParamException;
use Railken\Laravel\Manager\Exceptions\ModelByIdNotFoundException;
use Railken\Laravel\Manager\Permission\AgentContract;

use DB;
use Exception;
use Railken\Bag;
use Illuminate\Support\Collection;

abstract class ModelManager
{
    /**
     * Construct
     *
     */
    public function __construct(AgentContract $agent = null)
    {

        $this->agent = $agent;
    }

    /**
     * Retrieve agent
     *
     * @return Agent
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Set the agent
     *
     * @param AgentContract $agent
     *
     * @return $this
     */
    public function setAgent(AgentContract $agent)
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Filter parameters
     *
     * @param array|Bag $parameters
     *
     * @return ParameterBag
     */
    public function parameters($parameters)
    {
        return new ParameterBag($parameters);
    }

    /**
     * Has permission to do?
     *
     * @param string $permission
     * @param ModelContract $entity
     *
     * @return bool
     */
    public function can($permission, $entity)
    {
        return $this->getAgent()->can($permission, $entity);
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
     * First or create
     *
     * @param Bag $parameters
     *
     * @return ModelContract
     */
    public function findOrCreate(Bag $parameters)
    {
        $entity = $this->getRepository()->getQuery()->where($parameters->all())->first();

        return $entity ? $entity : $this->create($parameters);
    }

    /**
     * Update or create
     *
     * @param Bag $criteria
     * @param Bag $parameters
     *
     * @return ModelContract
     */
    public function updateOrCreate(Bag $criteria, Bag $parameters)
    {
        $entity = $this->getRepository()->getQuery()->where($criteria->all())->first();

        return $entity ? $this->update($entity, $parameters) : $this->create($parameters);
    }

    /**
     * Find
     *
     * @param Bag $parameters
     *
     * @return mixed
     */
    public function find(Bag $parameters)
    {
        return $this->getRepository()->find($parameters->all());
    }

    /**
     * Find where in
     *
     * @param Bag $parameters
     *
     * @return Collection ?
     */
    public function findWhereIn(Bag $parameters)
    {
        return $this->getRepository()->findWhereIn($parameters);
    }

    /**
     * Create a new ModelContract given array
     *
     * @param Bag $parameters
     *
     * @return Railken\Laravel\Manager\ModelContract
     */
    public function create(Bag $parameters)
    {
        return $this->update($this->getRepository()->newEntity(), $parameters);
    }

    /**
     * Update a ModelContract given array
     *
     * @param Bag $parameters
     *
     * @return Railken\Laravel\Manager\ModelContract
     */
    public function update(ModelContract $entity, Bag $parameters)
    {
        DB::beginTransaction();
        $result = new ResultExecute();
        try {

            if ($this->agent) {
                $parameters = $parameters->filterByAgent($this->agent);
                $result->addErrors($this->authorizer->authorize($entity, $parameters));
            }

            $result->addErrors($this->validator->validate($entity, $parameters));


            if (!$result->ok()) {
                DB::rollBack();
                return $result;
            }
            
            $this->fill($entity, $parameters);
            $this->save($entity);

            $result->getResources()->push($entity);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $result;
    }



    /**
     * Remove a ModelContract
     *
     * @param Railken\Laravel\Manager\ModelContract $entity
     *
     * @return void
     */
    public function remove(ModelContract $entity)
    {
        return $entity->delete();
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
        return $entity->save();
    }


    /**
     * Fill entity ModelContract with array
     *
     * @param Railken\Laravel\Manager\ModelContract $entity
     * @param Bag $parameters
     *
     * @return void
     */
    public function fill(ModelContract $entity, Bag $parameters)
    {
        $entity->fill($parameters);
        return $entity;
    }
}
