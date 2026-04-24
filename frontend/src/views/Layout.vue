<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2 style="color: var(--primary);">Annajah</h2>
      </div>
      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item" active-class="active">
          <LayoutDashboardIcon class="w-5 h-5" /> Dashboard
        </router-link>
        <router-link to="/bugs" class="nav-item" active-class="active">
          <BugIcon class="w-5 h-5" /> Bug List
        </router-link>
        <router-link to="/kanban" class="nav-item" active-class="active">
          <ColumnsIcon class="w-5 h-5" /> Kanban Board
        </router-link>
        <router-link to="/projects" class="nav-item" active-class="active">
          <FolderIcon class="w-5 h-5" /> Projects
        </router-link>
        <router-link to="/calendar" class="nav-item" active-class="active">
          <CalendarIcon class="w-5 h-5" /> Calendar
        </router-link>
        <router-link to="/gantt" class="nav-item" active-class="active">
          <BarChartHorizontalIcon class="w-5 h-5" /> Timeline (Gantt)
        </router-link>
        <router-link v-if="authStore.isAdmin" to="/users" class="nav-item" active-class="active">
          <UsersIcon class="w-5 h-5" /> Users
        </router-link>
        <router-link v-if="authStore.isAdmin" to="/analytics" class="nav-item" active-class="active">
          <PieChartIcon class="w-5 h-5" /> Analytics
        </router-link>
        <router-link to="/profile" class="nav-item" active-class="active">
          <UserIcon class="w-5 h-5" /> My Profile
        </router-link>
      </nav>
      <div class="sidebar-footer">
        <div class="user-info flex items-center gap-3">
          <div class="avatar-sm">{{ authStore.user?.name[0] }}</div>
          <div>
            <div style="font-weight: 600; font-size: 0.875rem;">{{ authStore.user?.name }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ authStore.user?.role?.name }}</div>
          </div>
        </div>
        <button @click="logout" class="btn btn-secondary w-full mt-4 flex items-center justify-center gap-2">
          <LogOutIcon class="w-4 h-4" /> Logout
        </button>
      </div>
    </aside>
    
    <main class="main-content">
      <header class="topbar">
        <div class="search-bar">
          <input type="text" class="form-input" placeholder="Search bugs (Cmd+K)..." style="max-width: 300px; padding-left: 1rem;">
        </div>
        <div class="topbar-actions flex items-center gap-4">
          <div class="notifications-container" style="position:relative;">
            <button class="icon-btn relative" @click="showNotifs = !showNotifs">
              <BellIcon class="w-6 h-6" />
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount }}</span>
            </button>
            <div v-if="showNotifs" class="notif-dropdown card shadow-xl">
              <div class="flex justify-between items-center mb-4 px-2">
                <span style="font-weight: 700; font-size: 1rem;">Notifications</span>
                <button @click="markAllRead" style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">Mark all read</button>
              </div>
              <div v-if="notifications.length === 0" class="p-8 text-center text-secondary">
                <BellIcon class="w-8 h-8 mx-auto mb-2 opacity-20" />
                <p style="font-size: 0.875rem;">All caught up!</p>
              </div>
              <div v-for="n in notifications" :key="n.id" class="notif-item" :class="{ unread: !n.read_at }" @click="markRead(n.id)">
                <div style="font-size: 0.875rem; font-weight: 500;">{{ n.data?.message || 'Activity update' }}</div>
                <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 2px;">{{ new Date(n.created_at).toLocaleString() }}</div>
              </div>
            </div>
          </div>
          <button class="btn btn-primary shadow-sm" @click="$router.push('/bugs/new')">+ Report Bug</button>
        </div>
      </header>
      
      <div class="page-content">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import { 
  LayoutDashboardIcon, 
  BugIcon, 
  ColumnsIcon, 
  FolderIcon, 
  CalendarIcon, 
  BarChartHorizontalIcon, 
  UsersIcon, 
  PieChartIcon, 
  UserIcon,
  LogOutIcon,
  BellIcon
} from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const showNotifs = ref(false);
const notifications = ref([]);
const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length);

const fetchNotifications = async () => {
  try {
    const response = await api.get('/notifications');
    notifications.value = response.data;
  } catch (error) {}
};

const markRead = async (id) => {
  try {
    await api.patch(`/notifications/${id}/read`);
    fetchNotifications();
  } catch (error) {}
};

const markAllRead = async () => {
  try {
    await api.patch('/notifications/read-all');
    fetchNotifications();
  } catch (error) {}
};

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

onMounted(() => {
  if (!authStore.user) {
    authStore.fetchUser();
  }
  fetchNotifications();
  // Poll for notifications every 30 seconds
  setInterval(fetchNotifications, 30000);
});
</script>

<style scoped>
.app-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 250px;
  background-color: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border);
}

.sidebar-nav {
  padding: 1.5rem 1rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav-item {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  color: var(--text);
  font-weight: 500;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background-color: var(--bg);
}

.nav-item.active {
  background-color: var(--primary-light);
  color: var(--primary);
  border-left-color: var(--primary);
}

.sidebar-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--border);
}

.main-content {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.topbar {
  height: 64px;
  background-color: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
}

.page-content {
  flex-grow: 1;
  overflow-y: auto;
  padding: 2rem;
}
.avatar-sm {
  width: 36px;
  height: 36px;
  background-color: var(--primary-light);
  color: var(--primary);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}
</style>
