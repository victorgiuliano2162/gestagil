import type { ThemeMode } from '@/types';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const STORAGE_KEY = 'gestagil-theme';
const VALID_THEMES: ThemeMode[] = ['light', 'dark', 'system'];

const theme = ref<ThemeMode>('system');
const systemPrefersDark = ref<boolean>(false);
let initialized = false;

function isValidTheme(value: unknown): value is ThemeMode {
    return typeof value === 'string' && VALID_THEMES.includes(value as ThemeMode);
}

function applyThemeToDom(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark =
        theme.value === 'dark' ||
        (theme.value === 'system' && systemPrefersDark.value);

    document.documentElement.classList.toggle('dark', isDark);
}

function initTheme(userTheme?: unknown): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!initialized) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        systemPrefersDark.value = mediaQuery.matches;

        const handleChange = (e: MediaQueryListEvent) => {
            systemPrefersDark.value = e.matches;
            applyThemeToDom();
        };

        mediaQuery.addEventListener('change', handleChange);
        initialized = true;
    }

    let stored: string | null = null;
    try {
        stored = localStorage.getItem(STORAGE_KEY);
    } catch {
        stored = null;
    }

    if (isValidTheme(userTheme)) {
        theme.value = userTheme;
        try {
            localStorage.setItem(STORAGE_KEY, userTheme);
        } catch {
            // ignore storage errors
        }
    } else if (isValidTheme(stored)) {
        theme.value = stored;
    } else {
        theme.value = 'system';
    }

    applyThemeToDom();
}

export function useTheme() {
    const page = usePage();
    const user = computed(() => (page.props.auth as { user?: { theme?: ThemeMode } | null } | undefined)?.user ?? null);

    if (!initialized) {
        initTheme(user.value?.theme);
    }

    const resolvedTheme = computed<'light' | 'dark'>(() =>
        theme.value === 'dark' || (theme.value === 'system' && systemPrefersDark.value)
            ? 'dark'
            : 'light',
    );

    const setTheme = async (newTheme: ThemeMode): Promise<void> => {
        theme.value = newTheme;

        try {
            localStorage.setItem(STORAGE_KEY, newTheme);
        } catch {
            // ignore storage errors
        }

        applyThemeToDom();

        if (user.value) {
            user.value.theme = newTheme;
            try {
                await axios.patch(route('profile.theme.update'), { theme: newTheme });
            } catch {
                // Keep optimistic UI update even if network request fails
            }
        }
    };

    const cycleTheme = (): void => {
        const order: ThemeMode[] = ['light', 'dark', 'system'];
        const nextIndex = (order.indexOf(theme.value) + 1) % order.length;
        void setTheme(order[nextIndex]);
    };

    return {
        theme,
        resolvedTheme,
        setTheme,
        cycleTheme,
        initTheme,
    };
}
