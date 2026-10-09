<script setup>
defineProps({
  brands: { type: Array, required: true }, // [{ name, count }]
  total: { type: Number, default: 0 },
  disabled: { type: Boolean, default: false },
})

const selected = defineModel({ type: String, default: '' })
</script>

<template>
  <div class="filter">
    <label for="brand-filter">Marque</label>
    <select id="brand-filter" v-model="selected" :disabled="disabled">
      <option value="">Toutes les marques ({{ total }})</option>
      <option v-for="brand in brands" :key="brand.name" :value="brand.name">
        {{ brand.name }} ({{ brand.count }})
      </option>
    </select>
  </div>
</template>

<style scoped>
.filter {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: min(100%, 18rem);
}

label {
  font-size: 0.875rem;
  font-weight: 600;
}

select {
  font: inherit;
  padding: 0.65rem 0.8rem;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: var(--surface);
  color: var(--ink);
}
</style>
