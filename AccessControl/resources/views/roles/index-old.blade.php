<x-smartpages::dashboard-base :ajax="$x_ajax">
    <div x-data="{showcreateform: false, showeditform: false}" class="p-3 relative">
        <h1 class="mb-6 text-2xl font-bold">{{$title}}<button @click.prevent.stop="$dispatch('linkaction', {link: '{{route('roles.create')}}', route: 'roles.create'});" class="ml-4 btn btn-sm btn-primary">Add&nbsp;<x-easyadmin::display.icon icon="icons.plus" width="h-4" height="w-4"/></button></h1>
        <div class="overflow-x-auto">
            <table class="table table-compact w-full p-3">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
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
                </tbody>
            </table>
            <div>
                {{$roles->links()}}
            </div>
        </div>
    </div>
</x-smartpages::dashboard-base>
