<template>
  <div class="kanban-page">
    <div class="page-header flex justify-between items-center mb-4">
      <div>
        <h1 style="color: var(--primary);">Kanban Board</h1>
        <p>Drag and drop bugs to update their status.</p>
      </div>
    </div>

    <div class="kanban-board">
      <div 
        v-for="(column, key) in columns" 
        :key="key" 
        class="kanban-column"
        :class="{ 'in-progress': key === 'In Progress' }"
      >
        <div class="kanban-header flex justify-between items-center">
          <span style="font-weight: 600;">{{ key }}</span>
          <span class="badge" style="background: var(--border); color: var(--text);">
            {{ column.length }}
          </span>
        </div>
        
        <div class="kanban-cards">
          <VueDraggable
            v-model="columns[key]"
            group="bugs"
            :animation="150"
            ghost-class="ghost"
            class="drag-area"
            @end="onDragEnd($event, key)"
            item-key="id"
          >
            <template #item="{ element }">
              <div class="card bug-card" @click="$router.push(`/bugs/${element.id}`)">
                <div class="flex justify-between items-center mb-2">
                  <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">{{ element.project?.name }}</span>
                  <span style="font-size: 0.75rem; color: var(--text-secondary);">#{{ element.id }}</span>
                </div>
                <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem;">{{ element.title }}</h4>
                <div class="flex justify-between items-center mt-2">
                  <span class="badge" :style="{ backgroundColor: getSeverityColor(element.severity), color: 'white' }">
                    {{ element.severity }}
                  </span>
                  <div class="assignee-avatar">
                    {{ element.assignee ? element.assignee.name[0] : 'U' }}
                  </div>
                </div>
              </div>
            </template>
          </VueDraggable>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import api from '../../services/api';

const bugs = ref([]);
const columns = ref({
  'Open': [],
  'In Progress': [],
  'Fixed': [],
  'Not Fixed': [],
  'Closed': []
});

const loadBugs = async () => {
  try {
    const response = await api.get('/bugs');
    const data = response.data.data;
    bugs.value = data;
    
    // Distribute into columns
    const cols = {
      'Open': [],
      'In Progress': [],
      'Fixed': [],
      'Not Fixed': [],
      'Closed': []
    };
    
    data.forEach(bug => {
      if (cols[bug.status]) {
        cols[bug.status].push(bug);
      } else {
        cols['Open'].push(bug);
      }
    });
    
    columns.value = cols;
  } catch (error) {}
};

const onDragEnd = async (evt, toColumn) => {
  // Try to figure out where it moved
  // Actually, we can just watch columns or do it differently.
  // Draggable-plus will mutate columns[key] automatically.
  // When an item moves to a new list, we need to find which item and what its new list is.
};

// Simplified approach for updating status on drop
// VueDraggable emits `add` when an item is added to a list
// But let's use the `@change` or `watch` or handle it specifically if needed.
// Actually, with vue-draggable-plus, we can just do a watcher on columns or use the `onAdd` event.
const getSeverityColor = (sev) => {
  const map = {
    'Low': 'var(--severity-low)',
    'Medium': 'var(--severity-medium)',
    'High': 'var(--severity-high)',
    'Critical': 'var(--severity-critical)'
  };
  return map[sev] || map['Low'];
};

onMounted(() => {
  loadBugs();
});
</script>

<style scoped>
.drag-area {
  min-height: 200px;
  height: 100%;
}
.ghost {
  opacity: 0.5;
  background-color: var(--primary-light);
}
.bug-card {
  padding: 1rem;
  margin-bottom: 0.5rem;
  cursor: grab;
}
.bug-card:active {
  cursor: grabbing;
}
.bug-card:hover {
  border-color: var(--primary);
}
.assignee-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: var(--border);
  color: var(--text);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}
</style>
