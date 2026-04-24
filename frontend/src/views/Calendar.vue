<template>
  <div class="calendar-page">
    <div class="page-header mb-6">
      <h1 style="color: var(--primary);">Bug Calendar</h1>
      <p>View bug reports and milestones over time.</p>
    </div>

    <div class="card">
      <!-- Legend -->
      <div class="calendar-legend">
        <span class="legend-title">Status:</span>
        <div v-for="(color, label) in statusColors" :key="label" class="legend-item">
          <span class="legend-dot" :style="{ background: color }"></span>
          {{ label }}
        </div>
      </div>

      <FullCalendar :options="calendarOptions" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import api from '../services/api';
import { useRouter } from 'vue-router';

const router = useRouter();
const events = ref([]);

const statusColors = {
  'Open':        '#f87171',   // red
  'In Progress': '#fb923c',   // orange
  'In Review':   '#a78bfa',   // purple
  'Resolved':    '#34d399',   // green
  'Closed':      '#9ca3af',   // gray
};

const getStatusColor = (status) => {
  return statusColors[status] || '#60a5fa';
};

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,dayGridWeek',
  },
  events: events,
  eventClick: (info) => {
    router.push(`/bugs/${info.event.id}`);
  },
  eventDidMount: (info) => {
    info.el.setAttribute('title', `#${info.event.id} — ${info.event.extendedProps.status}`);
  },
  height: '700px',
});

const loadBugs = async () => {
  try {
    const response = await api.get('/bugs');
    const bugs = response.data.data || response.data;

    events.value = bugs.map(bug => ({
      id: bug.id,
      title: `#${bug.id} ${bug.title}`,
      start: bug.created_at,
      backgroundColor: getStatusColor(bug.status),
      borderColor: getStatusColor(bug.status),
      textColor: getTextColor(bug.status),
      allDay: true,
      extendedProps: {
        status: bug.status,
        severity: bug.severity,
      },
    }));
  } catch (error) {
    console.error('Failed to load bugs:', error);
  }
};

// Keep text readable on lighter backgrounds
const getTextColor = (status) => {
  return status === 'In Review' ? '#ffffff' : '#ffffff';
};

onMounted(loadBugs);
</script>

<style>
.calendar-legend {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 4px 16px;
  flex-wrap: wrap;
}
.calendar-legend .legend-title {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
  font-weight: 600;
}
.calendar-legend .legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}
.calendar-legend .legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.fc .fc-button-primary {
  background-color: var(--primary);
  border-color: var(--primary);
}
.fc .fc-button-primary:hover {
  background-color: var(--primary-dark);
}
.fc .fc-daygrid-event {
  cursor: pointer;
  border-radius: 4px;
  padding: 2px 5px;
  font-size: 11px;
  font-weight: 500;
  border: none !important;
}
.fc .fc-daygrid-event:hover {
  filter: brightness(0.9);
}
.fc .fc-toolbar-title {
  color: var(--text);
  font-size: 1.25rem !important;
}
.fc .fc-day-today {
  background: rgba(var(--primary-rgb, 59,130,246), 0.06) !important;
}
</style>