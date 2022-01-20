<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Traits;

use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\LogicException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\RuntimeException;

trait CascadedSoftDeletes
{

    /**
     * Keeps track of Instance deleted_at time.
     * 
     * @var \Carbon\Carbon
     */
    public static $instanceDeletedAt;

    /**
     * Sign on model events.
     */
    protected static function bootCascadedSoftDeletes()
    {
        
        static::deleted(function ($model) {
            $model->deleteCascadedSoftDeletes();
        });

        if (static::instanceUsesSoftDelete()) {
            static::restoring(function ($model) {
                static::$instanceDeletedAt = $model->{$model->getDeletedAtColumn()};
            });

            static::restored(function ($model) {
                $model->restoreCascadedSoftDeleted();
            });
        }
    }

    /**
     * Delete the cascaded relations.
     */
    protected function deleteCascadedSoftDeletes()
    {
        if($this->isHardDeleting())
            return;

        $this->runCascadedSoftDeletesAction('delete');
    }

    /**
     * Restore the cascaded relations.
     */
    protected function restoreCascadedSoftDeleted()
    {
        $this->runCascadedSoftDeletesAction('restore');
    }

    /**
     * Delete/Restore Cascaded Relationships
     *
     * @param $action
     */
    protected function runCascadedSoftDeletesAction($action)
    {
        if(!method_exists($this, 'getCascadedSoftDeletes')) {
            throw new RuntimeException('getCascadedSoftDeletes function not found!');
        }

        collect($this->getCascadedSoftDeletes())->each(function($item, $key) use ($action) {

            $relation = $key;
            if(is_numeric($key))
                $relation = $item;

            if(!is_callable($item) && !$this->relationUsesSoftDelete($relation)) 
                throw new LogicException('relationship '.$relation.' does not use SoftDeletes trait.');

            if(is_callable($item))
                $query = $item();
            else
                $query = $this->{$relation}();

            if($action == 'delete')
                $query->get()->each->delete();
            else
                $query
                    ->onlyTrashed()
                    ->where($this->getDeletedAtColumn(), '>=', static::$instanceDeletedAt)
                    ->get()
                    ->each
                    ->restore();

        });

    }

    /**
     * Get whether user is intended to delete the model from database entirely.
     *
     * @return bool
     */
    protected function isHardDeleting()
    {
        return ! $this->instanceUsesSoftDelete() || $this->forceDeleting;
    }

    /**
     * @return bool
     */
    public static function instanceUsesSoftDelete()
    {
        static $softDelete;

        if (is_null($softDelete)) {
            $instance = new static;

            return $softDelete = method_exists($instance, 'bootSoftDeletes');
        }

        return $softDelete;
    }

    public static function relationUsesSoftDelete($relation)
    {
        static $softDeletes;

        if(is_null($softDeletes))
            $softDeletes = collect([]);

        return $softDeletes->get($relation, function() use($relation, $softDeletes) {
            $instance = new static;
            $cls = $instance->{$relation}()->getRelated();
            $relationInstance = new $cls;
            return $softDeletes->put($relation, method_exists($relationInstance, 'bootSoftDeletes'))->get($relation);
        });
    }
}