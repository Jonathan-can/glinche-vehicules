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
  <article class="card">
    <div class="photo">
      <img
        v-if="showImage"
        :src="vehicle.image"
        :alt="title"
        loading="lazy"
        @error="imageFailed = true"
      />
      <div v-else class="photo-fallback" role="img" aria-label="Photo indisponible">
        <svg viewBox="0 0 64 64" width="56" height="56" aria-hidden="true">
          <path
            d="M8 40l4-14a6 6 0 0 1 5.7-4.3h28.6A6 6 0 0 1 52 26l4 14v8h-6v-4H14v4H8zM16 36h32l-2.4-8H18.4z"
            fill="currentColor"
          />
          <circle cx="18" cy="40" r="3" fill="var(--surface)" />
          <circle cx="46" cy="40" r="3" fill="var(--surface)" />
        </svg>
        <span>Photo indisponible</span>
      </div>
    </div>

    <div class="body">
      <h3>{{ title }}</h3>
      <p v-if="vehicle.version" class="version">{{ vehicle.version }}</p>

      <ul v-if="specs.length" class="specs">
        <li v-for="spec in specs" :key="spec">{{ spec }}</li>
      </ul>

      <p class="price">{{ price }}</p>
    </div>
  </article>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 8px;
  overflow: hidden;
}

.photo {
  aspect-ratio: 4 / 3;
  background: var(--placeholder);
}

.photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.photo-fallback {
  height: 100%;
  display: grid;
  place-content: center;
  justify-items: center;
  gap: 0.5rem;
  color: var(--muted);
  font-size: 0.85rem;
}

.body {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding: 1rem;
  flex: 1;
}

h3 {
  margin: 0;
  font-size: 1.1rem;
}

.version {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
}

.specs {
  list-style: none;
  margin: 0.25rem 0 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.specs li {
  padding: 0.2rem 0.55rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  font-size: 0.8rem;
}

.price {
  margin: auto 0 0;
  padding-top: 0.75rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--accent);
}
</style>
