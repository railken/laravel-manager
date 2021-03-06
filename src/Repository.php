<?php

namespace Railken\Lem;

use Railken\Lem\Contracts\RepositoryContract;

class Repository implements RepositoryContract
{
    use Concerns\HasManager;

    /**
     * Entity class.
     *
     * @param string $entity
     */
    protected $entity;

    /**
     * Scopes.
     *
     * @param array
     */
    protected static $scopes = [];

    /**
     * Retrieve new instance of entity.
     *
     * @return \Railken\Lem\Contracts\EntityContract
     */
    public function newEntity()
    {
        return $this->manager->newEntity();
    }

    /**
     * Set entity.
     *
     * @param string $entity
     *
     * @return $this
     */
    public function setEntity(string $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Return entity.
     *
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * Find all.
     *
     * @return \Illuminate\Support\Collection
     */
    public function findAll()
    {
        return $this->getQuery()->get();
    }

    /**
     * Find by primary.
     *
     * @param array $parameters
     *
     * @return \Illuminate\Support\Collection
     */
    public function findBy($parameters = [])
    {
        return $this->getQuery()->where($this->filterParameters($parameters))->get();
    }

    /**
     * Find one by.
     *
     * @param array $parameters
     *
     * @return \Railken\Lem\Contracts\EntityContract|object|null
     */
    public function findOneBy($parameters = [])
    {
        return $this->getQuery()->where($this->filterParameters($parameters))->first();
    }

    /**
     * Find one by.
     *
     * @param int $id
     *
     * @return \Railken\Lem\Contracts\EntityContract|object|null
     */
    public function findOneById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Find where in.
     *
     * @param array $parameters
     *
     * @return \Illuminate\Support\Collection
     */
    public function findWhereIn(array $parameters)
    {
        $q = $this->getQuery();

        foreach ($parameters as $name => $value) {
            $q->whereIn($name, $value);
        }

        return $q->get();
    }

    /**
     * Return query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery()
    {
        return $this->newQuery();
    }

    /**
     * Return query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $query = $this->newEntity()->newQuery();

        $this->applyScopes($query);

        return $query->select($this->newEntity()->getTable().'.*');
    }

    public function filterParameters(array $parameters = []): array
    {
        $parameters = collect($parameters)->mapWithKeys(function ($item, $key) {
            $key = count(explode(".", $key)) > 1 ? $key : $this->newEntity()->getTable().".".$key;

            return [$key => $item];
        })->toArray();

        return $parameters;
    }

    public static function addScope($scope)
    {
        static::$scopes[] = $scope;
    }

    public function applyScopes($query)
    {
        foreach (static::$scopes as $scope) {
            $scope->apply($this->getManager(), $query);
        }
    }

    public static function resetScopes()
    {
        static::$scopes = [];
    }
}
