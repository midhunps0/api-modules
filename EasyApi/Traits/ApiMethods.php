<?php
/***
 *  This trait is to be used in the controller for quick setup.
 */
namespace Modules\Ynotz\EasyApi\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ynotz\EasyApi\ImportExports\DefaultArrayExports;
use Illuminate\Support\Str;

trait ApiMethods{
    public function indexMethod(Request $request, $clientId = null)
    {
        $data = $request->all();
        if (isset($clientId)) {
            $data['client_id'] = $clientId;
        }
        $rules = $this->connectorService->getIndexValidationRules($clientId);
        if (count($rules) > 0) {
            $validator = Validator::make(
                $this->connectorService->prepareForIndexValidation($data, $clientId),
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
                $data
            );
            return response()->json([
                'success' => true,
                'data' => $result
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

    public function showMethod(Request $request, $id, $clientId = null)
    {
        $data = $request->all();
        if (isset($clientId)) {
            $data['client_id'] = $clientId;
        }
        $rules = $this->connectorService->getShowValidationRules($clientId);
        if (count($rules) > 0) {
            $validator = Validator::make(
                $this->connectorService->prepareForShowValidation($data, $clientId),
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
                'data' => $this->connectorService->show($id, $clientId)
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

    public function selectIdsMethod($clientId = null)
    {
        try {
            $ids = $this->connectorService->getIdsForParams(
                $this->request->input('search', []),
                $this->request->input('sort', []),
                $clientId
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

    public function downloadMethod($clientId = null)
    {
        try {
            $results = $this->connectorService->indexDownload(
                $this->request->input('search', []),
                $this->request->input('sort', []),
                $this->request->input('selected_ids', ''),
                $clientId
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

    public function storeMethod(Request $request, $clientId = null)
    {
        try {
            $rules = $this->connectorService->getStoreValidationRules($clientId);

            if (count($rules) > 0) {
                $validator = Validator::make(
                    $this->connectorService->prepareForStoreValidation(
                        $request->all(),
                        $clientId
                    ),
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
                    $validator->validated(),
                    $clientId
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
                $instance = $this->connectorService->store($request->all(), $clientId);
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

    public function updateMethod(Request $request, $id, $clientId = null)
    {
        try {
            $rules = $this->connectorService->getUpdateValidationRules($id, $clientId);

            if (count($rules) > 0) {
                $validator = Validator::make($this->connectorService->prepareForUpdateValidation($request->all(), $clientId), $rules);

                if ($validator->fails()) {
                    return response()->json(
                        [
                            'success' => false,
                            'errors' => $validator->errors()
                        ],
                        status: 422
                    );
                }
                $result = $this->connectorService->update($id, $validator->validated(), $clientId);
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
                    $result = $this->connectorService->update($id, $request->all(), $clientId);
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

    public function destroyMethod(Request $request, $id, $clientId = null)
    {
        $rules = $this->connectorService->getDeleteValidationRules($id, $clientId);

        if (count($rules) > 0) {
            $validator = Validator::make(
                $this->connectorService->prepareForDeleteValidation(
                    $request->all(),
                    $clientId
                ),
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
        }
        try {
            // $this->connectorService->processBeforeDelete($id);
            $result = $this->connectorService->destroy($id, $clientId);
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

    public function suggestlistMethod($clientId = null)
    {
        try {
            $search = $this->request->input('search', null);

            return response()->json([
                'success' => true,
                'data' => $this->connectorService->suggestlist($search, $clientId)
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
