@props(['results' => []])
@foreach ($results as $result)
    <tr>
        <td><input type="checkbox" :value="{{$result->id}}" x-model="selectedIds"
                class="checkbox checkbox-primary checkbox-xs"></td>
        <td class="sticky !left-6">
            <span>{{$result->name}}</span>
        </td>
        <td class="sticky !left-36 z-20">
            <div class="flex flex-row justify-start space-x-4 items-center">
                <a href=""
                    @click.prevent.stop="$dispatch('linkaction', {link: '{{route('users.edit', $result->id)}}', route: 'users.edit'});"
                    class="btn btn-ghost btn-xs text-warning capitalize">
                    <x-easyadmin::display.icon icon="icons.edit" height="h-4" width="w-4"/>
                </a>
                <button @click.prevent.stop="$dispatch('deleteitem', {itemId: {{$result->id}}});" class="btn btn-ghost btn-xs text-error capitalize"><x-easyadmin::display.icon icon="icons.delete" height="h-4" width="w-4"/></button>
            </div>
        </td>
    </tr>
@endforeach
