<template>
    <div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4">
            <h2 class="text-xl font-semibold theme-title ">Exporter</h2>
            <p class="theme-muted-text ">Exporter ou téléchargez votre taf</p>
        </div>

        <div class="rounded-lg p-4 bg-white dark:bg-slate-800/40 theme-body">Zone future: options d'export (Texte,
            Markdown, HTML, PDF) et envoi vers des plateformes.</div>
    </div>
</template>

<script setup>
defineProps({ video: { type: Object, default: null } })
</script>
