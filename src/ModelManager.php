<?php

namespace Railken\Laravel\Manager;

use Railken\Laravel\Manager\ModelContract;
use Railken\Laravel\Manager\Exceptions\InvalidParamValueException;
use Railken\Laravel\Manager\Exceptions\MissingParamException;
use Railken\Laravel\Manager\Exceptions\ModelByIdNotFoundException;

use Railken\Laravel\Manager\Permission\AgentContract;

use DB;
use Exception;

use Illuminate\Http\UploadedFile;
use File;

abstract class ModelManager
{

    /**
     * @var array
     */
    protected $vars = [];

    /**
     * @var queue
     */
    public $queue = [];

    /**
     * Construct
     *
     */
    public function __construct(AgentContract $agent = null)
    {

        $this->agent = $agent;
        $this->vars = collect();
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
     * @param array $params
     *
     * @return ModelContract
     */
    public function findOrCreate(array $params)
    {
        $entity = $this->getRepository()->getQuery()->where($params)->first();

        return $entity ? $entity : $this->create($params);
    }

    /**
     * Update or create
     *
     * @param array $criteria
     * @param array $params
     *
     * @return ModelContract
     */
    public function updateOrCreate(array $criteria, array $params)
    {
        $entity = $this->getRepository()->getQuery()->where($criteria)->first();

        return $entity ? $this->update($entity, $params) : $this->create($params);
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
        return $this->update($entity, $params);
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
        DB::beginTransaction();

        try {
            $this->fill($entity, $params);
            $this->save($entity);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $entity;
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
     * @param array $params
     *
     * @return void
     */
    public function fill(ModelContract $entity, array $params)
    {
        $entity->fill($params);
        return $entity;
    }

    /**
     * Fill an attribute of relation Many to One given id or entity
     *
     * @param ModelContract $entity
     * @param ModelManager $manager
     * @param array $params
     * @param string $attribute
     * @param string $attribute_id
     *
     * @return $entity
     */
    public function fillManyToOneById(ModelContract $entity, ModelManager $manager, $params, $attribute, $attribute_id = null)
    {
        if ($attribute_id == null) {
            $attribute_id = $attribute."_id";
        }

        if (isset($params[$attribute_id])) {
            $value = $manager->getRepository()->findById($params[$attribute_id]);

            if (!$value) {
                throw new ModelByIdNotFoundException($attribute_id, $params[$attribute_id]);
            }

            $params[$attribute] = $value;
        }

        if (isset($params[$attribute])) {
            $value = $params[$attribute];
            $entity->$attribute_id = $params[$attribute]->id;
            $this->vars[$attribute] = $value;
        }

        return $value;
    }

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
            if (!in_array($value, $accepted)) {
                throw new InvalidParamValueException("Invalid value {$value} for param {$name}. Accepted: ".implode($accepted, ","));
            }
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
        foreach ($params as $name => $value) {
            if ($value == null) {
                throw new MissingParamException("Missing parameter: {$name}");
            }
        }
    }

    /**
     * Throw an exception if wrong permission
     *
     * @param array $params
     *
     * @return void
     */
    public function throwExceptionAccessDenied($permission, $entity)
    {
        if (!$this->can($permission, $entity)) {
            abort(401);
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


    /**
     * Execute queue
     *
     * @return null
     */
    public function executeQueue()
    {
        foreach ($this->getQueue() as $queue) {
            $queue();
        }

        $this->setQueue([]);
    }
    
    /**
     * Add an operation to queue
     *
     * @param Closure $closure
     *
     * @return this
     */
    public function addQueue(\Closure $closure)
    {
        $this->queue[] = $closure;
    }

    /**
     * Retrieve all queue
     *
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }
    
    /**
     * Add an operation to queue
     *
     * @param array $queue
     *
     * @return array
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * Convert field base64 encoded into FileUploaded
     *
     * @param string $base64
     *
     * @return UploadedFile
     */
    public function base64ToUploadedFile($base64)
    {

        $path = tempnam(sys_get_temp_dir(), '_');

        $fp = fopen($path, "w");
        fwrite($fp, base64_decode($base64));
        fclose($fp);

        $name = File::name($path);
        $extension = File::extension($path);

        return new UploadedFile($path, $name.'.'.$extension, File::mimeType($path), File::size($path), null, false);
    }
}
