/**
 * Number & Currency helper functions for frontend input and display.
 */

/**
 * Format a number into localized string with thousand separators (id-ID locale).
 * Example: 100000 -> "100.000"
 */
export function formatNumber(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '';
    const num = typeof value === 'string' ? parseNumber(value) : value;
    if (isNaN(num)) return '';
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Format a number into currency string (Rupiah).
 * Example: 150000 -> "Rp 150.000"
 */
export function formatCurrency(
    value: number | string | null | undefined,
    prefix: string = 'Rp ',
): string {
    if (value === null || value === undefined || value === '') return `${prefix}0`;
    const formatted = formatNumber(value);
    return formatted ? `${prefix}${formatted}` : `${prefix}0`;
}

/**
 * Parse any formatted string or number into a pure integer number.
 * Example: "Rp 150.000" -> 150000, "12,500" -> 12500
 */
export function parseNumber(value: string | number | null | undefined): number {
    if (value === null || value === undefined || value === '') return 0;
    if (typeof value === 'number') return isNaN(value) ? 0 : Math.round(value);
    
    // Remove all characters except digits and minus sign
    const cleaned = value.replace(/[^0-9-]/g, '');
    const parsed = parseInt(cleaned, 10);
    return isNaN(parsed) ? 0 : parsed;
}

/**
 * Filter an input event to allow only numeric characters.
 */
export function filterNumericInput(event: KeyboardEvent): void {
    const charCode = event.key;
    // Allow navigation keys, backspace, delete, tab, arrows
    if (
        ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(
            event.key,
        ) ||
        event.ctrlKey ||
        event.metaKey
    ) {
        return;
    }
    // Block non-digit characters
    if (!/^\d$/.test(charCode)) {
        event.preventDefault();
    }
}
