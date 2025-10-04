@extends('layouts.app', ['disableDefaultAssets' => true])
@push('scripts')
    @vite(['resources/css/app.css', 'resources/js/app-traitement.js'])
    <script>
        localStorage.setItem('video_data', JSON.stringify(@json($video)));
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
