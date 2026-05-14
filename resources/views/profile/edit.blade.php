<x-app-layout>
    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            <div>
                <h1 class="text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                    {{ __('Profile') }}
                </h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
                    {{ __('Manage your account settings') }}
                </p>
            </div>

            <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                        shadow-card p-6">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                        shadow-card p-6">
                @include('profile.partials.update-location-form')
            </div>

            <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                        shadow-card p-6">
                @include('profile.partials.update-password-form')
            </div>

            <div class="bg-surface-card rounded-2xl border border-neutral-200 dark:border-white/[0.06]
                        shadow-card p-6">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
