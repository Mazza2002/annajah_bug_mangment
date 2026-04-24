<template>
  <div class="new-bug-page">
    <div class="page-header mb-4">
      <router-link to="/bugs" class="text-secondary" style="font-size: 0.875rem;">← Back to bugs</router-link>
      <h1 style="color: var(--primary); margin-top: 0.5rem;">Report New Bug</h1>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
      <div class="left-col">
        <div class="card">
          <form @submit.prevent="handleSubmit">
            <div class="form-group">
              <label class="form-label">Project</label>
              <select v-model="form.project_id" class="form-input" required @change="checkDuplicates">
                <option value="">Select Project</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Title</label>
              <input 
                v-model="form.title" 
                type="text" 
                class="form-input" 
                placeholder="Brief summary of the issue" 
                required
                @blur="onTitleBlur"
              >
              <div v-if="duplicates.length > 0" class="mt-2 p-2" style="background: var(--primary-light); border-radius: 6px;">
                <p style="font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem;">
                  Potential Duplicates Found:
                </p>
                <ul style="font-size: 0.75rem; padding-left: 1rem;">
                  <li v-for="d in duplicates" :key="d.id">
                    <router-link :to="`/bugs/${d.id}`" target="_blank">#{{ d.id }} - {{ d.title }}</router-link>
                  </li>
                </ul>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea 
                v-model="form.description" 
                class="form-input" 
                rows="5" 
                placeholder="Detailed description of what's happening" 
                required
                @blur="suggestSeverity"
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Steps to Reproduce</label>
              <textarea 
                v-model="form.steps_to_reproduce" 
                class="form-input" 
                rows="4" 
                placeholder="1. Go to...&#10;2. Click on...&#10;3. Expect..."
              ></textarea>
            </div>

            <div class="flex gap-4">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Reporting...' : 'Create Bug Report' }}
              </button>
              <button type="button" class="btn btn-secondary" @click="$router.push('/bugs')">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <div class="right-col">
        <div class="card mb-4">
          <h3 class="mb-4">Metadata</h3>
          
          <div class="form-group">
            <label class="form-label">Severity</label>
            <div class="flex items-center gap-2">
              <select v-model="form.severity" class="form-input">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
              <span v-if="suggestingSeverity" style="font-size: 0.75rem; color: var(--primary);">AI Suggesting...</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Category</label>
            <select v-model="form.category_id" class="form-input">
              <option value="">None</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Environment</label>
            <input v-model="form.environment" type="text" class="form-input" placeholder="e.g. Chrome, Production, iOS">
          </div>
        </div>

        <div class="card">
          <h3 class="mb-4">Tags</h3>
          <div class="flex flex-wrap gap-2">
            <label v-for="tag in tags" :key="tag.id" class="tag-selector" :style="{ '--tag-color': tag.color }">
              <input type="checkbox" :value="tag.id" v-model="form.tags">
              <span>{{ tag.name }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const loading = ref(false);
const suggestingSeverity = ref(false);

const projects = ref([]);
const categories = ref([]);
const tags = ref([]);
const duplicates = ref([]);

const form = reactive({
  project_id: '',
  title: '',
  description: '',
  steps_to_reproduce: '',
  severity: 'Medium',
  category_id: '',
  environment: '',
  tags: []
});

const loadData = async () => {
  try {
    const [pRes, cRes, tRes] = await Promise.all([
      api.get('/projects'),
      api.get('/categories'),
      api.get('/tags')
    ]);
    projects.value = pRes.data.data || pRes.data;
    categories.value = cRes.data;
    tags.value = tRes.data;
  } catch (error) {}
};

const onTitleBlur = () => {
  checkDuplicates();
  suggestSeverity();
};

const checkDuplicates = async () => {
  if (!form.title || !form.project_id) return;
  try {
    const response = await api.post('/bugs/check-duplicates', {
      title: form.title,
      project_id: form.project_id
    });
    duplicates.value = response.data;
  } catch (error) {}
};

const suggestSeverity = async () => {
  if (!form.title || !form.description) return;
  suggestingSeverity.value = true;
  try {
    const response = await api.post('/bugs/suggest-severity', {
      title: form.title,
      description: form.description
    });
    form.severity = response.data.severity;
  } catch (error) {}
  finally {
    suggestingSeverity.value = false;
  }
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    const response = await api.post('/bugs', form);
    router.push(`/bugs/${response.data.id}`);
  } catch (error) {
    alert('Failed to report bug. Please check your inputs.');
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
</script>

<style scoped>
.tag-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--border);
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.tag-selector:has(input:checked) {
  background-color: var(--tag-color);
  color: white;
  border-color: var(--tag-color);
}

.tag-selector input {
  display: none;
}
</style>
