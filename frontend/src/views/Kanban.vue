<template>
  <div class="kanban-page">
    <BaseLoading :active="loading" message="Syncing board status..." />
    <BaseAlert v-model="alert.show" :type="alert.type" :title="alert.title" :message="alert.message" />

    <div class="page-header flex justify-between items-center mb-6">
      <div>
        <h1 style="color: var(--primary);">Kanban Board</h1>
        <p>Drag and drop bugs to update their lifecycle status.</p>
      </div>
      <div class="flex gap-2">
        <select v-model="filterProject" class="form-input" style="width: auto;">
          <option value="">All Projects</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>
    </div>

    <div class="kanban-board flex gap-4 overflow-x-auto pb-4">
      <div v-for="column in columns" :key="column.id" class="kanban-column">
        <div class="column-header flex justify-between items-center mb-4">
          <h3 class="flex items-center gap-2">
            <span class="dot" :style="{ backgroundColor: column.color }"></span>
            {{ column.name }}
          </h3>
          <span class="count-badge">{{ getBugsByStatus(column.name).length }}</span>
        </div>

        <div 
          class="column-body" 
          @dragover.prevent 
          @drop="handleDrop($event, column.name)"
        >
          <div 
            v-for="bug in getBugsByStatus(column.name)" 
            :key="bug.id" 
            class="kanban-card card shadow-sm"
            draggable="true"
            @dragstart="handleDragStart($event, bug)"
            @click="$router.push(`/bugs/${bug.id}`)"
          >
            <div class="flex justify-between items-start mb-2">
              <span class="bug-id">#{{ bug.id }}</span>
              <span class="badge badge-xs" :style="{ backgroundColor: getSeverityColor(bug.severity), color: 'white' }">
                {{ bug.severity }}
              </span>
            </div>
            <p class="bug-title">{{ bug.title }}</p>
            <div class="flex justify-between items-center mt-4">
              <div class="avatar-xs" v-if="bug.assignee">{{ bug.assignee.name[0] }}</div>
              <div class="avatar-xs unassigned" v-else>?</div>
              <span class="text-secondary" style="font-size: 0.7rem;">{{ new Date(bug.created_at).toLocaleDateString() }}</span>
            </div>
          </div>
          
          <div v-if="getBugsByStatus(column.name).length === 0" class="empty-column-text">
            No bugs here
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import api from '../services/api';
import BaseLoading from '../components/BaseLoading.vue';
import BaseAlert from '../components/BaseAlert.vue';

const loading = ref(true);
const bugs = ref([]);
const projects = ref([]);
const filterProject = ref('');
const alert = reactive({ show: false, type: 'info', title: '', message: '' });

const columns = [
  { id: 1, name: 'Open', color: '#60a5fa' },
  { id: 2, name: 'In Progress', color: '#fbbf24' },
  { id: 3, name: 'Fixed', color: '#10b981' },
  { id: 4, name: 'Closed', color: '#9ca3af' }
];

const showAlert = (type, title, message) => {
  alert.type = type;
  alert.title = title;
  alert.message = message;
  alert.show = true;
  setTimeout(() => alert.show = false, 5000);
};

const loadData = async () => {
  loading.value = true;
  try {
    const [bRes, pRes] = await Promise.all([
      api.get('/bugs'),
      api.get('/projects')
    ]);
    bugs.value = bRes.data.data || bRes.data;
    projects.value = pRes.data.data || pRes.data;
  } catch (error) {
    showAlert('error', 'Error', 'Failed to sync board.');
  } finally {
    loading.value = false;
  }
};

const getBugsByStatus = (status) => {
  return bugs.value.filter(b => {
    const matchesStatus = b.status === status;
    const matchesProject = !filterProject.value || b.project_id == filterProject.value;
    return matchesStatus && matchesProject;
  });
};

const getSeverityColor = (sev) => {
  const map = { 'Low': '#60a5fa', 'Medium': '#fbbf24', 'High': '#f97316', 'Critical': '#ef4444' };
  return map[sev] || '#60a5fa';
};

// Drag and Drop logic
const handleDragStart = (event, bug) => {
  event.dataTransfer.setData('bugId', bug.id);
  event.dataTransfer.effectAllowed = 'move';
};

const handleDrop = async (event, newStatus) => {
  const bugId = event.dataTransfer.getData('bugId');
  const bug = bugs.value.find(b => b.id == bugId);
  
  if (!bug || bug.status === newStatus) return;

  loading.value = true;
  try {
    await api.patch(`/bugs/${bugId}/status`, { 
      status: newStatus, 
      note: `Moved to ${newStatus} via Kanban Board` 
    });
    showAlert('success', 'Updated', `Bug #${bugId} moved to ${newStatus}`);
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', error.response?.data?.message || 'Invalid status transition');
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
.kanban-board {
  min-height: calc(100vh - 250px);
}
.kanban-column {
  min-width: 300px;
  max-width: 300px;
  background: var(--bg);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}
.column-header h3 { font-size: 1rem; margin: 0; }
.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.count-badge { background: var(--border); padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }

.column-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 200px;
}

.kanban-card {
  padding: 1rem;
  cursor: grab;
  transition: transform 0.2s, box-shadow 0.2s;
}
.kanban-card:active { cursor: grabbing; }
.kanban-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.bug-id { font-size: 0.75rem; color: var(--text-secondary); }
.bug-title { font-size: 0.875rem; font-weight: 600; margin: 0.5rem 0 0 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.avatar-xs { width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; }
.avatar-xs.unassigned { background: var(--border); color: var(--text-secondary); }

.badge-xs { padding: 1px 4px; font-size: 0.6rem; font-weight: 800; border-radius: 4px; }

.empty-column-text { text-align: center; color: var(--text-secondary); font-size: 0.875rem; padding-top: 2rem; border: 2px dashed var(--border); border-radius: 12px; flex: 1; display: flex; align-items: center; justify-content: center; }
</style>
