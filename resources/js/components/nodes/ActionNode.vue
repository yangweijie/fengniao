<template>
  <div class="action-node">
    <Handle type="target" :position="Position.Top" class="handle" />
    <Handle type="source" :position="Position.Bottom" class="handle" />
    
    <div class="node-content">
      <div class="node-icon">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
      </div>
      
      <div class="node-info">
        <div class="node-title">{{ data.label }}</div>
        <div class="node-description">{{ data.description }}</div>
        
        <div v-if="data.config && data.config.action" class="node-config">
          <span class="config-label">动作:</span>
          <span class="config-value">{{ data.config.action }}</span>
        </div>
      </div>
      
      <div class="node-status">
        <div class="status-indicator" :class="statusClass"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Handle, Position } from '@vue-flow/core'

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const statusClass = computed(() => {
  const status = props.data.status || 'idle'
  return {
    'status-idle': status === 'idle',
    'status-running': status === 'running',
    'status-success': status === 'success',
    'status-error': status === 'error'
  }
})
</script>

<style scoped>
.action-node {
  background: linear-gradient(135deg, #3B82F6, #2563EB);
  border: 2px solid #1D4ED8;
  border-radius: 12px;
  color: white;
  min-width: 180px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.action-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.node-content {
  padding: 12px 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  position: relative;
}

.node-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.node-info {
  flex: 1;
  min-width: 0;
}

.node-title {
  font-weight: 600;
  font-size: 14px;
  line-height: 1.2;
  margin-bottom: 2px;
}

.node-description {
  font-size: 12px;
  opacity: 0.9;
  line-height: 1.2;
  margin-bottom: 6px;
}

.node-config {
  font-size: 11px;
  opacity: 0.8;
  display: flex;
  gap: 4px;
}

.config-label {
  font-weight: 500;
}

.config-value {
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 3px;
}

.node-status {
  position: absolute;
  top: 8px;
  right: 8px;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.status-idle {
  background: #6B7280;
}

.status-running {
  background: #F59E0B;
  animation: pulse 2s infinite;
}

.status-success {
  background: #10B981;
}

.status-error {
  background: #EF4444;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.handle {
  width: 12px;
  height: 12px;
  background: #ffffff;
  border: 2px solid #1D4ED8;
}
</style>
