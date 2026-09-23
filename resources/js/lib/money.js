/**
 * Format a numeric price with the active salon currency (Rs / AED / …).
 */
export function formatMoney(price, currency = 'Rs') {
    const value = Number(price);
    if (!Number.isFinite(value)) {
        return String(price);
    }

    const locale = currency === 'AED' ? 'en-AE' : 'en-IN';

    return `${currency} ${value.toLocaleString(locale)}`;
}

/**
 * Menu flyer prices are stored as display strings (e.g. "4,500").
 */
export function formatMenuPrice(price, currency = 'Rs') {
    const text = String(price);
    if (
        text.includes('%') ||
        text.toLowerCase().includes('consult') ||
        text.toLowerCase().includes('ask')
    ) {
        return text;
    }

    return `${currency} ${text}`;
}

/**
 * Best-effort @handle from a social profile URL.
 */
export function socialHandle(url, fallback = '') {
    if (!url) {
        return fallback;
    }

    try {
        const path = new URL(url).pathname.replace(/\/+$/, '');
        const last = path.split('/').filter(Boolean).pop() ?? '';
        if (!last) {
            return fallback;
        }

        return last.startsWith('@') ? last : `@${last}`;
    } catch {
        return fallback;
    }
}
