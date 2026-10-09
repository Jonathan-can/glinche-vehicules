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
  <div v-if="loading" class="text-center text-secondary py-5" role="status">
    <div class="spinner-border spinner-border-sm me-2" aria-hidden="true"></div>
    Chargement des véhicules…
  </div>

  <div v-else-if="error" class="alert alert-danger text-center" role="alert">
    <p>{{ error }}</p>
    <button type="button" class="btn btn-accent" @click="$emit('retry')">Réessayer</button>
  </div>

  <p v-else-if="vehicles.length === 0" class="text-center text-secondary py-5">
    {{ filtered ? 'Aucun véhicule ne correspond à cette marque.' : 'Aucun véhicule disponible pour le moment.' }}
  </p>

  <!-- 1 colonne sur mobile, 2 sur petite tablette, 3 sur écran moyen, 4 sur grand écran -->
  <ul v-else class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 list-unstyled mb-0">
    <li
      v-for="vehicle in vehicles"
      :key="vehicle.id ?? `${vehicle.brand}-${vehicle.model}-${vehicle.version}`"
      class="col"
    >
      <VehicleCard :vehicle="vehicle" />
    </li>
  </ul>
</template>
