<template>
  <div class="edit-bug-page">
    <div class="page-header mb-4">
      <router-link :to="`/bugs/${$route.params.id}`" class="text-secondary" style="font-size: 0.875rem;">← Back to details</router-link>
      <h1 style="color: var(--primary); margin-top: 0.5rem;">Edit Bug #{{ $route.params.id }}</h1>
    </div>

    <div v-if="loadingData" class="text-center py-8">Loading bug data...</div>
    <div v-else class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
      <div class="left-col">
        <div class="card">
          <form @submit.prevent="handleUpdate">
            <div class="form-group">
              <label class="form-label">Project</label>
              <select v-model="form.project_id" class="form-input" required>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Title</label>
              <input v-model="form.title" type="text" class="form-input" required>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea v-model="form.description" class="form-input" rows="5" required></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Steps to Reproduce</label>
              <textarea v-model="form.steps_to_reproduce" class="form-input" rows="4"></textarea>
            </div>

            <div class="flex gap-4">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'Saving Changes...' : 'Save Changes' }}
              </button>
              <button type="button" class="btn btn-secondary" @click="$router.push(`/bugs/${$route.params.id}`)">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <div class="right-col">
        <div class="card">
          <h3 class="mb-4">Metadata</h3>
          <div class="form-group">
            <label class="form-label">Severity</label>
            <select v-model="form.severity" class="form-input">
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
              <option value="Critical">Critical</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select v-model="form.category_id" class="form-input">
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Environment</label>
            <input v-model="form.environment" type="text" class="form-input">
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';

const route = useRoute();
const router = useRouter();
const loadingData = ref(true);
const saving = ref(false);

const projects = ref([]);
const categories = ref([]);

const form = reactive({
  project_id: '',
  title: '',
  description: '',
  steps_to_reproduce: '',
  severity: '',
  category_id: '',
  environment: ''
});

const loadData = async () => {
  try {
    const [pRes, cRes, bRes] = await Promise.all([
      api.get('/projects'),
      api.get('/categories'),
      api.get(`/bugs/${route.params.id}`)
    ]);
    projects.value = pRes.data.data || pRes.data;
    categories.value = cRes.data;
    
    const bug = bRes.data;
    form.project_id = bug.project_id;
    form.title = bug.title;
    form.description = bug.description;
    form.steps_to_reproduce = bug.steps_to_reproduce;
    form.severity = bug.severity;
    form.category_id = bug.category_id;
    form.environment = bug.environment;
  } catch (error) {
    alert('Failed to load bug data.');
    router.push('/bugs');
  } finally {
    loadingData.value = false;
  }
};

const handleUpdate = async () => {
  saving.value = true;
  try {
    await api.put(`/bugs/${route.params.id}`, form);
    router.push(`/bugs/${route.params.id}`);
  } catch (error) {
    alert('Failed to save changes.');
  } finally {
    saving.value = false;
  }
};

onMounted(loadData);
</script>
