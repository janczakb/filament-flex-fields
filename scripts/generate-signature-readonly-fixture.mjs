/**
 * Generates the readonly playground signature fixture using the same
 * quadratic path format as SignatureField exports (dense sampled points).
 */
import fs from 'node:fs';
import path from 'node:path';

const packageRoot = path.resolve(import.meta.dirname, '..');
const outputPath = path.join(packageRoot, 'resources/fixtures/signature-readonly-preview.svg');

const viewBoxWidth = 1000;
const viewBoxHeight = 320;
const penColor = '#18181b';
const defaultPenWidth = 2.5;

function roundCoord(value) {
    return Math.round(value * 10) / 10;
}

function sampleCubic(p0, p1, p2, p3, steps, widthAt = () => defaultPenWidth) {
    const points = [];

    for (let index = 0; index <= steps; index++) {
        const t = index / steps;
        const inverse = 1 - t;

        points.push({
            x: inverse ** 3 * p0.x + 3 * inverse ** 2 * t * p1.x + 3 * inverse * t ** 2 * p2.x + t ** 3 * p3.x,
            y: inverse ** 3 * p0.y + 3 * inverse ** 2 * t * p1.y + 3 * inverse * t ** 2 * p2.y + t ** 3 * p3.y,
            width: widthAt(t),
        });
    }

    return points;
}

function joinStrokes(strokeParts) {
    const points = [];

    for (const part of strokeParts) {
        if (points.length > 0) {
            points.pop();
        }

        points.push(...part);
    }

    return points;
}

function pointsToQuadraticPath(points) {
    if (points.length === 0) {
        return '';
    }

    if (points.length === 1) {
        const point = points[0];

        return `M${roundCoord(point.x)},${roundCoord(point.y)}`;
    }

    if (points.length === 2) {
        return `M${roundCoord(points[0].x)},${roundCoord(points[0].y)}L${roundCoord(points[1].x)},${roundCoord(points[1].y)}`;
    }

    let path = `M${roundCoord(points[0].x)},${roundCoord(points[0].y)}`;

    for (let index = 1; index < points.length - 1; index++) {
        const control = points[index];
        const midX = (points[index].x + points[index + 1].x) / 2;
        const midY = (points[index].y + points[index + 1].y) / 2;

        path += `Q${roundCoord(control.x)},${roundCoord(control.y)} ${roundCoord(midX)},${roundCoord(midY)}`;
    }

    const last = points[points.length - 1];
    const prev = points[points.length - 2];

    path += `Q${roundCoord(prev.x)},${roundCoord(prev.y)} ${roundCoord(last.x)},${roundCoord(last.y)}`;

    return path;
}

function strokesToSvg(strokes) {
    const paths = strokes.map((stroke) => {
        const d = pointsToQuadraticPath(stroke.points);
        const width = stroke.width ?? defaultPenWidth;

        return `<path d="${d}" fill="none" stroke="${penColor}" stroke-width="${roundCoord(width)}" stroke-linecap="round" stroke-linejoin="round"/>`;
    }).join('');

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${viewBoxWidth} ${viewBoxHeight}">${paths}</svg>`;
}

/** Hand-authored cursive "James Wilson" style signature (sampled like real pad input). */
const strokes = [
    {
        width: 3.1,
        points: joinStrokes([
            sampleCubic({ x: 108, y: 214 }, { x: 102, y: 176 }, { x: 118, y: 132 }, { x: 152, y: 112 }, 18, (t) => 2.8 + t * 0.8),
            sampleCubic({ x: 152, y: 112 }, { x: 188, y: 92 }, { x: 214, y: 118 }, { x: 206, y: 152 }, 16, (t) => 3.2 - t * 0.3),
            sampleCubic({ x: 206, y: 152 }, { x: 198, y: 186 }, { x: 168, y: 204 }, { x: 142, y: 188 }, 14, (t) => 2.9 - t * 0.2),
            sampleCubic({ x: 142, y: 188 }, { x: 124, y: 176 }, { x: 118, y: 154 }, { x: 136, y: 142 }, 12, (t) => 2.6 + t * 0.4),
            sampleCubic({ x: 136, y: 142 }, { x: 158, y: 130 }, { x: 182, y: 146 }, { x: 198, y: 168 }, 14, (t) => 2.8 + Math.sin(t * Math.PI) * 0.5),
        ]),
    },
    {
        width: 2.7,
        points: joinStrokes([
            sampleCubic({ x: 198, y: 168 }, { x: 228, y: 154 }, { x: 258, y: 176 }, { x: 282, y: 158 }, 14),
            sampleCubic({ x: 282, y: 158 }, { x: 304, y: 142 }, { x: 322, y: 164 }, { x: 342, y: 148 }, 12),
            sampleCubic({ x: 342, y: 148 }, { x: 358, y: 134 }, { x: 376, y: 158 }, { x: 396, y: 142 }, 12),
            sampleCubic({ x: 396, y: 142 }, { x: 412, y: 128 }, { x: 432, y: 152 }, { x: 452, y: 136 }, 12),
            sampleCubic({ x: 452, y: 136 }, { x: 468, y: 124 }, { x: 486, y: 148 }, { x: 506, y: 132 }, 12),
        ]),
    },
    {
        width: 2.9,
        points: joinStrokes([
            sampleCubic({ x: 524, y: 148 }, { x: 552, y: 124 }, { x: 588, y: 152 }, { x: 622, y: 128 }, 16),
            sampleCubic({ x: 622, y: 128 }, { x: 652, y: 108 }, { x: 684, y: 138 }, { x: 712, y: 118 }, 14),
            sampleCubic({ x: 712, y: 118 }, { x: 738, y: 100 }, { x: 764, y: 128 }, { x: 792, y: 108 }, 14),
            sampleCubic({ x: 792, y: 108 }, { x: 818, y: 90 }, { x: 846, y: 118 }, { x: 872, y: 98 }, 14),
            sampleCubic({ x: 872, y: 98 }, { x: 894, y: 82 }, { x: 914, y: 108 }, { x: 928, y: 92 }, 10),
            sampleCubic({ x: 928, y: 92 }, { x: 938, y: 84 }, { x: 946, y: 102 }, { x: 934, y: 112 }, 8),
        ]),
    },
    {
        width: 2.2,
        points: joinStrokes([
            sampleCubic({ x: 122, y: 232 }, { x: 268, y: 246 }, { x: 428, y: 224 }, { x: 588, y: 238 }, 20, (t) => 2.0 + Math.sin(t * Math.PI) * 0.5),
            sampleCubic({ x: 588, y: 238 }, { x: 712, y: 248 }, { x: 828, y: 222 }, { x: 918, y: 236 }, 16, (t) => 2.2 - t * 0.3),
        ]),
    },
    {
        width: 2.4,
        points: joinStrokes([
            sampleCubic({ x: 878, y: 112 }, { x: 902, y: 132 }, { x: 922, y: 154 }, { x: 908, y: 176 }, 10),
            sampleCubic({ x: 908, y: 176 }, { x: 896, y: 192 }, { x: 872, y: 186 }, { x: 858, y: 168 }, 8),
        ]),
    },
];

const svg = strokesToSvg(strokes);

fs.writeFileSync(outputPath, `${svg}\n`);

console.log(`Wrote ${outputPath}`);
