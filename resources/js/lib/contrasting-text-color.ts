const LIGHT_TEXT = '#ffffff';
const DARK_TEXT = '#111111';
const LUMINANCE_THRESHOLD = 0.179;

function linearChannel(channel: number): number {
    return channel <= 0.04045
        ? channel / 12.92
        : ((channel + 0.055) / 1.055) ** 2.4;
}

function relativeLuminance(hexColor: string): number {
    const [red, green, blue] = [1, 3, 5].map((start) =>
        linearChannel(parseInt(hexColor.slice(start, start + 2), 16) / 255),
    );

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

export function contrastingTextColor(hexColor: string): string {
    return relativeLuminance(hexColor) > LUMINANCE_THRESHOLD
        ? DARK_TEXT
        : LIGHT_TEXT;
}
