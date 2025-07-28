<template>
  <div class="end-node">
    <Handle type="target" :position="Position.Top" class="handle" />
    
    <div class="node-content">
      <div class="node-icon">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      
      <div class="node-info">
        <div class="node-title">{{ data.label }}</div>
        <div class="node-description">{{ data.description }}</div>
        
        <div v-if="data.config && data.config.result" class="node-config">
          <span class="config-label">结果:</span>
          <span class="config-value">{{ data.config.result }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Handle, Position } from '@vue-flow/core'

defineProps({
  data: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.end-node {
  background: linear-gradient(135deg, #EF4444, #DC2626);
  border: 2px solid #B91C1C;
  border-radius: 12px;
  color: white;
  min-width: 160px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.end-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.node-content {
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
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

.handle {
  width: 12px;
  height: 12px;
  background: #ffffff;
  border: 2px solid #B91C1C;
}
</style>
