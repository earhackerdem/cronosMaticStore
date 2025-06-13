import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light';

const applyTheme = () => {
    // Always remove dark class to ensure light theme
    document.documentElement.classList.remove('dark');
};

export function initializeTheme() {
    // Always apply light theme
    applyTheme();
}

export function useAppearance() {
    const [appearance] = useState<Appearance>('light');

    const updateAppearance = useCallback(() => {
        // Do nothing - appearance is locked to light
        applyTheme();
    }, []);

    useEffect(() => {
        // Always apply light theme
        applyTheme();
    }, []);

    return { appearance, updateAppearance } as const;
}
