@extends('layouts.app', ['disableDefaultAssets' => true])
@push('scripts')
    @routes

    @vite(['resources/css/app.css', 'resources/js/app-traitement.js'])
    <script>
        localStorage.setItem('video_data', JSON.stringify(@json($video)));
        localStorage.setItem('auth_token', JSON.stringify(@json($authToken)));
        localStorage.setItem('promptes_section', JSON.stringify(@json($promptesSection)));
        localStorage.setItem('promptes_resume', JSON.stringify(@json($promptesResume)));
        localStorage.setItem('llms_configured', JSON.stringify(@json($llmsConfigured ?? [])));
    </script>
@endpush


@section('title', 'Traitement de la vidéo')
@section('page-title', 'Traitement de la vidéo')

@section('content')

    <div id="video-processing" class="max-w-7xl mx-auto">
        <div class=" ">
            <home-process></home-process>
        </div>
    </div>
@endsection
