<x-exercise-layout
    title="Profil"
    description="Verwalte deine Kontodaten und Sicherheitseinstellungen."
>
    <x-slot name="badges">
        <span class="exercise-badge">Konto</span>
    </x-slot>

    <div class="space-y-6">
        <section class="exercise-card">
            <div class="exercise-card-body">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </section>

        <section class="exercise-card">
            <div class="exercise-card-body">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </section>

        <section class="exercise-card">
            <div class="exercise-card-body">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </section>
    </div>
</x-exercise-layout>
