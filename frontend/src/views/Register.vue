<template>
  <div class="auth-container">
    <div class="card auth-card">
      <div class="text-center mb-4">
        <h1 class="auth-title" style="color: var(--primary);">Annajah</h1>
        <p>Create a new account</p>
      </div>
      
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label class="form-label" for="name">Full Name</label>
          <input 
            id="name" 
            v-model="form.name" 
            type="text" 
            class="form-input" 
            required 
            placeholder="John Doe"
          >
          <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input 
            id="email" 
            v-model="form.email" 
            type="email" 
            class="form-input" 
            required 
            placeholder="you@example.com"
          >
          <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input 
            id="password" 
            v-model="form.password" 
            type="password" 
            class="form-input" 
            required
          >
          <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirm Password</label>
          <input 
            id="password_confirmation" 
            v-model="form.password_confirmation" 
            type="password" 
            class="form-input" 
            required
          >
        </div>
        
        <div v-if="globalError" style="color: var(--severity-critical); font-size: 0.875rem; margin-bottom: 1rem; text-align: center;">
          {{ globalError }}
        </div>
        
        <button type="submit" class="btn btn-primary w-full" :disabled="loading">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
      </form>
      
      <div class="text-center mt-4" style="font-size: 0.875rem;">
        Already have an account? <router-link to="/login">Sign in here</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();
const loading = ref(false);
const errors = ref({});
const globalError = ref('');

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const handleRegister = async () => {
  loading.value = true;
  errors.value = {};
  globalError.value = '';
  
  try {
    const response = await api.post('/auth/register', form);
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    
    router.push('/');
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else if (err.response?.data?.message) {
      globalError.value = err.response.data.message;
    } else {
      globalError.value = 'An error occurred during registration.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: var(--bg);
  padding: 1rem;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem 2rem;
}

.auth-title {
  font-size: 1.875rem;
  letter-spacing: -0.025em;
  margin-bottom: 0.5rem;
}

.text-center {
  text-align: center;
}

.error-text {
  color: var(--severity-critical);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}
</style>
