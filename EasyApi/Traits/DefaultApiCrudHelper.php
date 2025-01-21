<?php
namespace Modules\Ynotz\EasyApi\Traits;

use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Database\Query\Builder;
use Modules\Ynotz\EasyAdmin\InputUpdateResponse;
use Modules\Ynotz\EasyAdmin\Services\FormHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\CreatePageData;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\ShowPageData;
use Modules\Ynotz\EasyApi\DataObjects\OperationEnum;
use Modules\Ynotz\EasyApi\DataObjects\SearchUnit;
use Modules\Ynotz\EasyApi\DataObjects\SortUnit;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

trait DefaultApiCrudHelper{
    protected $modelClass;
    protected $query = null;
    protected $resultsName = 'results';
    protected $idKey = 'id'; // id column in the db table to identify the items
    protected $selects = '*'; // query select keys/calcs
    protected $selIdsKey = 'id'; // selected items id key
    protected $searchesMap = []; // associative array mapping search query params to db columns
    // protected $searchTypesSettings = []; // Associative array mentioning what operation (OperationEnum case) is to be applied on each search field. Default for all fields is OperatoinEnum::EQUAL_TO. This will be overridden by search_types[] input from front-end. item example: 'name' => OperationEnum::EQUAL_TO
    protected $sortsMap = []; // associative array mapping sort query params to db columns
    protected $orderBy = ['created_at', 'desc'];
    // protected $uniqueSortKey = null; // unique key to sort items. it can be a calculated field to ensure unique values
    // protected $sqlOnlyFullGroupBy = true;
    protected $suggestListSearchColumn = 'name';
    protected $suggestListSearchMode = 'startswith'; // contains, startswith, endswith
    protected $processRelationsManually = false;
    protected $processMediaManually = false;
    protected $clientIdFieldName = 'client_id';

    public $downloadFileName = 'results';

    public function index(
        $data
    ): array {
        $name = ucfirst(Str::plural(Str::lower($this->getModelShortName())));
        if (!$this->authoriseIndex()) {
            throw new AuthorizationException('The user is not authorised to view '.$name.'.');
        }
        $inputParams = $data;
        $data = $this->processBeforeIndex($data, $data[$this->clientIdFieldName] ?? null);

        $itemsCount = null;
        $page = null;
        $paginate = config('easyapi.paginate_by_default', 1);
        if (isset($data['paginate'])) {
            $paginate = intval($data['paginate']);
            $itemsCount = $data['items_per_page'] ?? 15;
            $page = $data['page'] ?? 1;
            unset($data['paginate']);
            unset($data['items_per_page']);
            unset($data['page']);
        }
        $selectedIds = null;
        if (isset($data['selected_ids'])) {
            $selectedIds = $data['selected_ids'];
            unset($data['selected_ids']);
        }
        $sortParams = [];
        if (isset($data['sorts'])) {
            $sortParams = $data['sorts'];
            unset($data['sorts']);
        }
        $searchTypes = [];
        if (isset($data['search_types'])) {
            $searchTypes = $data['search_types'];
            unset($data['search_types']);
        }
        $preparedSearchParams = $this->prepareSearchParamsForQuery($data, $searchTypes);

        $queryData = $this->getQueryAndParams(
            $preparedSearchParams,
            $sortParams,
            $selectedIds,
        );

        if($paginate == 1) {
            $results = $queryData->orderBy(
                $this->orderBy[0],
                $this->orderBy[1]
            )->paginate(
                $itemsCount,
                $this->selects,
                'page',
                $page
            );
        } else {
            $results = $queryData->orderBy(
                $this->orderBy[0],
                $this->orderBy[1]
            )->get();
        }

        $this->processAfterIndex($inputParams, $results, $data[$this->clientIdFieldName] ?? null);
        $returnData = $results->toArray();

        return [
            $this->resultsName => $returnData,
            'query_params' => $inputParams,
        ];
    }

    private function prepareSearchParamsForQuery(array $searchData, $searchTypes): array
    {
        $preparedSearches = [];
        $searchFieldTypes = $this->searchFieldsOperations($searchTypes);
        foreach ($searchData as $field => $value) {
                $op = $searchFieldTypes[$field] ?? OperationEnum::EQUAL_TO;
                array_push($preparedSearches, new SearchUnit($field, $op, $value));
        }
        return $preparedSearches;
    }

    // private function getSarchTypes(array $searchTypes): array
    // {
    //     return array_merge(
    //         $this->searchTypesSettings,
    //         array_map(
    //             function ($t) {
    //                 return OperationEnum::from($t);
    //             },
    //             $searchTypes
    //         )
    //     );
    // }

    public function show($id, $clientId = null)
    {
        $id = $this->processBeforeShow($id, $clientId);
        $query = $this->modelClass::with($this->showWith())
        ->where($this->idKey, $id);
        if (isset($clientId)) {
            $query->where($this->clientIdFieldName, $clientId);
        }
        if (count($this->showWith())) {
            $item = $query->get()->first();
        } else {
            $item = $query->get()->first();
        }
        $name = ucfirst(Str::lower($this->getModelShortName()));
        if (!$this->authoriseShow($item)) {
            throw new AuthorizationException('The user is not authorised to view '.$name.'.');
        }
        $this->processAfterShow($item, $clientId);
        return [
            'item' => $item
        ];
    }

    public function showWith(): array
    {
        return [];
    }

    private function getQuery()
    {
        return $this->query ?? $this->modelClass::query();
    }

    private function getItemIds($results) {
        $ids = $results->pluck($this->idKey)->toArray();
        return json_encode($ids);
    }

    public function indexDownload(
        array $searches,
        array $sorts,
        string $selectedIds,
        $clientId = null
    ): array {
        if (isset($clientId)) {
            $searches[$this->clientIdFieldName] = $clientId;
        }
        $queryData = $this->getQueryAndParams(
            $searches,
            $sorts,
            $selectedIds
        );
            // DB::statement("SET SQL_MODE=''");
        $results = $queryData['query']->select($this->selects)->get();
        // DB::statement("SET SQL_MODE='only_full_group_by'");

        return $this->formatIndexResults($results->toArray());
    }

    public function getIdsForParams(
        array $searches,
        array $sorts,
        $clientId = null
    ): array {
        if (isset($clientId)) {
            $searches[$this->clientIdFieldName] = $clientId;
        }
        $queryData = $this->getQueryAndParams(
            $searches,
            $sorts,
            []
        );

        // DB::statement("SET SQL_MODE=''");

        $results = $queryData['query']->select($this->selects)->get()->pluck($this->idKey)->unique()->toArray();
        // DB::statement("SET SQL_MODE='only_full_group_by'");
        return $results;
    }

    public function getQueryAndParams(
        array $searches,
        array $sorts,
    ) {
        $query = $this->getQuery();

        // if (count($relations = $this->relations()) > 0) {
        //     $query->with(array_keys($relations));
        // }

        $query = $this->setSearchParams($query, $searches, $this->searchesMap);
        $query = $this->setSortParams($query, $sorts, $this->sortsMap);

        if (isset($selectedIds) && strlen(trim($selectedIds)) > 0) {
            $ids = explode('|', $selectedIds);
            $query = $this->querySelectedIds($query, $this->selIdsKey, $ids);
        }

        return $query;
    }

    public function getItem(string $id)
    {
        return $this->modelClass::where($this->idKey ,$id)->get()->firsst();
    }

    public function store(array $data, $clientId = null): Model
    {
        if (isset($clientId)) {
            $data[$this->clientIdFieldName] = $clientId;
        }
        $data = $this->processBeforeStore($data, $clientId);
        $name = ucfirst(Str::lower($this->getModelShortName()));
        if (!$this->authoriseStore()) {
            throw new AuthorizationException('Unable to create the '.$name.'. The user is not authorised for this action.');
        }
        //filter out relationship fields from $data
        $ownFields = [];
        $relations = [];
        $mediaFields = [];

        foreach ($data as $key => $value) {
            if ($this->isRelation($key)) {
                $relations[$key] = $value;
            } elseif ($this->isMideaField($key)) {
                $mediaFields[$key] = $value;
            } else {
                $ownFields[$key] = $value;
            }
        }

        DB::beginTransaction();
        try {
            $instance = $this->modelClass::create($ownFields);

            //attach relationship instances as per the relation
            if (!$this->processRelationsManually) {
                foreach ($relations as $rel => $val) {
                    if (isset($this->relations()[$rel]['store_fn'])) {
                        ($this->relations()[$rel]['store_fn'])($instance, $val, $data);
                    } else {
                        $type = $this->getRelationType($rel);
                        switch ($type) {
                            case 'BelongsTo':
                                $instance->$rel()->associate($val);
                                $instance->save();
                                break;
                            case 'BelongsToMany':
                                $instance->$rel()->attach($val);
                                break;
                            case 'HasOne':
                                $cl = $instance->$rel()->getRelated();
                                $fkey = $instance->$rel()->getForeignKeyName();
                                $lkey = $instance->$rel()->getLocalKeyName();
                                $darray = array_merge([$fkey => $instance->$lkey], $val);

                                $cl::create($darray);
                                break;
                            case 'HasMany':
                                $instance->$rel()->delete();
                                $t = array();
                                foreach ($val as $v) {
                                    if (is_array($v)) {
                                        $t[] = $instance->$rel()->create($v);
                                    }
                                }
                                $instance->$rel()->saveMany($t);
                        }
                    }
                }
            } else {
                $this->processRelationsAfterStore($instance);
            }
            if (!$this->processMediaManually) {
                foreach ($mediaFields as $fieldName => $val) {
                    $instance->addMediaFromEAInput($fieldName, $val);
                }
            } else {
                $this->processMediaAfterStore($instance);
            }
            DB::commit();
            $this->processAfterStore($instance, $clientId);
            return $instance;
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->__toString());
            throw new Exception("Unexpected error while updating $name. Check your inputs and validation settings. ". $e->__toString());
        }
    }

    public function update($id, array $data, $clientId = null)
    {
        if (isset($clientId)) {
            $data[$this->clientIdFieldName] = $clientId;
        }
        $data = $this->processBeforeUpdate($data, $id, $clientId);

        $instance = $this->modelClass::find($id);
        $name = ucfirst(Str::lower($this->getModelShortName()));
        if ($instance == null) {
            throw new ResourceNotFoundException("Couldn't find the $name to update.");
        }
        $oldInstance = $instance;
        if (!$this->authoriseUpdate($instance)) {
            throw new AuthorizationException('Unable to update the '.$name.'. The user is not authorised for this action.');
        }
        $ownFields = [];
        $relations = [];
        $mediaFields = [];
        foreach ($data as $key => $value) {
            if ($this->isRelation($key)) {
                $relations[$key] = $value;
            } elseif ($this->isMideaField($key)) {
                $mediaFields[$key] = $value;
            } else {
                $ownFields[$key] = $value;
            }
        }

        DB::beginTransaction();
        try {
            $instance->update($ownFields);
            if (!$this->processRelationsManually) {
            //attach relationship instances as per the relation
                foreach ($relations as $rel => $val) {
                    if (isset($this->relations()[$rel]['update_fn'])) {
                        ($this->relations()[$rel]['update_fn'])($instance, $val, $data);
                    } else {
                        $type = $this->getRelationType($rel);
                        switch ($type) {
                            case 'BelongsTo':
                                $instance->$rel()->associate($val);
                                $instance->save();
                                break;
                            case 'BelongsToMany':
                                $instance->$rel()->sync($val);
                                break;
                            case 'HasOne':
                                $relInst = $instance->$rel;
                                $relInst->update($val);
                                break;
                            case 'HasMany':
                                $instance->$rel()->delete();
                                $t = array();
                                foreach ($val as $v) {
                                    if (is_array($v)) {
                                        $t[] = $instance->$rel()->create($v);
                                    }
                                }
                                $instance->$rel()->saveMany($t);
                                break;
                        }
                    }
                }
            } else {
                $this->processRelationsAfterUpdate($instance);
            }
            if (!$this->processMediaManually) {
                foreach ($mediaFields as $fieldName => $val) {
                    $instance->syncMedia($fieldName, $val);
                }
            } else {
                $this->processMediaAfterUpdate($instance);
            }

//             foreach ($mediaFields as $fieldName => $val) {
//                 $instance->addMediaFromEAInput($fieldName, $val);
//             }
            DB::commit();
            $this->processAfterUpdate($oldInstance, $instance, $clientId);
        } catch (\Exception $e) {
            info('rolled back: '.$e->__toString());
            DB::rollBack();
            throw new Exception("Unexpected error while updating $name. Check your inputs and validation settings. ". $e->__toString());
        }
        $instance->refresh();
        return $instance;
    }

    public function getIndexValidationRules($clientId = null): array
    {
        return $this->indexValidationRules ?? [];
    }

    public function getShowValidationRules($id = null, $clientId = null): array
    {
        return $this->showValidationRules ?? [];
    }

    public function getStoreValidationRules($clientId = null): array
    {
        return $this->storeValidationRules ?? [];
    }

    public function getUpdateValidationRules($id = null, $clientId = null): array
    {
        return $this->updateValidationRules ?? [];
    }

    public function getDeleteValidationRules($id = null, $clientId = null): array
    {
        return $this->deleteValidationRules ?? [];
    }

    private function processBeforeIndex(array $data, $clientId = null): array
    {
        return $data;
    }

    private function processBeforeShow($id, $clientId = null)
    {
        return $id;
    }

    private function processBeforeStore(array $data, $clientId = null): array
    {
        return $data;
    }

    private function processBeforeUpdate(array $data, $id = null, $clientId = null): array
    {
        return $data;
    }

    private function processBeforeDelete($id, $clientId = null): void
    {}

    private function processAfterIndex($data, $results, $clientId = null)
    {
        return [
            'data' => $data,
            'results' => $results
        ];
    }

    private function processAfterStore($instance, $clientId = null): void
    {}

    private function processAfterShow($instance, $clientId = null): void
    {}

    private function processAfterUpdate($oldInstance, $instance, $clientId = null): void
    {}

    private function processAfterDelete($id, $clientId = null): void
    {}

    public function destroy($id, $clientId = null)
    {
        $item = $this->modelClass::find($id);

        $modelName = ucfirst(Str::lower($this->getModelShortName()));
        if ($item == null) {
            throw new ModelNotFoundException("The $modelName with id $id does not exist.");
        }
        if (!$this->authoriseDestroy($item)) {
            throw new AuthorizationException('Unable to delete the '.$modelName.'. The user is not authorised for this action.');
        }
        $this->processBeforeDelete($id, $clientId);
        $success = $item->delete();
        $this->processAfterDelete($id, $clientId);
        return $success;
    }

    private function querySelectedIds(Builder $query, string $idKey, array $ids): Builder
    {
        $query->whereIn($idKey, $ids);
        return $query;
    }

    private function accessCheck(Model $item): bool
    {
        return true;
    }

    private function setSearchParams($query, array $searches, $searchesMap)
    {
        foreach ($searches as $search) {
            if(!$search instanceof SearchUnit) {
                throw new InvalidArgumentException('Inside setSearchParams: $searches shall be an array of SearchUnits.');
            }
            $field = $searchesMap[$search->field] ?? $search->field;
            $operator = $search->operation->operator();
            $formattedValue = $search->operation->formatSearchValue($search->value);
            if($this->isRelation(explode('.', $field)[0])) {
                $this->applyRelationSearch($query, $field, $operator, $formattedValue);
            } else {
                $query->where($field, $operator, $formattedValue);
            }
        }
        return $query;
    }

    private function setSortParams($query, array $sorts, array $sortsMap)
    {
        foreach ($sorts as $sort) {
            $data = explode('::', $sort);
            if(count($data) != 2) {
                throw new InvalidFormatException("Inside setSortParams: argument \$sorts shall be an array of strings of pattern 'field::direction'.");
            }
            $key = $sortsMap[$data[0]] ?? $data[0];
            if (isset($usortkey) && isset($map[$data[0]])) {
                $type = $key['type'];
                $kname = $key['name'];
                switch ($type) {
                    case 'string';
                        $query->orderByRaw('CONCAT('.$kname.',\'::\','.$usortkey.') '.$data[1]);
                        break;
                    case 'integer';
                        $query->orderByRaw('CONCAT(LPAD(ROUND('.$kname.',0),20,\'00\'),\'::\','.$usortkey.') '.$data[1]);
                        break;
                    case 'float';
                        $query->orderByRaw('CONCAT( LPAD(ROUND('.$kname.',0) * 100,20,\'00\') ,\'::\','.$usortkey.') '.$data[1]);
                        break;
                    default:
                        $query->orderByRaw('CONCAT('.$kname.'\'::\','.$usortkey.') '.$data[1]);
                        break;
                }
            } else {
                $query->orderBy($data[0], $data[1]);
            }

        }
        return $query;
    }
    private function applyRelationSearch(Builder $query, $fieldName, $op, $val): void
    {
        $key = null;
        $searchFn = null;
        if (strlen(str_replace('.', '', $fieldName)) != strlen($fieldName)) {
            $arr = explode('.', $fieldName);
            $relName = $arr[0];
            $key = $arr[1];
            $searchFn = $this->relations()[$relName][$key]['search_fn'] ?? null;
        } else {
            $relName = $fieldName;
            $key = $this->relations()[$relName]['search_column'];
            $searchFn = $this->relations()[$relName]['search_fn'] ?? null;
        }
        // If isset(search_fn): execute it
        if (isset($searchFn)) {
            $searchFn($query, $op, $val);
        } else {
            // Get relation type
            $type = $this->getRelationType($relName);
            // dd($type);
            switch ($type) {
                case 'BelongsTo':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'HasOne':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'HasMany':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'BelongsToMany':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                default:
                    break;
            }
        }
    }
/*
    private function applyRelationSearch(Builder $query, $relName, $key, $op, $val): void
    {
        // If isset(search_fn): execute it
        if (isset($this->relations()[$relName]['search_fn'])) {
            $this->relations()[$relName]['search_fn']($query, $op, $val);
        } else {
            // Get relation type
            $type = $this->getRelationType($relName);
            // dd($type);
            switch ($type) {
                case 'BelongsTo':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'HasOne':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'HasMany':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                case 'BelongsToMany':
                    $query->whereHas($relName, function ($q) use ($key, $op, $val) {
                        $q->where($key, $op, $val);
                    });
                    break;
                default:
                    break;
            }
        }
    }
*/
    private function getRelationType(string $relation): string
    {
        $obj = new $this->modelClass;
        $type = get_class($obj->{$relation}());
        $ar = explode('\\', $type);
        return $ar[count($ar) - 1];
    }

    private function getRelatedModelClass(string $relation): string
    {
        $cl = $this->modelClass;
        $obj = new $cl;

        $r = $obj->$relation();

        return $r->getRelated();
    }

    private function isRelation($key): bool
    {
        return in_array($key, array_keys($this->relations()));
    }

    private function isMideaField($key): bool
    {
        return in_array($key, $this->getMediaFields());
    }

    private function getMediaFields(): array
    {
        return [];
    }

    protected function relations(): array
    {
        return [
            // 'relation_name' => [
            //     'type' => '',
            //     'field' => '',
            //     'search_fn' => function ($query, $op, $val) {}, // function to be executed on search
            //     'search_scope' => '', //optional: required only for combined fields search
            //     'sort_scope' => '', //optional: required only for combined fields sort
            //     'models' => '' //optional: required only for morph types of relations
            // ],
        ];
    }

    // protected function extraConditions(Builder $query): void {}
    protected function applyGroupings(Builder $q): void {}

    protected function formatIndexResults(array $results): array
    {
        return $results;
    }

    // protected function getSelectedIdsUrl(): string
    // {
    //     return route(Str::lower(Str::plural($this->getModelShortName())).'.selectIds');
    // }

    // protected function getDownloadUrl(): string
    // {
    //     return route(Str::lower(Str::plural($this->getModelShortName())).'.download');
    // }

    // protected function getCreateRoute(): string
    // {
    //     return Str::lower(Str::plural($this->getModelShortName())).'.create';
    // }

    // protected function getViewRoute(): string
    // {
    //     return Str::lower(Str::plural($this->getModelShortName())).'.view';
    // }

    // protected function getEditRoute(): string
    // {
    //     return Str::lower(Str::plural($this->getModelShortName())).'.edit';
    // }

    // protected function getDestroyRoute(): string
    // {
    //     return Str::lower(Str::plural($this->getModelShortName())).'.destroy';
    // }

    protected function getIndexId(): string
    {
        return Str::lower(Str::plural($this->getModelShortName())).'_index';
    }

    public function getDownloadCols(): array
    {
        return [];
    }

    public function getDownloadColTitles(): array
    {
        return [];
    }

    public function suggestList($search = null, $clientId = null)
    {
        if (isset($search)) {
            switch($this->suggestListSearchMode) {
                case 'contains':
                    $search = '%'.$search.'%';
                    break;
                case 'startswith':
                    $search = $search.'%';
                    break;
                case 'endswith':
                    $search = '%'.$search;
                    break;
            }
            return $this->modelClass::where($this->suggestListSearchColumn, 'like', $search)->get();
        } else {
            return $this->modelClass::all();
        }
    }

    public function getModelShortName() {
        $a = explode('\\', $this->modelClass);
        return $a[count($a) - 1];
    }

    public function prepareForIndexValidation(array $data, $clientId = null): array
    {
        return $data;
    }

    public function prepareForShowValidation(array $data, $clientId = null): array
    {
        return $data;
    }

    public function prepareForStoreValidation(array $data, $clientId = null): array
    {
        return $data;
    }

    public function prepareForUpdateValidation(array $data, $clientId = null): array
    {
        return $data;
    }

    public function prepareForDeleteValidation(array $data, $clientId = null): array
    {
        return $data;
    }

    public function processRelationsAfterStore(Model $instance)
    {}

    public function processMediaAfterStore(Model $instance)
    {}

    public function processRelationsAfterUpdate(Model $instance)
    {}

    public function processMediaAfterUpdate(Model $instance)
    {}

    public function authoriseIndex($clientId = null): bool
    {
        return true;
    }

    public function authoriseShow($item, $clientId = null): bool
    {
        return true;
    }

    public function authoriseStore($clientId = null): bool
    {
        return true;
    }

    public function authoriseUpdate($item, $clientId = null): bool
    {
        return true;
    }

    public function authoriseDestroy($item, $clientId = null): bool
    {
        return true;
    }
}
?>
