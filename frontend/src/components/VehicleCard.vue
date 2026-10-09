<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  vehicle: { type: Object, required: true },
})

const imageFailed = ref(false)
const showImage = computed(() => props.vehicle.image && !imageFailed.value)

const title = computed(() => [props.vehicle.brand, props.vehicle.model].filter(Boolean).join(' ') || 'Véhicule')

const price = computed(() =>
  props.vehicle.price == null
    ? 'Prix sur demande'
    : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(
        props.vehicle.price,
      ),
)

const specs = computed(() => {
  const { year, mileage, energy, gearbox } = props.vehicle
  return [
    year,
    mileage != null ? `${new Intl.NumberFormat('fr-FR').format(mileage)} km` : null,
    energy,
    gearbox,
  ].filter(Boolean)
})
</script>

<template>
  <article class="card h-100 overflow-hidden">
    <div class="ratio ratio-4x3 bg-body-secondary">
      <img
        v-if="showImage"
        :src="vehicle.image"
        :alt="title"
        class="object-fit-cover"
        loading="lazy"
        @error="imageFailed = true"
      />
      <div
        v-else
        class="d-flex flex-column align-items-center justify-content-center gap-2 text-secondary small"
        role="img"
        aria-label="Photo indisponible"
      >
        <svg viewBox="0 0 64 64" width="56" height="56" aria-hidden="true">
          <path
            d="M8 40l4-14a6 6 0 0 1 5.7-4.3h28.6A6 6 0 0 1 52 26l4 14v8h-6v-4H14v4H8zM16 36h32l-2.4-8H18.4z"
            fill="currentColor"
          />
          <circle cx="18" cy="40" r="3" fill="var(--bs-secondary-bg)" />
          <circle cx="46" cy="40" r="3" fill="var(--bs-secondary-bg)" />
        </svg>
        <span>Photo indisponible</span>
      </div>
    </div>

    <div class="card-body d-flex flex-column">
      <h3 class="card-title h6 fw-bold mb-1">{{ title }}</h3>
      <p v-if="vehicle.version" class="card-text text-secondary small mb-2">{{ vehicle.version }}</p>

      <ul v-if="specs.length" class="list-unstyled d-flex flex-wrap gap-1 mb-3">
        <li v-for="spec in specs" :key="spec" class="badge rounded-pill border text-body fw-normal">{{ spec }}</li>
      </ul>

      <p class="fs-5 fw-bold text-accent mt-auto mb-0">{{ price }}</p>
    </div>
  </article>
</template>
