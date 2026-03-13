export function toTitleCase(input) {
    if (input === null || input === undefined) return '';
    const str = String(input).trim();
    if (str === '') return '';

    return str
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => {
            const w = String(word);
            if (w.length === 0) return '';
            const first = w.charAt(0).toUpperCase();
            const rest = w.slice(1).toLowerCase();
            return first + rest;
        })
        .join(' ');
}
