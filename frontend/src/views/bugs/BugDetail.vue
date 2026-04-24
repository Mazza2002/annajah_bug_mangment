<template>
  <div v-if="bug" class="bug-detail">
    <BaseLoading :active="loading" message="Loading bug details..." />
    <BaseAlert v-model="alert.show" :type="alert.type" :title="alert.title" :message="alert.message" />

    <div class="page-header mb-4">
      <div class="flex items-center gap-2 mb-2">
        <router-link to="/bugs" class="text-secondary" style="font-size: 0.875rem;">← Back to bugs</router-link>
      </div>
      <div class="flex justify-between items-start">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span style="font-size: 0.875rem; color: var(--primary); font-weight: 600;">{{ bug.project?.name }}</span>
            <span style="font-size: 0.875rem; color: var(--text-secondary);">#{{ bug.id }}</span>
          </div>
          <h1 style="color: var(--text); font-size: 1.5rem; margin-bottom: 0;">{{ bug.title }}</h1>
        </div>
        <div class="flex gap-2">
          <button class="btn btn-secondary" @click="$router.push(`/bugs/${bug.id}/edit`)">Edit Bug</button>
          <button class="btn btn-secondary" @click="showDeleteConfirm = true" style="color: var(--severity-critical); border-color: var(--severity-critical);">Delete Bug</button>
        </div>
      </div>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
      <div class="left-col">
        <!-- Description & Steps -->
        <div class="card mb-4">
          <h3 class="mb-4">Description</h3>
          <p style="white-space: pre-line; margin-bottom: 1.5rem;">{{ bug.description }}</p>
          
          <h3 class="mb-4">Steps to Reproduce</h3>
          <p style="white-space: pre-line;">{{ bug.steps_to_reproduce || 'None provided.' }}</p>

          <div v-if="bug.resolution" class="mt-6 p-4" style="background: var(--bg); border-left: 4px solid #10b981; border-radius: 8px;">
            <h4 style="color: #10b981; margin-bottom: 0.5rem;">✅ Resolution</h4>
            <p style="font-size: 0.875rem; margin: 0;">{{ bug.resolution }}</p>
          </div>
        </div>

        <!-- Links -->
        <div class="card mb-4">
          <div class="flex justify-between items-center mb-4">
            <h3 style="margin:0;">Related Bugs</h3>
            <button class="btn btn-secondary btn-sm" @click="showLinkModal = true">+ Link Bug</button>
          </div>
          
          <div v-if="allLinks.length === 0" class="text-secondary" style="font-size: 0.875rem;">No links.</div>
          <div v-else class="flex flex-col gap-2">
            <div v-for="link in allLinks" :key="link.id" class="link-item">
              <span class="badge badge-sm">{{ link.relation_type }}</span>
              <router-link :to="`/bugs/${link.bug.id}`">#{{ link.bug.id }} - {{ link.bug.title }}</router-link>
            </div>
          </div>
        </div>

        <!-- Comments -->
        <div class="card mb-4">
          <h3 class="mb-4">Comments & Notes</h3>
          <div v-for="comment in bug.comments" :key="comment.id" class="mb-4 comment-box">
            <div class="flex items-center gap-2 mb-2">
              <div class="user-badge">{{ comment.user?.name[0] }}</div>
              <div style="font-weight: 600; font-size: 0.875rem;">{{ comment.user?.name }}</div>
              <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ new Date(comment.created_at).toLocaleString() }}</div>
              <span v-if="comment.is_internal_note" class="badge" style="background: var(--primary-light); color: var(--primary);">Internal</span>
            </div>
            <p style="font-size: 0.875rem; margin: 0; padding-left: 2rem;">{{ comment.content }}</p>
          </div>

          <form @submit.prevent="submitComment" class="mt-4">
            <textarea 
              v-model="newComment.content" 
              class="form-input mb-2" 
              rows="3" 
              placeholder="Add a comment..."
              required
            ></textarea>
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <input type="checkbox" id="internal" v-model="newComment.is_internal_note">
                <label for="internal" style="font-size: 0.875rem;">Internal Note</label>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="submitting">Post</button>
            </div>
          </form>
        </div>
      </div>

      <div class="right-col">
        <!-- Metadata -->
        <div class="card mb-4">
          <h3 class="mb-4">Details</h3>
          
          <div class="meta-row">
            <span class="meta-label">Status</span>
            <button @click="showStatusModal = true" class="btn btn-secondary btn-sm" style="min-width: 100px;">
              {{ bug.status }} ▾
            </button>
          </div>
          
          <div class="meta-row mt-4">
            <span class="meta-label">Severity</span>
            <span class="badge" :style="{ backgroundColor: getSeverityColor(bug.severity), color: 'white' }">{{ bug.severity }}</span>
          </div>

          <div class="meta-row mt-4">
            <span class="meta-label">Assignee</span>
            <select v-model="assigneeId" @change="handleAssign" class="form-input" style="padding: 0.25rem 0.5rem; width: auto;">
              <option :value="null">Unassigned</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div class="meta-row mt-4">
            <span class="meta-label">Created By</span>
            <span style="font-size: 0.875rem;">{{ bug.reporter?.name }}</span>
          </div>
          
          <div class="meta-row mt-4">
            <span class="meta-label">Environment</span>
            <span style="font-size: 0.875rem;">{{ bug.environment || 'N/A' }}</span>
          </div>
        </div>
        
        <!-- Attachments -->
        <div class="card">
          <div class="flex justify-between items-center mb-4">
            <h3 style="margin:0;">Attachments</h3>
            <label class="btn btn-secondary btn-sm" style="cursor:pointer;">
              Upload
              <input type="file" @change="handleFileUpload" style="display:none;">
            </label>
          </div>
          <div v-if="bug.attachments?.length === 0" class="text-secondary" style="font-size: 0.875rem;">No attachments.</div>
          <div v-else class="flex flex-col gap-2">
            <div v-for="att in bug.attachments" :key="att.id" class="att-item">
              <span class="truncate" style="flex: 1;">{{ att.file_name }}</span>
              <div class="flex gap-2">
                <a :href="`http://localhost:8000/storage/${att.file_path}`" target="_blank" style="color: var(--primary); font-size: 0.75rem;">View</a>
                <button @click="deleteAttachment(att.id)" style="color: var(--severity-critical); background:none; border:none; padding:0; font-size: 0.75rem; cursor:pointer;">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Change Modal -->
    <BaseModal :show="showStatusModal" title="Change Bug Status" @close="showStatusModal = false">
      <div class="form-group">
        <label class="form-label">New Status</label>
        <select v-model="statusUpdate.status" class="form-input">
          <option value="Open">Open</option>
          <option value="In Progress">In Progress</option>
          <option value="Fixed">Fixed</option>
          <option value="Not Fixed">Not Fixed</option>
          <option value="Closed">Closed</option>
          <option value="Reopened">Reopened</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Change Note</label>
        <textarea v-model="statusUpdate.note" class="form-input" rows="3" placeholder="Describe why you are changing the status"></textarea>
      </div>
      <div v-if="statusUpdate.status === 'Fixed'" class="form-group">
        <label class="form-label">Resolution Details</label>
        <textarea v-model="statusUpdate.resolution" class="form-input" rows="3" placeholder="How was this bug fixed?"></textarea>
      </div>
      <template #footer>
        <button class="btn btn-primary" @click="handleStatusUpdate">Update Status</button>
        <button class="btn btn-secondary" @click="showStatusModal = false">Cancel</button>
      </template>
    </BaseModal>

    <!-- Link Modal -->
    <BaseModal :show="showLinkModal" title="Link Bug" @close="showLinkModal = false">
      <div class="form-group">
        <label class="form-label">Target Bug ID</label>
        <input v-model="linkForm.target_bug_id" type="number" class="form-input" placeholder="e.g. 5">
      </div>
      <div class="form-group">
        <label class="form-label">Relation Type</label>
        <select v-model="linkForm.relation_type" class="form-input">
          <option value="duplicate">Duplicate of</option>
          <option value="related">Related to</option>
          <option value="blocks">Blocks</option>
        </select>
      </div>
      <template #footer>
        <button class="btn btn-primary" @click="submitLink">Link</button>
        <button class="btn btn-secondary" @click="showLinkModal = false">Cancel</button>
      </template>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal :show="showDeleteConfirm" title="Delete Bug" @close="showDeleteConfirm = false" width="400px">
      <p>Are you sure you want to permanently delete this bug report? This action cannot be undone.</p>
      <template #footer>
        <button class="btn btn-primary" style="background: var(--severity-critical); border-color: var(--severity-critical);" @click="handleDelete">Yes, Delete</button>
        <button class="btn btn-secondary" @click="showDeleteConfirm = false">Cancel</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import BaseLoading from '../../components/BaseLoading.vue';
import BaseAlert from '../../components/BaseAlert.vue';
import BaseModal from '../../components/BaseModal.vue';

const route = useRoute();
const router = useRouter();
const bug = ref(null);
const users = ref([]);
const loading = ref(true);
const submitting = ref(false);
const assigneeId = ref(null);

const alert = reactive({ show: false, type: 'info', title: '', message: '' });

const showStatusModal = ref(false);
const showLinkModal = ref(false);
const showDeleteConfirm = ref(false);

const newComment = ref({ content: '', is_internal_note: false });
const statusUpdate = ref({ status: '', note: '', resolution: '' });
const linkForm = ref({ target_bug_id: '', relation_type: 'related' });

const allLinks = computed(() => {
  if (!bug.value) return [];
  const sourceLinks = (bug.value.links_as_source || []).map(l => ({ id: l.id, relation_type: l.relation_type, bug: l.target_bug }));
  const targetLinks = (bug.value.links_as_target || []).map(l => ({ id: l.id, relation_type: l.relation_type, bug: l.source_bug }));
  return [...sourceLinks, ...targetLinks];
});

const loadData = async () => {
  try {
    const [bRes, uRes] = await Promise.all([
      api.get(`/bugs/${route.params.id}`),
      api.get('/users')
    ]);
    bug.value = bRes.data;
    users.value = uRes.data.data || uRes.data;
    statusUpdate.value.status = bug.value.status;
    assigneeId.value = bug.value.assignee_id;
  } catch (error) {
    showAlert('error', 'Error', 'Failed to load bug details.');
  } finally {
    loading.value = false;
  }
};

const showAlert = (type, title, message) => {
  alert.type = type;
  alert.title = title;
  alert.message = message;
  alert.show = true;
  setTimeout(() => alert.show = false, 5000);
};

const handleStatusUpdate = async () => {
  if (!statusUpdate.value.note) {
    showAlert('warning', 'Note Required', 'Please provide a note for this status change.');
    return;
  }
  loading.value = true;
  try {
    await api.patch(`/bugs/${bug.value.id}/status`, statusUpdate.value);
    showStatusModal.value = false;
    showAlert('success', 'Updated', 'Bug status updated successfully.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', error.response?.data?.message || 'Invalid status transition');
  } finally {
    loading.value = false;
  }
};

const handleAssign = async () => {
  loading.value = true;
  try {
    await api.patch(`/bugs/${bug.value.id}/assign`, { assignee_id: assigneeId.value });
    showAlert('success', 'Assigned', 'Bug assignee updated.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to update assignee.');
  } finally {
    loading.value = false;
  }
};

const submitComment = async () => {
  submitting.value = true;
  try {
    await api.post(`/bugs/${bug.value.id}/comments`, newComment.value);
    newComment.value = { content: '', is_internal_note: false };
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to post comment.');
  } finally {
    submitting.value = false;
  }
};

const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  loading.value = true;
  const formData = new FormData();
  formData.append('file', file);
  try {
    await api.post(`/bugs/${bug.value.id}/attachments`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    showAlert('success', 'Uploaded', 'File attached successfully.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Upload failed.');
  } finally {
    loading.value = false;
  }
};

const deleteAttachment = async (id) => {
  loading.value = true;
  try {
    await api.delete(`/bugs/attachments/${id}`);
    showAlert('success', 'Deleted', 'Attachment removed.');
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Delete failed.');
  } finally {
    loading.value = false;
  }
};

const submitLink = async () => {
  try {
    await api.post(`/bugs/${bug.value.id}/links`, linkForm.value);
    showLinkModal.value = false;
    showAlert('success', 'Linked', 'Bug linked successfully.');
    linkForm.value = { target_bug_id: '', relation_type: 'related' };
    await loadData();
  } catch (error) {
    showAlert('error', 'Failed', 'Failed to link bug.');
  }
};

const handleDelete = async () => {
  loading.value = true;
  try {
    await api.delete(`/bugs/${bug.value.id}`);
    router.push('/bugs');
  } catch (error) {
    showAlert('error', 'Failed', 'Could not delete bug.');
  } finally {
    loading.value = false;
  }
};

const getSeverityColor = (sev) => {
  const map = { 'Low': 'var(--severity-low)', 'Medium': 'var(--severity-medium)', 'High': 'var(--severity-high)', 'Critical': 'var(--severity-critical)' };
  return map[sev] || map['Low'];
};

onMounted(loadData);
</script>

<style scoped>
.meta-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; }
.meta-label { color: var(--text-secondary); font-weight: 500; }
.link-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; }
.comment-box { border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
.att-item { display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; }
.truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-badge { width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.badge-sm { padding: 0.1rem 0.3rem; font-size: 0.65rem; }
</style>
