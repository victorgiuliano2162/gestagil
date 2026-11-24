<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';

const { resolvedTheme } = useTheme();
</script>

<template>
    <img
        :src="resolvedTheme === 'dark' ? '/images/logo-dark.png' : '/images/logo.png'"
        alt="GestÁgil"
        class="object-contain"
    />
</template>
