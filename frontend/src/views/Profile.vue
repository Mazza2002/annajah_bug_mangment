<template>
  <div class="profile-page">
    <BaseLoading :active="loading" message="Saving profile changes..." />
    <BaseAlert v-model="alert.show" :type="alert.type" :title="alert.title" :message="alert.message" />

    <h1 style="color: var(--primary); margin-bottom: 2rem;">My Profile</h1>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
      <!-- Profile Info -->
      <div class="card">
        <h3 class="mb-4">Account Information</h3>
        <form @submit.prevent="updateProfile">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input v-model="profileForm.name" type="text" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input v-model="profileForm.email" type="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Notification Preferences</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2" style="font-size: 0.875rem;">
                <input type="checkbox" v-model="profileForm.notification_prefs.email"> Email
              </label>
              <label class="flex items-center gap-2" style="font-size: 0.875rem;">
                <input type="checkbox" v-model="profileForm.notification_prefs.in_app"> In-App
              </label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-4" :disabled="loading">
            Update Profile
          </button>
        </form>
      </div>

      <!-- Sessions -->
      <div class="card">
        <h3 class="mb-4">Active Sessions</h3>
        <p class="text-secondary mb-4" style="font-size: 0.875rem;">Manage devices that are currently logged into your account.</p>
        
        <div class="flex flex-col gap-3">
          <div v-for="session in sessions" :key="session.id" class="session-item shadow-sm">
            <div class="flex items-center gap-3">
              <div class="device-icon">
                <MonitorIcon v-if="session.name.toLowerCase().includes('windows') || session.name.toLowerCase().includes('mac')" class="w-5 h-5" />
                <SmartphoneIcon v-else class="w-5 h-5" />
              </div>
              <div>
                <div style="font-weight: 700; font-size: 0.875rem;">{{ session.name }}</div>
                <div style="font-size: 0.75rem; color: var(--text-secondary);">Last used: {{ new Date(session.last_used_at || session.created_at).toLocaleString() }}</div>
              </div>
            </div>
            <button 
              class="btn btn-secondary btn-sm" 
              style="color: var(--severity-critical); border-color: transparent;"
              @click="confirmRevoke(session)"
            >
              Revoke
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Revoke Confirmation -->
    <BaseModal :show="showRevokeConfirm" title="Revoke Session" @close="showRevokeConfirm = false" width="400px">
      <p>Are you sure you want to end this session? You will be logged out on that device immediately.</p>
      <template #footer>
        <button class="btn btn-primary" style="background: var(--severity-critical); border-color: var(--severity-critical);" @click="handleRevoke">End Session</button>
        <button class="btn btn-secondary" @click="showRevokeConfirm = false">Cancel</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';
import BaseLoading from '../components/BaseLoading.vue';
import BaseAlert from '../components/BaseAlert.vue';
import BaseModal from '../components/BaseModal.vue';
import { MonitorIcon, SmartphoneIcon } from 'lucide-vue-next';

const authStore = useAuthStore();
const loading = ref(true);
const sessions = ref([]);
const alert = reactive({ show: false, type: 'info', title: '', message: '' });

const showRevokeConfirm = ref(false);
const sessionToRevoke = ref(null);

const profileForm = reactive({
  name: '',
  email: '',
  notification_prefs: { email: true, in_app: true }
});

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
    const [uRes, sRes] = await Promise.all([
      api.get('/auth/user'),
      api.get('/auth/sessions')
    ]);
    const user = uRes.data.user;
    profileForm.name = user.name;
    profileForm.email = user.email;
    profileForm.notification_prefs = user.notification_prefs || { email: true, in_app: true };
    
    sessions.value = sRes.data.tokens || [];
  } catch (error) {
    showAlert('error', 'Error', 'Failed to load profile data.');
  } finally {
    loading.value = false;
  }
};

const updateProfile = async () => {
  loading.value = true;
  try {
    const response = await api.put('/auth/profile', profileForm);
    authStore.user = response.data.user;
    showAlert('success', 'Success', 'Profile updated successfully.');
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to update profile.');
  } finally {
    loading.value = false;
  }
};

const confirmRevoke = (session) => {
  sessionToRevoke.value = session;
  showRevokeConfirm.value = true;
};

const handleRevoke = async () => {
  loading.value = true;
  try {
    await api.delete(`/auth/sessions/${sessionToRevoke.value.id}`);
    showRevokeConfirm.value = false;
    showAlert('success', 'Revoked', 'Session has been ended.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to revoke session.');
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
.session-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  border: 1px solid var(--border-light);
}
.device-icon {
  width: 40px; height: 40px; background: var(--bg); border-radius: 10px;
  display: flex; align-items: center; justify-content: center; color: var(--primary);
}
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
</style>
