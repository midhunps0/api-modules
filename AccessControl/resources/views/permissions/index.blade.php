<x-easyadmin::utils.panelbase
    :x_ajax="$x_ajax"
    title="Permissions"
    indexUrl="{{route('permissions.index')}}"
    {{-- downloadUrl="{{route('permissions.download')}}" --}}
    selectIdsUrl="{{route('permissions.selectIds')}}"
    :items_count="$items_count"
    :items_ids="$items_ids"
    :total_results="$total_results"
    :current_page="$current_page"
    :results="$permissions"
    results_name="permissions"
    unique_str="permissionsx"
    :results_json="$results_json"
    :paginator="$paginator"
    total_disp_cols="15"
    adv_fields=""
    id="permissions_index">
    <x-slot:inputFields>
        @if (isset($aggregates))
            <input type="hidden" value="{{$aggregates}}" id="aggregates">
        @endif
        <input type="hidden" value="{{$results_json}}" id="results_json">
        <input type="hidden" value="{{$items_ids}}" id="itemIds">
    </x-slot>
    <x-slot:thead>
        <th>
            <div class="flex flex-row items-center w-32">
                <x-easyadmin::utils.spotsort name="name" val="{{ $sort['name'] ?? 'none' }}" />
                <div class="relative flex-grow ml-2">
                    Name
                    <x-easyadmin::utils.spotsearch textval="{{ $params['name'] ?? '' }}" textname="name" label="Search permissin" />
                </div>
            </div>
        </th>
        <th>
            <div class="flex flex-row items-center w-36">
                Action
            </div>
        </th>
    </x-slot>
    <x-slot:rows>
        <template x-for="result in results">
            <tr>
                <td><input type="checkbox" :value="result.id" x-model="selectedIds"
                        class="checkbox checkbox-primary checkbox-xs"></td>
                <td class="sticky !left-6">
                    <span x-text="result.name"></span>
                </td>
                <td class="sticky !left-36 z-20">
                    <div class="flex flex-row justify-start space-x-4 items-center">
                        <a href=""
                            @click.prevent.stop="$dispatch('linkaction', {link: '{{route('permissions.edit', 'x_x')}}'.replace('x_x', result.id), route: 'permissions.edit'});"
                            class="btn btn-ghost btn-xs text-warning capitalize">
                            <x-easyadmin::display.icon icon="icons.edit" height="h-4" width="w-4"/>
                        </a>
                        <button @click.prevent.stop="$dispatch('deleteitem', {itemId: result.id});" class="btn btn-ghost btn-xs text-error capitalize"><x-easyadmin::display.icon icon="icons.delete" height="h-4" width="w-4"/></button>
                    </div>
                </td>
            </tr>
        </template>
    </x-slot>
</x-utils.panelbase>
