<script setup>
import BrandFilter from './components/BrandFilter.vue'
import VehicleList from './components/VehicleList.vue'
import { useVehicles } from './composables/useVehicles'

const { vehicles, filteredVehicles, brands, selectedBrand, loading, error, reload } = useVehicles()
</script>

<template>
  <header class="site-header text-white py-3">
    <div class="container">
      <h1 class="h4 m-0">Véhicules disponibles</h1>
    </div>
  </header>

  <main class="container pb-5">
    <div class="row align-items-end justify-content-between g-3 py-4">
      <div class="col-12 col-sm-7 col-md-5 col-lg-4">
        <BrandFilter
          v-model="selectedBrand"
          :brands="brands"
          :total="vehicles.length"
          :disabled="loading || !!error"
        />
      </div>
      <p v-if="!loading && !error" class="col-auto m-0 text-secondary" aria-live="polite">
        {{ filteredVehicles.length }} véhicule{{ filteredVehicles.length > 1 ? 's' : '' }}
      </p>
    </div>

    <VehicleList
      :vehicles="filteredVehicles"
      :loading="loading"
      :error="error"
      :filtered="!!selectedBrand"
      @retry="reload"
    />
  </main>
</template>
