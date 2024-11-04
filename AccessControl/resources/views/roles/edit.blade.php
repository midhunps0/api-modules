<x-smartpages::dashboard-base :ajax="$x_ajax">
    <div x-data="{ compact: $persist(false) }" class="p-3 border-b border-base-200 overflow-x-scroll">

        <div class="flex flex-col justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-left w-full">Edit Role</h3>
            <div class="flex-grow flex flex-row justify-end items-center space-x-4">
                <form
                    x-data="{
                        name: '',
                        result: 0,
                        error: '',
                        formValid() {
                            return this.name.length > 0
                        },
                        update() {
                            let params = {
                                    _method: 'PUT',
                                    name: this.name
                                }
                            axios.post(
                                '{{route('roles.update', $role->id)}}',
                                params
                            ).then((r) => {
                                if (r.data.success) {
                                    this.result = 1;
                                } else {
                                    this.result = -1;
                                    console.log(Object.values(r.data.error));

                                    let estr = Object.values(r.data.error).reduce((s, e) => {
                                        e.forEach((ers) => {
                                            if (s.length > 0) {
                                                return s+', '+ers;
                                            }
                                            else {
                                                return s + ers;
                                            }
                                        });
                                        console.log(e);
                                        return s;
                                    });
                                    console.log(estr);
                                    this.error = estr;
                                }
                            })
                            .catch((e) => {
                                this.result = -1;
                                this.error = 'Sorry, something went wrong.';
                                console.log(e);
                            });
                        }
                    }"
                    x-init="
                        name = '{{$role->name}}';
                    "
                    action=""
                    @submit.prevent.stop="update();"
                    class="p-4 border border-base-200 rounded-md w-96 relative"
                >
                    <div x-show="result == 1" class="absolute top-0 left-0 z-20 w-full h-full bg-base-200 text-center flex flex-col space-y-8 items-center justify-center">
                            <div class="text-success">Role updated successfully!</div>
                            <div class="flex flex-row justify-evenly space-x-4">
                                <a href="{{route('roles.index')}}" @click.prevent.stop="result = 0; $dispatch('linkaction', {link: '{{route('roles.index')}}', route: 'roles.index'})" class="btn btn-sm capitalize">Back To Roles</a>
                                <button @click.prevent.stop="result = 0;" class="btn btn-sm capitalize">Close</button>
                            </div>
                    </div>
                    <div x-show="result == -1" class="absolute top-0 left-0 z-20 w-full h-full bg-base-200 text-center flex flex-col space-y-8 items-center justify-center">
                        <div class="text-error">Couldn't edit role:</div>
                        <div class="flex flex-col space-y-4 justify-center items-center space-x-4">
                            <span x-text="error"></span>
                            <button @click.prevent.stop="result = 0;" class="btn btn-sm btn-error capitalize">Ok</button>
                        </div>
                    </div>
                    <div class="form-control w-full max-w-md mb-1">
                        <label class="label mb-0 pb-0">
                          <span class="label-text">Name</span>
                        </label>
                        <input x-model="name" type="text" class="input input-bordered w-full max-w-md" required/>
                    </div>
                    <div class="form-control w-full max-w-md mt-6 mb-2">
                        <button type="submit" class="btn btn-primary" :disabled="!formValid()">Update</button>
                    </div>
                </form>
            </div>
            <div>
                <a href="{{route('roles.index')}}" @click.prevent.stop="$dispatch('linkaction', {link: '{{route('roles.index')}}', route: 'roles.index'})" class="text-warning">Go Back</a>
            </div>
        </div>
    </div>
</x-dashboard-base>
