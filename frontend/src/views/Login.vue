<template>
  <div class="auth-container">
    <div class="card auth-card">
      <div class="text-center mb-4">
        <h1 class="auth-title" style="color: var(--primary);">Annajah</h1>
        <p>Sign in to your account</p>
      </div>
      
      <form @submit.prevent="handleLogin">
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
        </div>
        
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <input type="checkbox" id="remember" v-model="form.remember">
            <label for="remember" style="font-size: 0.875rem;">Remember me</label>
          </div>
          <a href="#" style="font-size: 0.875rem;">Forgot password?</a>
        </div>
        
        <div v-if="error" style="color: var(--severity-critical); font-size: 0.875rem; margin-bottom: 1rem; text-align: center;">
          {{ error }}
        </div>
        
        <button type="submit" class="btn btn-primary w-full" :disabled="loading">
          {{ loading ? 'Signing in...' : 'Sign in' }}
        </button>
      </form>
      
      <div class="text-center mt-4" style="font-size: 0.875rem;">
        Don't have an account? <router-link to="/register">Register here</router-link>
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
const error = ref('');

const form = reactive({
  email: '',
  password: '',
  remember: false
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await api.post('/auth/login', form);
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    
    // Redirect based on role or to dashboard
    router.push('/');
  } catch (err) {
    if (err.response?.data?.errors?.email) {
      error.value = err.response.data.errors.email[0];
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'An error occurred during login.';
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
</style>
