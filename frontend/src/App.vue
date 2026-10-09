<script setup>
import BrandFilter from './components/BrandFilter.vue'
import VehicleList from './components/VehicleList.vue'
import { useVehicles } from './composables/useVehicles'

const { vehicles, filteredVehicles, brands, selectedBrand, loading, error, reload } = useVehicles()
</script>

<template>
  <header class="site-header">
    <div class="container">
      <h1>Véhicules disponibles</h1>
    </div>
  </header>

  <main class="container">
    <div class="toolbar">
      <BrandFilter
        v-model="selectedBrand"
        :brands="brands"
        :total="vehicles.length"
        :disabled="loading || !!error"
      />
      <p v-if="!loading && !error" class="count" aria-live="polite">
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

<style scoped>
.site-header {
  background: var(--ink);
  color: #fff;
  padding: 1.25rem 0;
}

.site-header h1 {
  margin: 0;
  font-size: 1.4rem;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem 0;
}

.count {
  margin: 0;
  color: var(--muted);
}
</style>
