<template>
    <div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4">
            <h2 class="text-xl font-semibold theme-title ">Transcrire</h2>
            <p class="theme-muted-text ">Générer la transcription</p>
        </div>
        <div class="rounded-lg p-4 bg-white dark:bg-slate-800/40 theme-body">Zone future: bouton pour lancer la
            génération de transcription.</div>
        <div class="mt-2 text-xs theme-muted-text">Video ID: {{ video?.id ?? '—' }}</div>
    </div>
</template>

<script setup>
defineProps({ video: { type: Object, default: null } })
</script>
