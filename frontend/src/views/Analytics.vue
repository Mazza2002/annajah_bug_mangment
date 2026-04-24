<template>
  <div class="analytics">
    <h1 style="color: var(--primary); margin-bottom: 0.5rem;">Analytics</h1>
    <p style="margin-bottom: 2rem;">Platform performance and developer metrics.</p>

    <div class="card mb-4">
      <h3 class="mb-4">Bugs Resolved (Last 5 Days)</h3>
      <div style="height: 300px; display: flex; align-items: flex-end; gap: 1rem; padding-top: 2rem;">
        <!-- Simple CSS Bar Chart -->
        <div v-for="(val, idx) in chartData.resolved" :key="idx" class="flex-col items-center justify-end" style="flex: 1; height: 100%; display: flex;">
          <div class="stat-tooltip">{{ val }}</div>
          <div 
            style="width: 50%; background-color: var(--primary); border-radius: 4px 4px 0 0; transition: height 0.5s;" 
            :style="{ height: `${(val / Math.max(...chartData.resolved)) * 100}%` }"
          ></div>
          <div style="margin-top: 0.5rem; font-size: 0.875rem;">{{ chartData.labels[idx] }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const chartData = ref({
  labels: [],
  resolved: []
});

const fetchPerformance = async () => {
  try {
    const response = await api.get('/analytics/performance');
    chartData.value = response.data;
  } catch (error) {
    chartData.value = {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
      resolved: [1, 2, 0, 5, 3]
    };
  }
};

onMounted(() => {
  fetchPerformance();
});
</script>

<style scoped>
.stat-tooltip {
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--text-secondary);
}
</style>
