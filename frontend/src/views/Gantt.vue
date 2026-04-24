<template>
  <div class="gantt-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p class="loading-text">Rendering timeline...</p>
    </div>

    <div class="gantt-widget">
      <!-- Blue Header Bar -->
      <div class="gantt-title-bar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
          <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
          <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        <span>Gantt Chart</span>
      </div>

      <!-- Filters -->
      <div class="gantt-controls">
        <label>Severity:</label>
        <select v-model="filterSeverity">
          <option value="">All</option>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
          <option value="Critical">Critical</option>
        </select>

        <label>Status:</label>
        <select v-model="filterStatus">
          <option value="">All</option>
          <option value="Open">Open</option>
          <option value="In Progress">In Progress</option>
          <option value="Resolved">Resolved</option>
          <option value="Closed">Closed</option>
        </select>
      </div>

      <div class="gantt-scroll-wrapper">
        <div class="gantt-inner">

          <!-- Sticky Header -->
          <div class="gantt-header-row">
            <div class="tasks-label-col">Tasks</div>
            <div class="weeks-wrapper">
              <!-- Week Labels -->
              <div class="weeks-row">
                <div class="week-cell">Week 1</div>
                <div class="week-cell">Week 2</div>
              </div>
              <!-- Day Numbers -->
              <div class="days-row">
                <div
                  v-for="(date, index) in timelineDays"
                  :key="index"
                  class="day-cell"
                  :class="{ 'today-cell': isToday(date) }"
                >
                  {{ date.getDate() }}
                </div>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="gantt-body">
            <!-- Skeleton Rows -->
            <template v-if="loading">
              <div v-for="n in 8" :key="n" class="gantt-row skeleton-row">
                <div class="task-name-col">
                  <div class="skel" :style="{ width: (50 + n * 12) + 'px', height: '12px' }"></div>
                </div>
                <div class="timeline-cells">
                  <div class="skel pill-skel" :style="{ left: (n * 4) + '%', width: (8 + n * 3) + '%' }"></div>
                </div>
              </div>
            </template>

            <!-- Empty State -->
            <div v-else-if="filteredBugs.length === 0" class="empty-state">
              No bugs found for the selected filters.
            </div>

            <!-- Bug Rows -->
            <template v-else>
              <div
                v-for="bug in filteredBugs"
                :key="bug.id"
                class="gantt-row"
              >
                <!-- Task Label -->
                <div class="task-name-col" @click="navigateToBug(bug.id)">
                  <span class="bug-id">#{{ bug.id }}</span>
                  <span class="bug-title">{{ bug.title }}</span>
                </div>

                <!-- Timeline Bar Area -->
                <div class="timeline-cells">
                  <div class="dot-grid"></div>
                  <div
                    v-if="getBarStyle(bug)"
                    class="gantt-pill-bar"
                    :style="getBarStyle(bug)"
                    @click="navigateToBug(bug.id)"
                    :title="`#${bug.id} — ${bug.title} (${bug.severity})`"
                  >
                    <span class="status-label">{{ bug.status }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>

        </div>
      </div>

      <!-- Legend -->
      <div class="gantt-legend">
        <span class="legend-title">Severity:</span>
        <div v-for="(color, label) in severityColors" :key="label" class="legend-item">
          <span class="legend-dot" :style="{ background: color }"></span>
          {{ label }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();
const bugs = ref([]);
const loading = ref(true);
const filterSeverity = ref('');
const filterStatus = ref('');

const severityColors = {
  Low: '#4A6FE8',
  Medium: '#FACC15',
  High: '#FB923C',
  Critical: '#F87171',
};

// Generate array of last 14 days
const timelineDays = computed(() => {
  const today = new Date();
  const days = [];
  for (let i = 13; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(today.getDate() - i);
    d.setHours(0, 0, 0, 0);
    days.push(d);
  }
  return days;
});

const filteredBugs = computed(() => {
  return bugs.value.filter(bug => {
    if (filterSeverity.value && bug.severity !== filterSeverity.value) return false;
    if (filterStatus.value && bug.status !== filterStatus.value) return false;
    return true;
  });
});

const isToday = (date) => {
  const today = new Date();
  return (
    date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear()
  );
};

const getBarStyle = (bug) => {
  const start = new Date(bug.created_at);
  start.setHours(0, 0, 0, 0);

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const windowStart = new Date(today);
  windowStart.setDate(today.getDate() - 13);

  const offsetDays = Math.floor((start - windowStart) / (1000 * 60 * 60 * 24));
  const lifeSpanDays = Math.max(1, Math.floor((today - start) / (1000 * 60 * 60 * 24)) + 1);

  const left = Math.max(0, offsetDays);
  const width = Math.min(14 - left, lifeSpanDays - Math.max(0, left - offsetDays));

  if (left >= 14 || width <= 0) return null;

  return {
    left: `${((left / 14) * 100).toFixed(2)}%`,
    width: `${((width / 14) * 100).toFixed(2)}%`,
    backgroundColor: severityColors[bug.severity] || '#4A6FE8',
  };
};

const navigateToBug = (id) => {
  router.push(`/bugs/${id}`);
};

const loadData = async () => {
  loading.value = true;
  try {
    const response = await api.get('/bugs');
    bugs.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load bugs:', error);
    bugs.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
/* ── Page ── */
.gantt-page {
  padding: 1.5rem;
  position: relative;
}

/* ── Loading Overlay ── */
.loading-overlay {
  position: absolute;
  inset: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border-radius: 12px;
}
[data-theme='dark'] .loading-overlay {
  background: rgba(17, 24, 39, 0.75);
}
.loading-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid var(--border, #e5e7eb);
  border-top-color: #1e40af;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.loading-text {
  font-size: 0.8rem;
  color: var(--text-secondary, #6b7280);
}

/* ── Widget Shell ── */
.gantt-widget {
  border-radius: 12px;
  border: 1px solid var(--border, #e5e7eb);
  overflow: hidden;
  background: var(--surface, #ffffff);
  display: flex;
  flex-direction: column;
}

/* ── Title Bar ── */
.gantt-title-bar {
  background: #1e3a8a;
  color: #ffffff;
  padding: 12px 18px;
  font-size: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* ── Controls ── */
.gantt-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  border-bottom: 1px solid var(--border, #e5e7eb);
  background: var(--bg, #f9fafb);
  flex-wrap: wrap;
}
.gantt-controls label {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}
.gantt-controls select {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid var(--border, #e5e7eb);
  background: var(--surface, #ffffff);
  color: var(--text, #111827);
  cursor: pointer;
  outline: none;
}
.gantt-controls select:focus {
  border-color: #1e40af;
}

/* ── Scroll Wrapper ── */
.gantt-scroll-wrapper {
  overflow-x: auto;
  flex: 1;
}
.gantt-inner {
  min-width: 700px;
}

/* ── Header Row ── */
.gantt-header-row {
  display: flex;
  position: sticky;
  top: 0;
  z-index: 20;
  background: var(--surface, #ffffff);
  border-bottom: 2px solid var(--border, #e5e7eb);
}

.tasks-label-col {
  width: 220px;
  flex-shrink: 0;
  background: #065f46;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  border-right: 1px solid rgba(255, 255, 255, 0.15);
}

.weeks-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.weeks-row {
  display: flex;
}
.week-cell {
  flex: 1;
  background: #f97316;
  color: #ffffff;
  text-align: center;
  padding: 4px 0;
  font-size: 11px;
  font-weight: 700;
  border-right: 1px solid rgba(0, 0, 0, 0.12);
}
.week-cell:last-child {
  border-right: none;
}
.days-row {
  display: flex;
  background: var(--bg, #f9fafb);
}
.day-cell {
  flex: 1;
  text-align: center;
  padding: 5px 0;
  font-size: 10px;
  font-weight: 600;
  color: var(--text-secondary, #6b7280);
  border-right: 1px solid var(--border-light, #f3f4f6);
}
.day-cell:last-child {
  border-right: none;
}
.today-cell {
  color: #1e40af;
  font-weight: 800;
  background: rgba(30, 64, 175, 0.06);
}

/* ── Body ── */
.gantt-body {}
.gantt-row {
  display: flex;
  border-bottom: 1px solid var(--border-light, #f3f4f6);
  min-height: 48px;
  transition: background 0.15s;
}
.gantt-row:last-child { border-bottom: none; }
.gantt-row:hover { background: var(--bg, #f9fafb); }

/* ── Task Label Column ── */
.task-name-col {
  width: 220px;
  flex-shrink: 0;
  padding: 0 12px;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 5px;
  overflow: hidden;
  cursor: pointer;
  border-right: 1px solid var(--border-light, #f3f4f6);
  transition: color 0.15s;
}
.task-name-col:hover .bug-title {
  color: #1e40af;
}
.bug-id {
  color: var(--text-secondary, #9ca3af);
  font-size: 11px;
  flex-shrink: 0;
}
.bug-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text, #111827);
}

/* ── Timeline Cells ── */
.timeline-cells {
  flex: 1;
  position: relative;
  min-height: 48px;
}
.dot-grid {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle, var(--border-light, #d1d5db) 1px, transparent 1px);
  background-size: calc(100% / 14) 10px;
  pointer-events: none;
  opacity: 0.5;
}

/* ── Pill Bars ── */
.gantt-pill-bar {
  position: absolute;
  top: 20%;
  height: 60%;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 5;
  min-width: 14px;
  transition: transform 0.15s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}
.gantt-pill-bar:hover {
  transform: scaleY(1.15);
  z-index: 10;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
.status-label {
  font-size: 9px;
  font-weight: 800;
  color: #ffffff;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
  padding: 0 10px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* ── Skeleton ── */
.skeleton-row {
  pointer-events: none;
}
.skel {
  border-radius: 4px;
  background: var(--border-light, #e5e7eb);
  animation: pulse 1.4s ease-in-out infinite;
}
.pill-skel {
  position: absolute;
  top: 25%;
  height: 50%;
  border-radius: 20px;
}
@keyframes pulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.9; }
}

/* ── Empty State ── */
.empty-state {
  padding: 48px;
  text-align: center;
  font-size: 13px;
  color: var(--text-secondary, #9ca3af);
}

/* ── Legend ── */
.gantt-legend {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 16px;
  border-top: 1px solid var(--border-light, #f3f4f6);
  background: var(--bg, #f9fafb);
  flex-wrap: wrap;
}
.legend-title {
  font-size: 11px;
  color: var(--text-secondary, #6b7280);
  margin-right: 4px;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-secondary, #6b7280);
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Dark Mode ── */
[data-theme='dark'] .gantt-widget {
  background: #111827;
  border-color: #374151;
}
[data-theme='dark'] .gantt-row:hover { background: #1f2937; }
[data-theme='dark'] .gantt-header-row { background: #111827; }
[data-theme='dark'] .gantt-controls { background: #1f2937; border-color: #374151; }
[data-theme='dark'] .gantt-controls select { background: #111827; border-color: #374151; color: #f9fafb; }
[data-theme='dark'] .gantt-legend { background: #1f2937; border-color: #374151; }
[data-theme='dark'] .days-row { background: #1f2937; }
[data-theme='dark'] .day-cell { border-color: #374151; color: #9ca3af; }
[data-theme='dark'] .task-name-col { border-color: #374151; }
[data-theme='dark'] .bug-title { color: #f3f4f6; }
[data-theme='dark'] .tasks-label-col { background: #064e3b; }
[data-theme='dark'] .skel { background: #374151; }
[data-theme='dark'] .today-cell { background: rgba(59, 130, 246, 0.1); }
</style>