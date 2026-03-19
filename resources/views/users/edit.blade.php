<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Edit User — {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    @if ($errors->any())
                        <div class="mb-6 px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                            <strong>Please correct the errors below.</strong>
                            <ul class="mt-1 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('users.update', $user) }}"
                          x-data="{ role: '{{ old('role', $user->role) }}' }"
                          class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2">
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $user->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                    :value="old('email', $user->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                    :value="old('phone', $user->phone)" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password" :value="__('New Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                                <p class="mt-1 text-xs text-gray-400">Leave blank to keep current password.</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="role" :value="__('Role')" />
                                <select id="role" name="role" x-model="role" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red">
                                    <option value="inspector" {{ old('role', $user->role) === 'inspector' ? 'selected' : '' }}>Inspector</option>
                                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="client_viewer" {{ old('role', $user->role) === 'client_viewer' ? 'selected' : '' }}>Client Viewer</option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>

                            <div class="sm:col-span-2" x-show="role === 'client_viewer'">
                                <x-input-label for="client_id" :value="__('Associated Client')" />
                                <select id="client_id" name="client_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red">
                                    <option value="">— Select client —</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', $user->client_id) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                            </div>
                        </div>

                        <div x-show="role === 'inspector' || role === 'admin'" class="border border-gray-200 rounded-lg p-4 space-y-4">
                            <p class="text-sm font-medium text-gray-700">Inspector Qualifications</p>

                            <div>
                                <x-input-label for="qualifications" :value="__('Qualifications & Experience')" />
                                <textarea id="qualifications" name="qualifications" rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                                    placeholder="e.g. IRATA Level 3 (2018), LEEA Licence, 8 years rope access exp…">{{ old('qualifications', $user->qualifications) }}</textarea>
                                <x-input-error :messages="$errors->get('qualifications')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="qualification_expiry" :value="__('Certificate Expiry Date')" />
                                <x-text-input id="qualification_expiry" name="qualification_expiry" type="date"
                                    class="mt-1 block w-full sm:w-48"
                                    :value="old('qualification_expiry', $user->qualification_expiry?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('qualification_expiry')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="competent_person_flag" name="competent_person_flag" value="1"
                                    {{ old('competent_person_flag', $user->competent_person_flag) ? 'checked' : '' }}
                                    class="h-4 w-4 text-brand-navy border-gray-300 rounded focus:ring-brand-red">
                                <x-input-label for="competent_person_flag" :value="__('Designated Competent Person (LOLER)')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save Changes</x-primary-button>
                            <a href="{{ route('users.index') }}">
                                <x-secondary-button type="button">Cancel</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
