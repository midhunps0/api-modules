<x-easyadmin::utils.panelbase
    :x_ajax="$x_ajax"
    title="Roles"
    indexUrl="{{route('roles.index')}}"
    {{-- downloadUrl="{{route('roles.download')}}" --}}
    selectIdsUrl="{{route('roles.selectIds')}}"
    :items_count="$items_count"
    :items_ids="$items_ids"
    selected_ids="{{$selected_ids}}"
    :total_results="$total_results"
    :current_page="$current_page"
    :results="$roles"
    results_name="roles"
    unique_str="rolesx"
    :results_json="$results_json"
    :paginator="$paginator"
    total_cols="3"
    adv_fields=""
    id="roles_index"
    :selectionEnabled="$selectionEnabled"
    :row_counts="[10, 20, 30]">
    <x-slot:inputFields>
        <input type="hidden" value="{{$results_json}}" id="results_json">
        <input type="hidden" value="{{$items_ids}}" id="itemIds">
    </x-slot>
    {{-- <x-slot:aggregateCols>

    </x-slot> --}}
    <x-slot:thead>
        <th>
            <div class="flex flex-row items-center w-32">
                <x-easyadmin::utils.spotsort name="name" val="{{ $sort['name'] ?? 'none' }}" />
                <div class="relative flex-grow ml-2">
                    Name
                    <x-easyadmin::utils.spotsearch textval="{{ $params['name'] ?? '' }}" textname="name" label="Search role" />
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
        @foreach ($roles as $role)
            <tr>
                @if ($selectionEnabled)
                    <td><input type="checkbox" :value="{{$role->id}}" x-model="selectedIds"
                        class="checkbox checkbox-primary checkbox-xs">
                    </td>
                @endif
                <td>{{$role->name}}</td>
                <td>
                    <div class="flex flex-row justify-start space-x-4 items-center">
                        <a href="{{route('roles.edit', $role->id)}}"
                            @click.prevent.stop="$dispatch('linkaction', {link: '{{route('roles.edit', $role->id)}}', route: 'roles.edit'});"
                            class="btn btn-ghost btn-xs text-warning capitalize">
                            <x-easyadmin::display.icon icon="icons.edit" height="h-4" width="w-4"/>
                        </a>
                        <button @click.prevent.stop="$dispatch('deleteitem', {itemId: {{$role->id}}});" class="btn btn-ghost btn-xs text-error capitalize"><x-easyadmin::display.icon icon="icons.delete" height="h-4" width="w-4"/></button>
                    </div>
                </td>
            </tr>
        @endforeach
        {{-- <template x-for="result in results">
            <tr>
                <td><input type="checkbox" :value="result.id" x-model="selectedIds"
                        class="checkbox checkbox-primary checkbox-xs"></td>
                <td class="sticky !left-6">
                    <span x-text="result.name"></span>
                </td>
                <td class="sticky !left-36 z-20">
                    <div class="flex flex-row justify-start space-x-4 items-center">
                        <a href=""
                            @click.prevent.stop="$dispatch('linkaction', {link: '{{route('roles.edit', 'x_x')}}'.replace('x_x', result.id), route: 'roles.edit'});"
                            class="btn btn-ghost btn-xs text-warning capitalize">
                            <x-easyadmin::display.icon icon="icons.edit" height="h-4" width="w-4"/>
                        </a>
                        <button @click.prevent.stop="$dispatch('deleteitem', {itemId: result.id});" class="btn btn-ghost btn-xs text-error capitalize"><x-easyadmin::display.icon icon="icons.delete" height="h-4" width="w-4"/></button>
                    </div>
                </td>
            </tr>
        </template> --}}
    </x-slot>
</x-easyadmin::utils.panelbase>
