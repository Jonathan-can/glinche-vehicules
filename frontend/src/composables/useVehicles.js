import { computed, onMounted, ref } from 'vue'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? ''

/**
 * Charge les véhicules une seule fois, puis filtre côté client :
 * changer de marque ne déclenche aucune nouvelle requête.
 */
export function useVehicles() {
  const vehicles = ref([])
  const loading = ref(true)
  const error = ref(null)
  const selectedBrand = ref('')

  // Marques construites à partir des véhicules reçus, avec leur nombre de véhicules.
  const brands = computed(() => {
    const counts = new Map()
    for (const { brand } of vehicles.value) {
      if (brand) counts.set(brand, (counts.get(brand) ?? 0) + 1)
    }
    return [...counts]
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => a.name.localeCompare(b.name, 'fr'))
  })

  const filteredVehicles = computed(() =>
    selectedBrand.value
      ? vehicles.value.filter((vehicle) => vehicle.brand === selectedBrand.value)
      : vehicles.value,
  )

  async function load() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE_URL}/api/vehicles`, {
        headers: { Accept: 'application/json' },
      })
      const body = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(body.message ?? 'Impossible de charger les véhicules.')
      }

      vehicles.value = body.data ?? []
    } catch (e) {
      error.value =
        e instanceof TypeError
          ? 'Le serveur est injoignable. Vérifiez que Laravel est bien lancé.'
          : e.message
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return { vehicles, filteredVehicles, brands, selectedBrand, loading, error, reload: load }
}
