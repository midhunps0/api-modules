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
    use ApiMethods;
    private $itemName = null;
    private $itemsCount = 10;
    private $resultsName = 'results';
    private ApiCrudHelperContract $connectorService;

    public function serviceMethodResult($method, $data = [])
    {
        return $this->serviceMethod($method, $data);
    }

    public function index(Request $request)
    {
        return $this->indexMethod($request);
    }

    public function show(Request $request, $id)
    {
        return $this->showMethod($request, $id);
    }

    public function selectIds()
    {
        $this->selectIdsMethod();
    }

    public function download()
    {
        return $this->downloadMethod();
    }

    public function store(Request $request)
    {
        return $this->storeMethod($request);
    }

    public function update(Request $request, $id)
    {
        return $this->updateMethod($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        return $this->destroyMethod($request, $id);
    }

    public function suggestlist()
    {
        return $this->suggestlist();
    }
}
?>
