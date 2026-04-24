<template>
  <div class="bugs-page">
    <div class="page-header flex justify-between items-center mb-4">
      <div>
        <h1 style="color: var(--primary);">Bug Tracking</h1>
        <p>Track and manage application issues across projects.</p>
      </div>
      
      <div class="header-actions flex gap-4">
        <button class="btn btn-primary" @click="$router.push('/bugs/new')">
          <span>Report Bug</span>
        </button>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
      <div class="flex justify-between items-center">
        <div class="flex gap-4">
          <select v-model="filters.project_id" class="form-input" style="width: auto;">
            <option value="">All Projects</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <select v-model="filters.status" class="form-input" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="Open">Open</option>
            <option value="In Progress">In Progress</option>
            <option value="Fixed">Fixed</option>
            <option value="Not Fixed">Not Fixed</option>
            <option value="Closed">Closed</option>
          </select>
          <select v-model="filters.severity" class="form-input" style="width: auto;">
            <option value="">All Severities</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
          <select v-model="filters.category_id" class="form-input" style="width: auto;">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <span style="font-size: 0.875rem; color: var(--text-secondary);">
            {{ bugs.length }} bugs found
          </span>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div class="card" style="padding: 0; overflow: hidden;">
      <table class="w-full text-left" style="border-collapse: collapse;">
        <thead>
          <tr style="background-color: var(--bg); border-bottom: 1px solid var(--border);">
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">ID</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Title</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Project</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Severity</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Status</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Category</th>
            <th class="py-3 px-4" style="font-weight: 600; font-size: 0.875rem;">Assignee</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="bugs.length === 0">
            <td colspan="6" class="py-8 text-center text-secondary">No bugs found.</td>
          </tr>
          <tr 
            v-for="bug in bugs" 
            :key="bug.id" 
            class="table-row"
            @click="$router.push(`/bugs/${bug.id}`)"
          >
            <td class="py-3 px-4 text-secondary">#{{ bug.id }}</td>
            <td class="py-3 px-4" style="font-weight: 500;">{{ bug.title }}</td>
            <td class="py-3 px-4 text-secondary">{{ bug.project?.name }}</td>
            <td class="py-3 px-4">
              <span class="badge" :style="{ backgroundColor: getSeverityColor(bug.severity), color: 'white' }">
                {{ bug.severity }}
              </span>
            </td>
            <td class="py-3 px-4">
              <span class="badge" :style="{ backgroundColor: getStatusColor(bug.status), color: 'white' }">
                {{ bug.status }}
              </span>
            </td>
            <td class="py-3 px-4 text-secondary">
              {{ bug.category ? bug.category.name : 'N/A' }}
            </td>
            <td class="py-3 px-4 text-secondary">
              {{ bug.assignee ? bug.assignee.name : 'Unassigned' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';

const bugs = ref([]);
const projects = ref([]);
const categories = ref([]);
const filters = ref({
  project_id: '',
  severity: '',
  status: '',
  category_id: ''
});

const loadBugs = async () => {
  try {
    const params = new URLSearchParams();
    if (filters.value.project_id) params.append('project_id', filters.value.project_id);
    if (filters.value.severity) params.append('severity', filters.value.severity);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.category_id) params.append('category_id', filters.value.category_id);
    
    const response = await api.get(`/bugs?${params.toString()}`);
    bugs.value = response.data.data;
  } catch (error) {}
};

const loadProjects = async () => {
  try {
    const response = await api.get('/projects');
    projects.value = response.data.data || response.data;
  } catch (error) {}
};

const loadCategories = async () => {
  try {
    const response = await api.get('/categories');
    categories.value = response.data;
  } catch (error) {}
};

const getSeverityColor = (sev) => {
  const map = {
    'Low': 'var(--severity-low)',
    'Medium': 'var(--severity-medium)',
    'High': 'var(--severity-high)',
    'Critical': 'var(--severity-critical)'
  };
  return map[sev] || map['Low'];
};

const getStatusColor = (stat) => {
  const map = {
    'Open': 'var(--status-open)',
    'In Progress': 'var(--status-in-progress)',
    'Fixed': 'var(--status-fixed)',
    'Not Fixed': 'var(--status-not-fixed)',
    'Closed': 'var(--status-closed)'
  };
  return map[stat] || map['Open'];
};

onMounted(() => {
  loadBugs();
  loadProjects();
  loadCategories();
});

watch(filters, loadBugs, { deep: true });
</script>

<style scoped>
.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.text-center { text-align: center; }
.text-secondary { color: var(--text-secondary); }

.table-row {
  border-bottom: 1px solid var(--border);
  transition: background-color 0.2s;
  cursor: pointer;
}
.table-row:hover {
  background-color: var(--primary-light);
}
</style>
