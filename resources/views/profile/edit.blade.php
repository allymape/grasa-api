<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">My Profile</h2>
    </x-slot>

    <div class="mx-auto mt-8 max-w-5xl px-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="display_name" value="Display Name" />
                        <x-text-input id="display_name" name="display_name" class="mt-1 block w-full" :value="old('display_name', $profile?->display_name)" required />
                        <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="age" value="Age" />
                        <x-text-input id="age" type="number" name="age" class="mt-1 block w-full" :value="old('age', $profile?->age)" required />
                        <x-input-error :messages="$errors->get('age')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="region" value="Region" />
                        <x-text-input id="region" name="region" class="mt-1 block w-full" :value="old('region', $profile?->region)" required />
                        <x-input-error :messages="$errors->get('region')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="district" value="District (Optional)" />
                        <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district', $profile?->district)" />
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="current_residence" value="Current Residence" />
                        <x-text-input id="current_residence" name="current_residence" class="mt-1 block w-full" :value="old('current_residence', $profile?->current_residence)" required />
                        <x-input-error :messages="$errors->get('current_residence')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="height" value="Height" />
                        <x-text-input id="height" name="height" class="mt-1 block w-full" :value="old('height', $profile?->height)" required />
                        <x-input-error :messages="$errors->get('height')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="employment_status" value="Employment Status" />
                        <x-text-input id="employment_status" name="employment_status" class="mt-1 block w-full" :value="old('employment_status', $profile?->employment_status)" required />
                        <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="job_title" value="Job Title (Optional)" />
                        <x-text-input id="job_title" name="job_title" class="mt-1 block w-full" :value="old('job_title', $profile?->job_title)" />
                        <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="marital_status" value="Marital Status" />
                        <x-text-input id="marital_status" name="marital_status" class="mt-1 block w-full" :value="old('marital_status', $profile?->marital_status)" required />
                        <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="religion" value="Religion" />
                        <x-text-input id="religion" name="religion" class="mt-1 block w-full" :value="old('religion', $profile?->religion)" required />
                        <x-input-error :messages="$errors->get('religion')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="has_children" value="Has Children" />
                        <select id="has_children" name="has_children" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>
                            <option value="0" @selected(old('has_children', $profile?->has_children ? '1' : '0') == '0')>No</option>
                            <option value="1" @selected(old('has_children', $profile?->has_children ? '1' : '0') == '1')>Yes</option>
                        </select>
                        <x-input-error :messages="$errors->get('has_children')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="children_count" value="Children Count" />
                        <x-text-input id="children_count" type="number" name="children_count" class="mt-1 block w-full" :value="old('children_count', $profile?->children_count ?? 0)" required />
                        <x-input-error :messages="$errors->get('children_count')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="body_type" value="Body Type (Optional)" />
                        <x-text-input id="body_type" name="body_type" class="mt-1 block w-full" :value="old('body_type', $profile?->body_type)" />
                        <x-input-error :messages="$errors->get('body_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="skin_tone" value="Skin Tone (Optional)" />
                        <x-text-input id="skin_tone" name="skin_tone" class="mt-1 block w-full" :value="old('skin_tone', $profile?->skin_tone)" />
                        <x-input-error :messages="$errors->get('skin_tone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="about_me" value="About Me" />
                    <textarea id="about_me" name="about_me" rows="4" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>{{ old('about_me', $profile?->about_me) }}</textarea>
                    <x-input-error :messages="$errors->get('about_me')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="life_outlook" value="Life Outlook" />
                    <textarea id="life_outlook" name="life_outlook" rows="4" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>{{ old('life_outlook', $profile?->life_outlook) }}</textarea>
                    <x-input-error :messages="$errors->get('life_outlook')" class="mt-2" />
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <x-input-label for="photos" value="Upload Profile Photos" />
                    <input id="photos" type="file" name="photos[]" multiple accept="image/*" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('photos')" class="mt-2" />
                    <x-input-error :messages="$errors->get('photos.*')" class="mt-2" />

                    @if($profile?->photos?->count())
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($profile->photos as $photo)
                                <label class="rounded-lg border border-slate-200 bg-white p-2 text-xs">
                                    <img src="{{ asset('storage/'.$photo->path) }}" alt="profile photo" class="h-36 w-full rounded-md object-cover">
                                    <div class="mt-2 flex items-center justify-between">
                                        <span>{{ $photo->is_primary ? 'Primary' : 'Secondary' }}</span>
                                        <input type="radio" name="primary_photo_id" value="{{ $photo->id }}" @checked($photo->is_primary)>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_visible" value="1" class="rounded border-slate-300 text-emerald-600" @checked(old('is_visible', $profile?->is_visible ?? true))>
                    <span class="text-sm text-slate-700">Make profile visible after admin approval</span>
                </label>

                <div>
                    <x-primary-button>Save Profile</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
