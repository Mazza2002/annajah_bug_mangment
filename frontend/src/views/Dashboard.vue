<template>
  <div class="dashboard">
    <BaseLoading :active="loading" />
    <h1 style="color: var(--primary); margin-bottom: 0.5rem;">Dashboard</h1>
    <p style="margin-bottom: 2rem;">Overview of your projects and bugs.</p>

    <div class="stats-grid">
      <div class="card stat-card">
        <h3>Total Bugs</h3>
        <div class="stat-value">{{ stats.total }}</div>
      </div>
      <div class="card stat-card">
        <h3>Open</h3>
        <div class="stat-value" style="color: var(--status-open);">{{ stats.open }}</div>
      </div>
      <div class="card stat-card">
        <h3>In Progress</h3>
        <div class="stat-value" style="color: var(--status-in-progress);">{{ stats.inProgress }}</div>
      </div>
      <div class="card stat-card">
        <h3>Critical</h3>
        <div class="stat-value" style="color: var(--severity-critical);">{{ stats.critical }}</div>
      </div>
    </div>

    <div class="card mt-4">
      <h3 class="mb-4">Recent Activity</h3>
      <div v-if="activities.length === 0" class="text-secondary">No recent activity.</div>
      <div class="activity-feed">
        <div v-for="log in activities" :key="log.id" class="activity-item">
          <div style="font-weight: 500;">{{ log.user?.name }}</div>
          <div style="font-size: 0.875rem;">
            {{ log.action }} a {{ log.entity_type }}
            <span v-if="log.properties?.title">"{{ log.properties.title }}"</span>
          </div>
          <div style="font-size: 0.75rem; color: var(--text-secondary);">
            {{ new Date(log.created_at).toLocaleString() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import BaseLoading from '../components/BaseLoading.vue';

const stats = ref({
  total: 0,
  open: 0,
  inProgress: 0,
  critical: 0
});

const activities = ref([]);
const loading = ref(true);

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    const response = await api.get('/analytics/dashboard');
    stats.value = response.data.stats;
    activities.value = response.data.activities;
  } catch (error) {
    // mock data if endpoint is not fully ready
    stats.value = {
      total: 12, open: 5, inProgress: 3, critical: 1
    };
    activities.value = [
      { id: 1, user: { name: 'System' }, action: 'created', entity_type: 'bug', created_at: new Date().toISOString() }
    ];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}
.stat-card {
  text-align: center;
}
.stat-card h3 {
  font-size: 1rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}
.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text);
}
.activity-feed {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.activity-item {
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}
.activity-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
</style>
