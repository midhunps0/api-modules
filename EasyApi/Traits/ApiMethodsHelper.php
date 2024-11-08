<?php
/***
 *  This trait is to be used in the controller for quick setup.
 */
namespace Modules\Ynotz\EasyApi\Traits;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Modules\Ynotz\EasyApi\Contracts\ApiCrudHelperContract;
use Modules\Ynotz\EasyApi\ImportExports\DefaultArrayExports;
use Throwable;

trait ApiMethodsHelper {
    private $itemName = null;
    private $itemsCount = 10;
    private $resultsName = 'results';
    private ApiCrudHelperContract $connectorService;

    public function index(Request $request)
    {
        $rules = $this->connectorService->getIndexValidationRules();
        if (count($rules) > 0) {
            $validator = Validator::make(
                $this->connectorService->prepareForIndexValidation($request->all()),
                $rules
            );

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'error' => $validator->errors(),
                        'message' => $validator->errors()
                    ],
                    status: 422
                );
            }
        }

        try {
            $result = $this->connectorService->index(
                $request->all()
            );
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }

    }

    public function show(Request $request, $id)
    {
        $rules = $this->connectorService->getShowValidationRules();
        if (count($rules) > 0) {
            $validator = Validator::make(
                $this->connectorService->prepareForShowValidation($request->all()),
                $rules
            );

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'error' => $validator->errors(),
                        'message' => $validator->errors()
                    ],
                    status: 422
                );
            }
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $this->connectorService->show($id)
            ]);
        } catch (\Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function selectIds()
    {
        try {
            $ids = $this->connectorService->getIdsForParams(
                $this->request->input('search', []),
                $this->request->input('sort', []),
                $this->request->input('filter', []),
                $this->request->input('adv_search', [])
            );
            return response()->json([
                'success' => true,
                'ids' => $ids
            ]);
        } catch (\Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function download()
    {
        try {
            $results = $this->connectorService->indexDownload(
                $this->request->input('search', []),
                $this->request->input('sort', []),
                $this->request->input('filter', []),
                $this->request->input('adv_search', []),
                $this->request->input('selected_ids', '')
            );

            $respone = Excel::download(
                new DefaultArrayExports(
                    $results,
                    $this->connectorService->getDownloadCols(),
                    $this->connectorService->getDownloadColTitles()
                ),
                $this->connectorService->downloadFileName.'.'
                    .$this->request->input('format', 'xlsx')
            );
        } catch (\Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }

        ob_end_clean();

        return $respone;
    }

    public function store(Request $request)
    {
        try {
            $rules = $this->connectorService->getStoreValidationRules();
            info($rules);
            if (count($rules) > 0) {
                $validator = Validator::make(
                    $this->connectorService->prepareForStoreValidation($request->all()),
                    $rules
                );

                if ($validator->fails()) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => $validator->errors()
                        ],
                        status: 422
                    );
                }
                $instance = $this->connectorService->store(
                    $validator->validated()
                );
            } else {
                if (config('easyapi.enforce_validation')) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => 'Validation rules not defined'
                        ],
                        status: 401
                    );
                }
                $instance = $this->connectorService->store($request->all());
            }

            return response()->json([
                'success' => true,
                'data' => $instance,
                'message' => 'New '.$this->getItemName().' added.'
            ]);
        } catch (\Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $rules = $this->connectorService->getUpdateValidationRules($id);
            info('rules');
            info($rules);
            if (count($rules) > 0) {
                $validator = Validator::make($this->connectorService->prepareForUpdateValidation($request->all()), $rules);

                if ($validator->fails()) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => $validator->errors()
                        ],
                        status: 422
                    );
                }
                $result = $this->connectorService->update($id, $validator->validated());
            } else {
                if (config('easyapi.enforce_validation')) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => 'Validation rules not defined'
                        ],
                        status: 401
                    );
                } else {
                    $result = $this->connectorService->update($id, $request->all());
                }
            }

            return response()->json([
                'success' => true,
                'instance' => $result,
                'message' => 'New '.$this->getItemName().' updated.'
            ]);
        } catch (\Throwable $e) {
            if (env('APP_DEBUG')) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $e->__toString(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy(Request $request, $id)
    {
        $rules = $this->connectorService->getDeleteValidationRules($id);

        if (count($rules) > 0) {
            $validator = Validator::make($this->connectorService->prepareForDeleteValidation($request->all()), $rules);

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'errors' => $validator->errors()
                    ],
                    status: 422
                );
            }
        }
        try {
            // $this->connectorService->processBeforeDelete($id);
            $result = $this->connectorService->destroy($id);
            // $this->connectorService->processAfterDelete($id);
            if ($result) {
                return response()->json([
                    'success' => $result,
                    'message' => 'Item deleted'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Unexpected error',
                    'message' => 'Failed to delete '.$this->connectorService->getModelShortname(),
                ]);
            }
        } catch (\Throwable $e) {
            $debug = env('APP_DEBUG');
            if ($debug) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $debug ? $e->__toString() : $e->getMessage()
            ]);
        }
    }

    public function suggestlist()
    {
        try {
            $search = $this->request->input('search', null);

            return response()->json([
                'success' => true,
                'data' => $this->connectorService->suggestlist($search)
            ]);
        } catch (\Throwable $e) {
            $debug = env('APP_DEBUG');
            if ($debug) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $debug ? $e->__toString() : $e->getMessage()
            ]);
        }
    }

    private function getItemName()
    {
        return $this->itemName ?? $this->generateItemName();
    }

    private function generateItemName()
    {
        $t = explode('\\', $this->connectorService->getModelShortName());
        return Str::snake(array_pop($t));
    }
}
?>
