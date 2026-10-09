<script setup>
defineProps({
  brands: { type: Array, required: true }, // [{ name, count }]
  total: { type: Number, default: 0 },
  disabled: { type: Boolean, default: false },
})

const selected = defineModel({ type: String, default: '' })
</script>

<template>
  <div>
    <label for="brand-filter" class="form-label fw-semibold small mb-1">Marque</label>
    <select id="brand-filter" v-model="selected" class="form-select" :disabled="disabled">
      <option value="">Toutes les marques ({{ total }})</option>
      <option v-for="brand in brands" :key="brand.name" :value="brand.name">
        {{ brand.name }} ({{ brand.count }})
      </option>
    </select>
  </div>
</template>
