<script setup lang="ts">
import { computed } from 'vue'

/**
 * A tiny pixel-art archer facing left, drawn from character grids so the
 * sprite stays editable by eye. `drawn` pulls the string back.
 *
 * With `aim` (degrees, 0 = straight ahead, positive = up: SVG turns clockwise,
 * and a bow drawn pointing left swings up when turned clockwise) the bow arm is a
 * separate layer rotating at the shoulder, and `flip` turns him to face right.
 * `loaded` false hides the arrow on the string (just shot).
 */
const props = withDefaults(defineProps<{
    drawn?: boolean
    aim?: number | null
    flip?: boolean
    loaded?: boolean
}>(), { aim: null, loaded: true })

const PALETTE: Record<string, string> = {
    h: '#16a34a', // hood
    H: '#15803d', // hood shade
    s: '#fcd3a8', // skin
    e: '#292524', // eye
    b: '#22c55e', // tunic
    B: '#166534', // belt
    w: '#a16207', // bow
    t: '#e7e5e4', // string
    l: '#78350f', // legs
    k: '#44403c', // boots
    f: '#f97316', // feather
    a: '#78350f', // arrow shaft
    p: '#57534e' // arrowhead
}

const IDLE = [
    '.....hhhh...',
    '....hhhhHh..',
    '....hsssHh..',
    '....esssh...',
    '.....sss....',
    '.w..bbbbb...',
    'w.t.sbbbbs..',
    'w.t..bbbbs..',
    'w.t..BBBB...',
    'w.t..bbbb...',
    '.w...l..l...',
    '.....l..l...',
    '....kk..kk..'
]

const DRAWN = [
    '.....hhhh...',
    '....hhhhHh..',
    '....hsssHh..',
    '....esssh...',
    '.....sss....',
    '.w..bbbbb...',
    'ws..tbbbbsf.',
    'ws...tbbbs..',
    'w...tBBBB...',
    'w..t.bbbb...',
    '.w...l..l...',
    '.....l..l...',
    '....kk..kk..'
]

/** The archer without his bow arm, for aiming. */
const BODY = [
    '.....hhhh...',
    '....hhhhHh..',
    '....hsssHh..',
    '....esssh...',
    '.....sss....',
    '....bbbbb...',
    '.....bbbbs..',
    '.....bbbbs..',
    '.....BBBB...',
    '.....bbbb...',
    '.....l..l...',
    '.....l..l...',
    '....kk..kk..'
]

/** Bow arm, shoulder at the right end of the middle row. */
const BOW_IDLE = [
    '.w.....',
    'w.t....',
    'w.t....',
    'paaasss',
    'w.t....',
    'w.t....',
    '.w.....'
]

const BOW_DRAWN = [
    '.w.....',
    'w..t...',
    'w...t..',
    'wpaaass',
    'w...t..',
    'w..t...',
    '.w.....'
]

/** Bow layer offset so its shoulder lands on the body's (5.5, 7.5). */
const BOW_OFFSET = { x: -1, y: 4 }
const SHOULDER = { x: 5.5, y: 7.5 }

function pixels (grid: string[], skip = ''): { x: number, y: number, c: string }[] {
    return grid.flatMap((row, y) => [...row].flatMap((char, x) => (PALETTE[char] && !skip.includes(char) ? [{ x, y, c: PALETTE[char] }] : [])))
}

const idle = pixels(IDLE)
const drawn = pixels(DRAWN)
const body = pixels(BODY)

const bow = computed(() => pixels(props.drawn ? BOW_DRAWN : BOW_IDLE, props.loaded ? '' : 'ap')
    .map(p => ({ ...p, x: p.x + BOW_OFFSET.x, y: p.y + BOW_OFFSET.y })))
</script>

<template>
    <svg
        v-if="aim === null"
        viewBox="0 0 12 13"
        shape-rendering="crispEdges"
        aria-hidden="true"
    >
        <rect
            v-for="(p, i) in (props.drawn ? drawn : idle)"
            :key="i"
            :x="p.x"
            :y="p.y"
            width="1"
            height="1"
            :fill="p.c"
        />
    </svg>
    <!-- Wider canvas: the bow swings past the body's box. -->
    <svg
        v-else
        viewBox="-4 -1 20 15"
        shape-rendering="crispEdges"
        aria-hidden="true"
    >
        <g :transform="flip ? 'translate(12 0) scale(-1 1)' : undefined">
            <rect
                v-for="(p, i) in body"
                :key="'b' + i"
                :x="p.x"
                :y="p.y"
                width="1"
                height="1"
                :fill="p.c"
            />
            <g :transform="`rotate(${aim} ${SHOULDER.x} ${SHOULDER.y})`">
                <rect
                    v-for="(p, i) in bow"
                    :key="'w' + i"
                    :x="p.x"
                    :y="p.y"
                    width="1.02"
                    height="1.02"
                    :fill="p.c"
                />
            </g>
        </g>
    </svg>
</template>
