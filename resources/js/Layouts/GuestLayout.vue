<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-950"
    >
        <div class="absolute right-4 top-4">
            <ThemeToggle />
        </div>

        <div>
            <Link href="/" class="flex flex-col items-center">
                <ApplicationLogo class="h-16 w-auto" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:border dark:border-gray-800 dark:bg-gray-900 dark:shadow-none"
        >
            <slot />
        </div>
    </div>
</template>
