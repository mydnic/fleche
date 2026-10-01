<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import PixelArcher from '@/components/PixelArcher.vue'

/**
 * The archer on the auth screens: the bow follows the pointer, pressing draws
 * the string, a click looses an arrow that flies to the spot and sticks there.
 * Clicks on form controls are left alone, so logging in never fires a shot.
 *
 * `away` ducks him down out of his box (and holds his fire) while another
 * archer has the stage.
 */
const props = defineProps<{ away?: boolean }>()
const sprite = ref<HTMLElement | null>(null)
const quiver = ref<HTMLElement | null>(null)

const aim = ref(-10)
const flip = ref(false)
const drawn = ref(false)
const loaded = ref(true)

// PixelArcher's aiming canvas: viewBox "-4 -1 20 15", shoulder at (5.5, 7.5).
const VIEW = { x: -4, y: -1, width: 20 }
const SHOULDER = { x: 5.5, y: 7.5 }
const BOW_REACH = 7

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

function shoulder (): { x: number, y: number, unit: number } {
    const box = sprite.value!.getBoundingClientRect()
    const unit = box.width / VIEW.width
    const x = flip.value ? 12 - SHOULDER.x : SHOULDER.x

    return { x: box.left + (x - VIEW.x) * unit, y: box.top + (SHOULDER.y - VIEW.y) * unit, unit }
}

function track (event: PointerEvent): void {
    if (!sprite.value) {
        return
    }

    const box = sprite.value.getBoundingClientRect()
    flip.value = event.clientX > box.left + box.width / 2

    const origin = shoulder()
    const angle = Math.atan2(origin.y - event.clientY, Math.abs(event.clientX - origin.x)) * 180 / Math.PI
    aim.value = Math.max(-75, Math.min(85, angle))
}

function ignored (event: Event): boolean {
    return event.target instanceof Element && event.target.closest('input, button, a, label, select, textarea') !== null
}

function draw (event: PointerEvent): void {
    if (!props.away && !ignored(event)) {
        track(event)
        drawn.value = true
    }
}

function shoot (event: MouseEvent): void {
    drawn.value = false

    if (props.away || ignored(event) || !loaded.value || !quiver.value) {
        return
    }

    const origin = shoulder()
    const dx = event.clientX - origin.x
    const dy = event.clientY - origin.y
    const distance = Math.hypot(dx, dy)

    if (distance < BOW_REACH * origin.unit) {
        return
    }

    const heading = Math.atan2(dy, dx)
    const start = {
        x: origin.x + Math.cos(heading) * BOW_REACH * origin.unit,
        y: origin.y + Math.sin(heading) * BOW_REACH * origin.unit
    }
    // The arrow is drawn pointing left; its tip is the element's origin.
    const turn = `rotate(${heading * 180 / Math.PI - 180}deg)`

    const arrow = document.createElement('div')
    arrow.className = 'absolute top-0 left-0 h-3 w-10 origin-[0_50%]'
    arrow.innerHTML = '<svg viewBox="0 0 16 5" shape-rendering="crispEdges" class="size-full"><rect x="0" y="2" width="2" height="1" fill="#57534e"/><rect x="1" y="1" width="1" height="3" fill="#57534e"/><rect x="2" y="2" width="11" height="1" fill="#a16207"/><rect x="12" y="0" width="1" height="2" fill="#f97316"/><rect x="13" y="1" width="2" height="1" fill="#f97316"/><rect x="12" y="3" width="1" height="2" fill="#f97316"/><rect x="13" y="3" width="2" height="1" fill="#f97316"/></svg>'
    quiver.value.appendChild(arrow)

    const at = (x: number, y: number): string => `translate(${x}px, ${y - 6}px) ${turn}`
    const flight = reduceMotion ? 0 : Math.min(500, Math.max(120, distance / 1.8))

    arrow.animate([{ transform: at(start.x, start.y) }, { transform: at(event.clientX, event.clientY) }], {
        duration: flight,
        easing: 'cubic-bezier(.3, 0, .8, .6)',
        fill: 'forwards'
    }).finished.then(() => {
        // Thunk, then fade out where it landed.
        arrow.animate([
            { transform: `${at(event.clientX, event.clientY)} rotate(-6deg)` },
            { transform: `${at(event.clientX, event.clientY)} rotate(4deg)` },
            { transform: at(event.clientX, event.clientY) }
        ], { duration: reduceMotion ? 0 : 180, fill: 'forwards' })

        arrow.animate([{ opacity: 1 }, { opacity: 0 }], { delay: 1400, duration: 400, fill: 'forwards' })
            .finished.then(() => arrow.remove())
    })

    loaded.value = false
    setTimeout(() => (loaded.value = true), 350)
}

onMounted(() => {
    window.addEventListener('pointermove', track)
    window.addEventListener('pointerdown', draw)
    window.addEventListener('click', shoot)
})

onUnmounted(() => {
    window.removeEventListener('pointermove', track)
    window.removeEventListener('pointerdown', draw)
    window.removeEventListener('click', shoot)
})
</script>

<template>
    <div class="overflow-hidden">
        <div
            ref="sprite"
            class="size-full transition-transform duration-250"
            :class="away ? 'translate-y-full ease-in' : 'ease-[cubic-bezier(0.34,1.56,0.64,1)]'"
        >
            <PixelArcher
                :aim="aim"
                :flip="flip"
                :drawn="drawn"
                :loaded="loaded"
                class="size-full"
            />
        </div>
        <!-- In <body>: any transformed ancestor would otherwise become the
             fixed layer's containing block and offset every arrow. -->
        <Teleport to="body">
            <div
                ref="quiver"
                class="pointer-events-none fixed inset-0 z-50 overflow-hidden"
            />
        </Teleport>
    </div>
</template>
