<template>
  <div class="projects-page">
    <div class="page-header flex justify-between items-center mb-4">
      <div>
        <h1 style="color: var(--primary);">Projects</h1>
        <p>Manage your software projects and track their bug counts.</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">+ Create Project</button>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
      <div v-for="project in projects" :key="project.id" class="card project-card">
        <div class="flex justify-between items-start mb-4">
          <h2 style="font-size: 1.25rem;">{{ project.name }}</h2>
          <span class="badge" style="background: var(--primary-light); color: var(--primary);">
            {{ project.bugs_count || 0 }} Bugs
          </span>
        </div>
        <p style="font-size: 0.875rem; min-height: 3rem;">{{ project.description || 'No description provided.' }}</p>
        <div class="flex justify-between items-center mt-4 pt-4" style="border-top: 1px solid var(--border);">
          <router-link :to="`/bugs?project_id=${project.id}`" style="font-size: 0.875rem; font-weight: 500;">View Bugs →</router-link>
          <div class="flex gap-2">
            <button class="btn btn-secondary btn-sm" @click="openEditModal(project)">Edit</button>
            <button class="btn btn-secondary btn-sm" @click="handleDelete(project.id)" style="color: var(--severity-critical);">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="projects.length === 0" class="text-center py-8">
      <p class="text-secondary">No projects found. Create your first project to start tracking bugs!</p>
    </div>

    <!-- Create Project Modal -->
    <div v-if="showCreateModal" class="modal-overlay">
      <div class="card modal-content" style="max-width: 500px; width: 90%;">
        <h2 class="mb-4">Create New Project</h2>
        <form @submit.prevent="createProject">
          <div class="form-group">
            <label class="form-label">Project Name</label>
            <input v-model="newProject.name" type="text" class="form-input" placeholder="e.g. Mobile App v2" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="newProject.description" class="form-input" rows="3" placeholder="Briefly describe the project scope"></textarea>
          </div>
          <div class="flex gap-2 mt-6">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? 'Creating...' : 'Create Project' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Project Modal -->
    <div v-if="showEditModal" class="modal-overlay">
      <div class="card modal-content" style="max-width: 500px; width: 90%;">
        <h2 class="mb-4">Edit Project</h2>
        <form @submit.prevent="updateProject">
          <div class="form-group">
            <label class="form-label">Project Name</label>
            <input v-model="editingProject.name" type="text" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="editingProject.description" class="form-input" rows="3"></textarea>
          </div>
          <div class="flex gap-2 mt-6">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save Changes' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const projects = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loading = ref(false);
const newProject = ref({ name: '', description: '' });
const editingProject = ref({ id: null, name: '', description: '' });

const loadProjects = async () => {
  try {
    const response = await api.get('/projects');
    projects.value = response.data;
  } catch (error) {}
};

const createProject = async () => {
  loading.value = true;
  try {
    await api.post('/projects', newProject.value);
    newProject.value = { name: '', description: '' };
    showCreateModal.value = false;
    await loadProjects();
  } catch (error) {
    alert('Failed to create project.');
  } finally {
    loading.value = false;
  }
};

const openEditModal = (project) => {
  editingProject.value = { ...project };
  showEditModal.value = true;
};

const updateProject = async () => {
  loading.value = true;
  try {
    await api.put(`/projects/${editingProject.value.id}`, editingProject.value);
    showEditModal.value = false;
    await loadProjects();
  } catch (error) {
    alert('Failed to update project.');
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id) => {
  if (!confirm('Are you sure you want to delete this project and all its associated bugs?')) return;
  try {
    await api.delete(`/projects/${id}`);
    await loadProjects();
  } catch (error) {}
};

onMounted(loadProjects);
</script>

<style scoped>
.project-card {
  display: flex;
  flex-direction: column;
  transition: transform 0.2s;
}
.project-card:hover {
  transform: translateY(-4px);
  border-color: var(--primary);
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
