<?php namespace Dec\Api\Pagination;

use Carbon\Carbon;
use DateTime;
use Exception;
use Input;
use InvalidArgumentException;
use Log;
use Response;

class Paginator {

    public static $defaultCount = 20;
    public static $defaultSimpleFields = ['id', 'name'];

    public static function paginate($model, array $simpleFields = null)
    {
        $showAll = Input::getBoolean('all');
        $simple = Input::getBoolean('simple');
        $with = Input::has('with') ? explode(',', Input::get('with')) : null;

        $simpleFields = $simpleFields ?: static::$defaultSimpleFields;

        // String = Model class name
        if (is_string($model))
        {
            $builder = $model::query();
        }
        else
        {
            $builder = $model;
            $model = $builder->getModel();
        }

        if (!is_a($builder, 'Illuminate\Database\Eloquent\Builder'))
            throw new InvalidArgumentException('$model should be a string or Builder');

        if ($simple)
        {
            // Remove eager loads so we don't have null objects in the response
            $builder->setEagerLoads([]);
            $builder->select($simpleFields);
        }
        else if (count($with) > 0)
        {
            foreach ($with as $relation)
            {
                if (method_exists($model, explode('.', $relation)[0]))
                {
                    $builder->with($relation);
                }
            }
        }

        if (Input::has('since'))
        {
            try
            {
                $builder->where('updated_at', '>', Input::getTimestamp('since'));
            }
            catch (Exception $e)
            {
                return Response::error("Invalid 'since' timestamp");
            }
        }

        if (Input::has('order_by'))
        {
            $ascending = Input::getBoolean('asc', true);
            $builder->orderBy(Input::get('order_by'), $ascending ? 'asc' : 'desc');
        }


        if ($showAll)
            return $builder->get();

        $count = Input::get('count') ?: static::$defaultCount;

        return $builder->paginate($count);
    }

}
