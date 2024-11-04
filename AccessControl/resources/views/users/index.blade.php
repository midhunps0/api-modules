<x-easyadmin::utils.panelbase
    :x_ajax="$x_ajax"
    title="Users"
    indexUrl="{{route('users.index')}}"
    {{-- downloadUrl="{{route('users.download')}}" --}}
    selectIdsUrl="{{route('users.selectIds')}}"
    :items_count="$items_count"
    :items_ids="$items_ids"
    selected_ids="{{$selected_ids}}"
    :total_results="$total_results"
    :current_page="$current_page"
    :results="$users"
    results_name="users"
    unique_str="usersx"
    :results_json="$results_json"
    :paginator="$paginator"
    total_cols="4"
    adv_fields=""
    id="users_index"
    :selectionEnabled="$selectionEnabled">
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
                    <x-easyadmin::utils.spotsearch textval="{{ $params['name'] ?? '' }}" textname="name" label="Search user" />
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
        <x-accesscontrol::partials.users-rows :results="$users"/>
    </x-slot>
</x-easyadmin::utils.panelbase>
