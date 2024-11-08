<?php
namespace Modules\Ynotz\EasyApi\Contracts;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\CreatePageData;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\EditPageData;

interface ApiCrudHelperContract
{
    public function index(
        array $data
    ): array;
    public function show($id);
    public function store(array $data);
    public function update($id, array $data);
    public function destroy($id);
    public function getModelShortName();

    public function prepareForIndexValidation(array $data): array;
    public function prepareForShowValidation(array $data): array;
    public function prepareForStoreValidation(array $data): array;
    public function prepareForUpdateValidation(array $data): array;

    public function indexDownload(
        array $searches,
        array $sorts,
        string $selectedIds
    ): array;

    public function getIdsForParams(
        array $searches,
        array $sorts,
        array $filters,
    ): array;

    public function suggestlist($search = null);

    public function getDownloadCols(): array;

    public function getDownloadColTitles(): array;

    public function getIndexValidationRules(): array;
    public function getShowValidationRules($id = null): array;
    public function getStoreValidationRules(): array;
    public function getUpdateValidationRules($id = null): array;
    public function getDeleteValidationRules($id = null): array;

}
?>
