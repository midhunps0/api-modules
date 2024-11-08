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

trait DefaultApiCrudHelper{
    protected $modelClass;
    protected $query = null;
    protected $resultsName = 'results';
    protected $idKey = 'id'; // id column in the db table to identify the items
    protected $selects = '*'; // query select keys/calcs
    protected $selIdsKey = 'id'; // selected items id key
    protected $searchesMap = []; // associative array mapping search query params to db columns
    protected $sortsMap = []; // associative array mapping sort query params to db columns
    protected $orderBy = ['created_at', 'desc'];
    // protected $uniqueSortKey = null; // unique key to sort items. it can be a calculated field to ensure unique values
    // protected $sqlOnlyFullGroupBy = true;
    protected $defaultSearchColumn = 'name';
    protected $defaultSearchMode = 'startswith'; // contains, startswith, endswith
    protected $processRelationsManually = false;
    protected $processMediaManually = false;

    public $downloadFileName = 'results';

    public function index(
        $data
    ): array {
        $name = ucfirst(Str::plural(Str::lower($this->getModelShortName())));
        if (!$this->authoriseIndex()) {
            throw new AuthorizationException('The user is not authorised to view '.$name.'.');
        }
        $inputParams = $data;
        $data = $this->processBeforeIndex($data);

        $itemsCount = null;
        $page = null;
        $paginate = 0;
        if (isset($data['paginate'])) {
            $paginate = intval($data['paginate']);
            $itemsCount = $data['items_per_page'] ?? 15;
            $page = $data['page'] ?? 1;
            unset($data['paginate']);
            unset($data['paginitems_per_pageate']);
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

        $preparedSearchParams = $this->prepareSearchParamsForQuery($data, $inputParams);

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

        $this->processAfterIndex($inputParams, $results);
        $returnData = $results->toArray();

        return [
            $this->resultsName => $returnData,
            'query_params' => $inputParams,
        ];

    }

    private function prepareSearchParamsForQuery(array $searchData, $inputParams): array
    {
        $preparedSearches = [];
        $searchFields = $this->prepareSarchFields($inputParams);
        foreach ($searchData as $field => $value) {
            if (isset($searchFields[$field])) {
                $this->setSearchUnitForField($preparedSearches, $field, $value, $searchFields[$field]);
            }
        }
        return $preparedSearches;
    }

    private function setSearchUnitForField($preparedData, $field, $value, OperationEnum $operation): void
    {
        if ($field != null) {
            array_push($preparedData, new SearchUnit($field, $operation, $value));
        }
    }

    private function prepareSarchFields($data): array
    {
        return $data;
    }

    public function show($id)
    {
        if (count($this->showWith())) {
            $item = $this->modelClass::with($this->showWith())
                ->where($this->idKey, $id)->get()->first();
        } else {
            $item = $this->modelClass::where($this->idKey, $id)->get()->first();
        }
        $name = ucfirst(Str::lower($this->getModelShortName()));
        if (!$this->authoriseShow($item)) {
            throw new AuthorizationException('The user is not authorised to view '.$name.'.');
        }
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
        string $selectedIds
    ): array {
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
        array $filters,
    ): array {
        $queryData = $this->getQueryAndParams(
            $searches,
            $sorts,
            $filters
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

    public function store(array $data): Model
    {
        $data = $this->processBeforeStore($data);
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
            $this->processAfterStore($instance);
            return $instance;
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->__toString());
            throw new Exception($e->__toString());
        }
    }

    public function update($id, array $data)
    {
        info('update inputs: ');
        info($data);
        $data = $this->processBeforeUpdate($data, $id);

        $instance = $this->modelClass::find($id);
        $oldInstance = $instance;
        $name = ucfirst(Str::lower($this->getModelShortName()));
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
            info('about to process media...');
            if (!$this->processMediaManually) {
                info('inside process auto');
                info($mediaFields);
                foreach ($mediaFields as $fieldName => $val) {
                    info('field: '.$fieldName);
                    info('value: ');
                    info($val);
                    $instance->syncMedia($fieldName, $val);
                }
            } else {
                $this->processMediaAfterUpdate($instance);
            }

//             foreach ($mediaFields as $fieldName => $val) {
//                 $instance->addMediaFromEAInput($fieldName, $val);
//             }
            DB::commit();
            $this->processAfterUpdate($oldInstance, $instance);
        } catch (\Exception $e) {
            info('rolled back: '.$e->__toString());
            DB::rollBack();
        }
        $instance->refresh();
        return $instance;
    }

    public function getIndexValidationRules(): array
    {
        return $this->indexValidationRules ?? [];
    }

    public function getShowValidationRules($id = null): array
    {
        return $this->showValidationRules ?? [];
    }

    public function getStoreValidationRules(): array
    {
        return $this->storeValidationRules ?? [];
    }

    public function getUpdateValidationRules($id = null): array
    {
        return $this->updateValidationRules ?? [];
    }

    public function getDeleteValidationRules($id = null): array
    {
        return $this->deleteValidationRules ?? [];
    }

    private function processBeforeIndex(array $data): array
    {
        return $data;
    }

    private function processBeforeShow(array $data): array
    {
        return $data;
    }

    private function processBeforeStore(array $data): array
    {
        return $data;
    }

    private function processBeforeUpdate(array $data, $id = null): array
    {
        return $data;
    }

    private function processBeforeDelete($id): void
    {}

    private function processAfterIndex($data, $results)
    {
        return [
            'data' => $data,
            'results' => $results
        ];
    }

    private function processAfterStore($instance): void
    {}

    private function processAfterUpdate($oldInstance, $instance): void
    {}

    private function processAfterDelete($id): void
    {}

    public function destroy($id)
    {
        $item = $this->modelClass::find($id);

        $modelName = ucfirst(Str::lower($this->getModelShortName()));
        if ($item == null) {
            throw new ModelNotFoundException("The $modelName with id $id does not exist.");
        }
        if (!$this->authoriseDestroy($item)) {
            throw new AuthorizationException('Unable to delete the '.$modelName.'. The user is not authorised for this action.');
        }
        $this->processBeforeDelete($id);
        $success = $item->delete();
        $this->processAfterDelete($id);
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

    private function getSearchOperator($op, $val)
    {
        $ops = [
            OperationEnum::IS => 'like',
            OperationEnum::CONTAINS => 'like',
            OperationEnum::STARTS_WITH => 'like',
            OperationEnum::ENDS_WITH => 'like',
            OperationEnum::GREATER_THAN => '>',
            OperationEnum::LESS_THAN => '<',
            OperationEnum::GREATER_THAN_OR_EQUAL_TO => '>=',
            OperationEnum::LESS_THAN_OR_EQUAL_TO => '<=',
            OperationEnum::EQUAL_TO => '=',
            OperationEnum::NO_EQUAL_TO => '<>',
        ];
        $v = $val;
        switch($op) {
            case OperationEnum::CONTAINS:
                $v = '%'.$val.'%';
                break;
            case OperationEnum::STARTS_WITH:
                $v = $val.'%';
                break;
            case OperationEnum::ENDS_WITH:
                $v = '%'.$val;
                break;
        }

        return [
            'op' => $ops[$op],
            'val' => $v
        ];
    }

    private function setSearchParams($query, array $searches, $searchesMap)
    {
        foreach ($searches as $search) {
            if(!$search instanceof SearchUnit) {
                throw new InvalidArgumentException('Inside setSearchParams: $searches shall be an array of SearchUnits.');
            }
            $field = $searchesMap[$search->field] ?? $search->field;
            $op = $this->getSearchOperator($search->operation, $search->value);
            if($this->isRelation(explode('.', $field)[0])) {
                $this->applyRelationSearch($query, $field, $this->relations()[$field]['search_column'], $op['op'], $op['val']);
            } else {
                $query->where($field, $op['op'], $op['val']);
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

    public function suggestList($search = null)
    {
        if (isset($search)) {
            switch($this->defaultSearchMode) {
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
            return $this->modelClass::where($this->defaultSearchColumn, 'like', $search)->get();
        } else {
            return $this->modelClass::all();
        }
    }

    public function getModelShortName() {
        $a = explode('\\', $this->modelClass);
        return $a[count($a) - 1];
    }

    public function prepareForIndexValidation(array $data): array
    {
        return $data;
    }

    public function prepareForShowValidation(array $data): array
    {
        return $data;
    }

    public function prepareForStoreValidation(array $data): array
    {
        return $data;
    }

    public function prepareForUpdateValidation(array $data): array
    {
        return $data;
    }

    public function prepareForDeleteValidation(array $data): array
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

    public function authoriseIndex(): bool
    {
        return true;
    }

    public function authoriseShow($item): bool
    {
        return true;
    }

    public function authoriseCreate(): bool
    {
        return true;
    }

    public function authoriseStore(): bool
    {
        return true;
    }

    public function authoriseEdit($id): bool
    {
        return true;
    }

    public function authoriseUpdate($item): bool
    {
        return true;
    }

    public function authoriseDestroy($item): bool
    {
        return true;
    }
}
?>
