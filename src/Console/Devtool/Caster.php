<?php

namespace Rejoice\Console\Devtool;

use Symfony\Component\VarDumper\Caster\Caster as CasterHelper;

class Caster
{
    /**
     * Get an array representing the properties of a collection.
     *
     * @param \Illuminate\Support\Collection $collection
     *
     * @return array
     * @source \Laravel\Tinker\TinkerCaster::castCollection
     */
    public static function castCollection($collection)
    {
        return [
            CasterHelper::PREFIX_VIRTUAL.'all' => $collection->all(),
        ];
    }

    /**
     * Get an array representing the properties of a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return array
     * @source \Laravel\Tinker\TinkerCaster::castModel
     */
    public static function castModel($model)
    {
        $attributes = array_merge(
            $model->getAttributes(),
            $model->getRelations()
        );

        $visible = array_flip(
            $model->getVisible() ?: array_diff(array_keys($attributes), $model->getHidden())
        );

        $results = [];

        foreach (array_intersect_key($attributes, $visible) as $key => $value) {
            $results[(isset($visible[$key]) ? CasterHelper::PREFIX_VIRTUAL : CasterHelper::PREFIX_PROTECTED).$key] = $value;
        }

        return $results;
    }
}
