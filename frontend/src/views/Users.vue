<template>
  <div class="users-page">
    <BaseLoading :active="loading" message="Processing user data..." />
    <BaseAlert v-model="alert.show" :type="alert.type" :title="alert.title" :message="alert.message" />

    <div class="page-header flex justify-between items-center mb-4">
      <div>
        <h1 style="color: var(--primary);">User Management</h1>
        <p>Manage your team, invite members, or add them directly.</p>
      </div>
      <button class="btn btn-primary" @click="showAddModal = true">+ Add User</button>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
      <table class="w-full text-left">
        <thead>
          <tr style="background: var(--bg); border-bottom: 1px solid var(--border);">
            <th class="p-4">Name</th>
            <th class="p-4">Email</th>
            <th class="p-4">Role</th>
            <th class="p-4">Joined</th>
            <th class="p-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" class="table-row">
            <td class="p-4">
              <div class="flex items-center gap-2">
                <div class="avatar-circle">{{ user.name[0] }}</div>
                <span style="font-weight: 500;">{{ user.name }}</span>
              </div>
            </td>
            <td class="p-4 text-secondary">{{ user.email }}</td>
            <td class="p-4">
              <select 
                :value="user.role_id" 
                @change="handleRoleChange(user.id, $event.target.value)"
                class="form-input"
                style="padding: 0.25rem 0.5rem; width: auto; font-size: 0.875rem;"
              >
                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
              </select>
            </td>
            <td class="p-4 text-secondary">{{ new Date(user.created_at).toLocaleDateString() }}</td>
            <td class="p-4">
              <button 
                class="text-danger" 
                style="background:none; border:none; cursor:pointer;"
                @click="confirmDelete(user)"
                :disabled="user.id === authStore.user?.id"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Invite Modal -->
    <BaseModal :show="showAddModal" :title="activeTab === 'add' ? 'Add New User' : 'Invite User'" @close="showAddModal = false">
      <div class="flex gap-4 mb-6 border-b" style="border-color: var(--border);">
        <button 
          @click="activeTab = 'add'" 
          class="pb-2 px-2" 
          :style="activeTab === 'add' ? 'border-bottom: 2px solid var(--primary); color: var(--primary);' : 'color: var(--text-secondary);'"
        >
          Direct Add
        </button>
        <button 
          @click="activeTab = 'invite'" 
          class="pb-2 px-2" 
          :style="activeTab === 'invite' ? 'border-bottom: 2px solid var(--primary); color: var(--primary);' : 'color: var(--text-secondary);'"
        >
          Send Invite
        </button>
      </div>

      <form v-if="activeTab === 'add'" @submit.prevent="addUser">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input v-model="addForm.name" type="text" class="form-input" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input v-model="addForm.email" type="email" class="form-input" placeholder="john@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input v-model="addForm.password" type="password" class="form-input" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select v-model="addForm.role_id" class="form-input" required>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
        </div>
        <div class="flex gap-2 mt-6 justify-end">
          <button type="submit" class="btn btn-primary">Create User</button>
          <button type="button" class="btn btn-secondary" @click="showAddModal = false">Cancel</button>
        </div>
      </form>

      <form v-else @submit.prevent="inviteUser">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input v-model="inviteForm.email" type="email" class="form-input" placeholder="team@company.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select v-model="inviteForm.role_id" class="form-input" required>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
        </div>
        <div class="flex gap-2 mt-6 justify-end">
          <button type="submit" class="btn btn-primary">Send Invitation</button>
          <button type="button" class="btn btn-secondary" @click="showAddModal = false">Cancel</button>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal :show="showDeleteConfirm" title="Delete User" @close="showDeleteConfirm = false" width="400px">
      <p>Are you sure you want to remove <strong>{{ userToDelete?.name }}</strong>? This will revoke their access to the system.</p>
      <template #footer>
        <button class="btn btn-primary" style="background: var(--severity-critical); border-color: var(--severity-critical);" @click="handleDelete">Yes, Remove</button>
        <button class="btn btn-secondary" @click="showDeleteConfirm = false">Cancel</button>
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

const authStore = useAuthStore();
const users = ref([]);
const roles = ref([]);
const loading = ref(true);
const alert = reactive({ show: false, type: 'info', title: '', message: '' });

const showAddModal = ref(false);
const activeTab = ref('add');
const showDeleteConfirm = ref(false);
const userToDelete = ref(null);

const addForm = ref({ name: '', email: '', password: '', role_id: 4 });
const inviteForm = ref({ email: '', role_id: 4 });

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
    const [uRes, rRes] = await Promise.all([
      api.get('/users'),
      api.get('/auth/roles')
    ]);
    users.value = uRes.data.data || uRes.data;
    roles.value = rRes.data;
  } catch (error) {
    showAlert('error', 'Error', 'Failed to load user data.');
  } finally {
    loading.value = false;
  }
};

const addUser = async () => {
  loading.value = true;
  try {
    await api.post('/v1/users', addForm.value); // Wait, prefix is usually handled by api.js
    // Let's use relative path
    await api.post('/users', addForm.value); 
    showAddModal.value = false;
    showAlert('success', 'Created', 'User created successfully.');
    addForm.value = { name: '', email: '', password: '', role_id: 4 };
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', error.response?.data?.message || 'Failed to create user.');
  } finally {
    loading.value = false;
  }
};

const inviteUser = async () => {
  loading.value = true;
  try {
    await api.post('/users/invite', inviteForm.value);
    showAddModal.value = false;
    showAlert('success', 'Invited', 'Invitation sent successfully.');
    inviteForm.value = { email: '', role_id: 4 };
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to invite user.');
  } finally {
    loading.value = false;
  }
};

const handleRoleChange = async (userId, roleId) => {
  loading.value = true;
  try {
    await api.put(`/users/${userId}/role`, { role_id: roleId });
    showAlert('success', 'Updated', 'User role updated.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to change role.');
  } finally {
    loading.value = false;
  }
};

const confirmDelete = (user) => {
  userToDelete.value = user;
  showDeleteConfirm.value = true;
};

const handleDelete = async () => {
  loading.value = true;
  try {
    await api.delete(`/users/${userToDelete.value.id}`);
    showDeleteConfirm.value = false;
    showAlert('success', 'Removed', 'User removed.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Could not delete user.');
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
.table-row { border-bottom: 1px solid var(--border); }
.p-4 { padding: 1rem; }
.avatar-circle {
  width: 32px; height: 32px; background: var(--primary-light); color: var(--primary);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.875rem;
}
.text-danger { color: var(--severity-critical); }
</style>
