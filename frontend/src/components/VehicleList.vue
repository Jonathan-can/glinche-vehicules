<script setup>
import VehicleCard from './VehicleCard.vue'

defineProps({
  vehicles: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  filtered: { type: Boolean, default: false }, // vrai si une marque est sélectionnée
})

defineEmits(['retry'])
</script>

<template>
  <p v-if="loading" class="state" role="status">Chargement des véhicules…</p>

  <div v-else-if="error" class="state state-error" role="alert">
    <p>{{ error }}</p>
    <button type="button" @click="$emit('retry')">Réessayer</button>
  </div>

  <p v-else-if="vehicles.length === 0" class="state">
    {{ filtered ? 'Aucun véhicule ne correspond à cette marque.' : 'Aucun véhicule disponible pour le moment.' }}
  </p>

  <ul v-else class="grid">
    <li v-for="vehicle in vehicles" :key="vehicle.id ?? `${vehicle.brand}-${vehicle.model}-${vehicle.version}`">
      <VehicleCard :vehicle="vehicle" />
    </li>
  </ul>
</template>

<style scoped>
.grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr));
  gap: 1.25rem;
}

.state {
  padding: 2rem 1rem;
  text-align: center;
  color: var(--muted);
}

.state-error {
  color: var(--danger);
  background: var(--danger-bg);
  border: 1px solid var(--danger);
  border-radius: 8px;
}

button {
  font: inherit;
  padding: 0.55rem 1rem;
  border: 0;
  border-radius: 6px;
  background: var(--accent);
  color: #fff;
  cursor: pointer;
}
</style>
