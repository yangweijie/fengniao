<template>
  <div class="start-node">
    <Handle type="source" :position="Position.Bottom" class="handle" />
    
    <div class="node-content">
      <div class="node-icon">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-9-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      
      <div class="node-info">
        <div class="node-title">{{ data.label }}</div>
        <div class="node-description">{{ data.description }}</div>
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
.start-node {
  background: linear-gradient(135deg, #10B981, #059669);
  border: 2px solid #047857;
  border-radius: 12px;
  color: white;
  min-width: 160px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.start-node:hover {
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
}

.handle {
  width: 12px;
  height: 12px;
  background: #ffffff;
  border: 2px solid #047857;
}
</style>
