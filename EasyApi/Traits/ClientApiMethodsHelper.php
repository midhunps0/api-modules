<?php
/***
 *  This trait is to be used in the controller for quick setup.
 */
namespace Modules\Ynotz\EasyApi\Traits;

use Illuminate\Http\Request;
use Modules\Ynotz\EasyApi\Contracts\ApiCrudHelperContract;

trait ClientApiMethodsHelper {
    use ApiMethods;
    private $itemName = null;
    private $itemsCount = 10;
    private $resultsName = 'results';
    private ApiCrudHelperContract $connectorService;

    public function index(Request $request, $clientId)
    {
        return $this->indexMethod($request, $clientId);
    }

    public function show(Request $request, $clientId, $id)
    {
        return $this->showMethod($request, $id, $clientId);
    }

    public function selectIds($clientId)
    {
        $this->selectIdsMethod($clientId);
    }

    public function download($clientId)
    {
        return $this->downloadMethod($clientId);
    }

    public function store(Request $request, $clientId)
    {
        return $this->storeMethod($request);
    }

    public function update(Request $request,$clientId, $id)
    {
        return $this->updateMethod($request, $id, $clientId);
    }

    public function destroy(Request $request,$clientId, $id)
    {
        return $this->destroyMethod($request, $id, $clientId);
    }

    public function suggestlist($clientId)
    {
        return $this->suggestlist($clientId);
    }
}
?>
