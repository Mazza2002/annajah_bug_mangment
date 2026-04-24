<template>
  <Transition name="slide">
    <div v-if="modelValue" class="alert-container" :class="type">
      <div class="flex items-center gap-3">
        <span class="icon">{{ icon }}</span>
        <div class="flex-1">
          <h4 v-if="title" class="alert-title">{{ title }}</h4>
          <p class="alert-message">{{ message }}</p>
        </div>
        <button @click="$emit('update:modelValue', false)" class="close-btn">&times;</button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  type: { type: String, default: 'info' }, // info, success, warning, error
  title: String,
  message: String
});

defineEmits(['update:modelValue']);

const icon = computed(() => {
  switch (props.type) {
    case 'success': return '✅';
    case 'error': return '❌';
    case 'warning': return '⚠️';
    default: return 'ℹ️';
  }
});
</script>

<style scoped>
.alert-container {
  position: fixed;
  top: 2rem;
  right: 2rem;
  z-index: 10000;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  background: white;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
  min-width: 300px;
  max-width: 450px;
  border-left: 6px solid var(--primary);
  animation: slideIn 0.3s ease-out;
}

.alert-container.success { border-left-color: #10b981; }
.alert-container.error { border-left-color: #ef4444; }
.alert-container.warning { border-left-color: #f59e0b; }

.alert-title { margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 700; }
.alert-message { margin: 0; font-size: 0.875rem; color: var(--text-secondary); }

.close-btn {
  background: none; border: none; font-size: 1.5rem; color: var(--text-secondary);
  cursor: pointer; padding: 0 0.5rem;
}

.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from { transform: translateX(100%); opacity: 0; }
.slide-leave-to { transform: translateX(100%); opacity: 0; }

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
</style>
