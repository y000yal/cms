<?php


namespace App\Repositories;

use App\Interfaces\BaseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Str;

/**
 * @namespace App\Repositories
 * @class    BaseRepository
 * @author   Yoyal Limbu
 * @date     14-10-2025 : 08:39 PM
 */
class BaseRepository implements BaseInterface {
    protected Model $model;

    public function __construct(Model $model) {
        $this->model = $model;
    }

    /**
     * Insert new row in related table.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data): mixed {
        return $this->model->create($data);
    }

    /**
     * Insert multiple row in related table.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function insert(array $data): mixed {
        return $this->model->insert($data);
    }


    /**
     * Update row of given id in related table.
     *
     * @param $id
     * @param array $data
     *
     * @return mixed
     */
    public function update($id, array $data): mixed {

        $this->model->findOrFail($id)->update($data);
        $data = $this->model->findOrFail($id);
        return $data;
    }

    /**
     * Delete row of given id in related table.
     *
     * @param $id
     *
     * @return mixed
     */
    public function delete($id): mixed {
        return $this->model->findOrFail($id)->delete();
    }

    /**
     * Get data related to given id in related table.
     *
     * @param $id
     *
     * @return mixed
     */
    public function getSpecificById($id): mixed {
        return $this->model->findOrFail($id);
    }

    /**
     * getAll
     *
     * @param array $parameter
     * @param $path
     *
     * @return mixed
     */
    public function getAll( array $parameter, $path): mixed {
        $columnsList = Schema::getColumnListing($this->model->getTable());
        $orderByColumn = "id";
        $selectFields = [];
        foreach ($columnsList as $columnName) {
            if (isset($parameter["sort_field"]) && $columnName == $parameter["sort_field"]) {
                $orderByColumn = $columnName;
                break;
            }
        }
        $parameter["sort_field"] = $orderByColumn;
        if (isset($parameter["filter_field"])) {
            if (in_array($parameter["filter_field"], $columnsList)) {
                $data = $this->model->where($parameter["filter_field"], $parameter["filter_value"]);
            }
            else {
                $data = $this->model;
            }
        }
        else {
            $data = $this->model;
        }
        /**
         * Multiple filter Implementation
         */
        if (isset($parameter["has"])) {
            $hasParams = $parameter["has"];
            foreach ($hasParams as $key => $val) {
                $data = ($val === 'true') ? $data->whereNotNull($key) : $data->whereNull($key);
            }
        }
        if (isset($parameter["filter"])) {
            $filterParams = $parameter["filter"];
            foreach ($filterParams as $key => $val) {
                if (is_array($val)) {
                    $data = $data->where(function ($q) use ($val, $key) {
                        foreach ($val as $v) {
                            $q->orWhere($key, "like", '%' . $v . '%');
                        }
                    });
                }
                else {
                    /**
                     * Check if filter is needed from relationship or column of a table.
                     * If item count of $checkKey is 1 after exploding $key, filter from table column. Else use relation existence method for filter.
                     */
                    $checkKey = explode(".", $key);
                    $count = count($checkKey);
                    array_push($selectFields, $checkKey[0]);

                    if ($count == 1) {
                        if (empty($val) || $val == "null") {
                            $data = $data->where($key, null);
                        }
                        else {
                            $data = $data->where($key, "like", "%$val%");
                        }
                    }
                    else {
                        $relationKey = Str::camel(implode(".", Arr::except($checkKey, [$count - 1])));
                        $data = $data->whereHas($relationKey, function ($query) use ($checkKey, $val) {
                            $query->where(last($checkKey), 'like', "%$val%");
                        });

                    }
                }
            }
        }
        if (isset($parameter["q"])) {
            $searchValue = "%" . $parameter["q"] . "%";

            $data = $data->where(function ($query) use ($searchValue, $columnsList) {
                foreach ($columnsList as $key => $columnName) {
                    $query->orWhere($columnName, "like", $searchValue);
                }
            });

        }
        if (isset($parameter["start_date"])) {
            $data = $data->where('created_at', '>=', $parameter['start_date'] . ' 00:00:00');
        }
        if (isset($parameter["end_date"])) {
            $data = $data->where('created_at', '<=', $parameter['end_date'] . ' 23:59:59');
        }
        if (isset($parameter['with_relationship'])) {
            $data = $data->with($parameter['with_relationship']);
        }
        if (isset($parameter["where"])) {
            foreach ($parameter["where"] as  $where){
                $data = $data->orWhere($where[0], $where[1]);
            }
        }
        if (isset($parameter['select'])) {
            if (isset($parameter['select'])) {
                $fields = explode(',', $parameter['select']);
                $fields = array_filter($fields, fn($f) => in_array($f, $columnsList));
                $selectFields = array_merge($selectFields, $fields);
                $data = $data->select($selectFields);
            }
        }
        if (isset($parameter['sort_by'])) {
            $data = $data->orderBy($orderByColumn, $parameter["sort_by"]);
        }
        return $data->paginate($parameter["limit"])->withPath($path)->appends($parameter);
    }

    /**
     * getSpecificByColumnValue
     *
     * @param $column
     * @param $value
     *
     * @return mixed
     */
    public function getSpecificByColumnValue($column, $value): mixed {
        return $this->model->where($column, $value)->first();
    }

    /**
     * deleteMutipleByColumnValue
     *
     * @param $column
     * @param array $values
     *
     * @return mixed
     */
    public function deleteMultipleByColumnValue($column, array $values): mixed {
        return $this->model->whereIn($column, $values)->delete();
    }

    /**
     * getSpecificByIdOrSlug
     *
     * @param $id
     *
     * @return mixed
     */
    public function getSpecificByIdOrSlug($id): mixed {
        $field = is_numeric($id) ? "id" : "slug";
        return $this->model->where($field, $id)->firstOrFail();
    }

    /**
     * createNewSlug
     *
     * @param $name
     *
     * @return string
     */
    public function createNewSlug($name): string {
        $slug = Str::slug($name, '-');
        $data = $this->model->where('slug', 'like', "$slug%")->selectRaw(DB::raw('slug,max(cast(replace(slug,"' . $slug . '-","") as unsigned)) as slug_no'))->groupBy('slug')->orderByDesc('slug_no')->first();
        if (!empty($data) && $data['slug_no'] > 0) {
            return $slug . '-' . ($data['slug_no'] + 1);
        }
        else {
            if (!empty($data) && $data['slug'] == $slug) {
                return $slug . '-1';
            }
            else {
                return $slug;
            }
        }
    }

}
