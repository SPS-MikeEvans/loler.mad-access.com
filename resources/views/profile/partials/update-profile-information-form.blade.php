<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-red">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full sm:w-72" :value="old('phone', $user->phone)" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        @if ($user->isInspector() || $user->isAdmin())
            <div class="border border-gray-200 rounded-lg p-4 space-y-4">
                <p class="text-sm font-medium text-gray-700">Inspector Qualifications</p>

                <div>
                    <x-input-label for="qualifications" :value="__('Qualifications & Experience')" />
                    <textarea id="qualifications" name="qualifications" rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red text-sm"
                        placeholder="e.g. IRATA Level 3 (2018), LEEA Licence, 8 years rope access exp…">{{ old('qualifications', $user->qualifications) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('qualifications')" />
                </div>

                <div>
                    <x-input-label for="qualification_expiry" :value="__('Certificate Expiry Date')" />
                    <x-text-input id="qualification_expiry" name="qualification_expiry" type="date"
                        class="mt-1 block w-full sm:w-48"
                        :value="old('qualification_expiry', $user->qualification_expiry?->format('Y-m-d'))" />
                    <x-input-error class="mt-2" :messages="$errors->get('qualification_expiry')" />
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="competent_person_flag" name="competent_person_flag" value="1"
                        {{ old('competent_person_flag', $user->competent_person_flag) ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-navy border-gray-300 rounded focus:ring-brand-red">
                    <x-input-label for="competent_person_flag" :value="__('Designated Competent Person (LOLER)')" />
                </div>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
