<template>
  <div class="condition-node">
    <Handle type="target" :position="Position.Top" class="handle" />
    <Handle type="source" :position="Position.Bottom" :style="{ left: '25%' }" class="handle handle-true" />
    <Handle type="source" :position="Position.Bottom" :style="{ left: '75%' }" class="handle handle-false" />
    
    <div class="node-content">
      <div class="node-icon">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      
      <div class="node-info">
        <div class="node-title">{{ data.label }}</div>
        <div class="node-description">{{ data.description }}</div>
        
        <div v-if="data.config && data.config.condition" class="node-config">
          <span class="config-label">条件:</span>
          <span class="config-value">{{ data.config.condition }}</span>
        </div>
      </div>
    </div>
    
    <div class="node-outputs">
      <div class="output-label output-true">True</div>
      <div class="output-label output-false">False</div>
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
.condition-node {
  background: linear-gradient(135deg, #F59E0B, #D97706);
  border: 2px solid #B45309;
  border-radius: 12px;
  color: white;
  min-width: 180px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  position: relative;
}

.condition-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.node-content {
  padding: 12px 16px;
  display: flex;
  align-items: flex-start;
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
  flex-wrap: wrap;
}

.config-label {
  font-weight: 500;
}

.config-value {
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 3px;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.node-outputs {
  display: flex;
  justify-content: space-between;
  padding: 0 16px 8px;
}

.output-label {
  font-size: 10px;
  font-weight: 600;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.output-true {
  color: #10B981;
}

.output-false {
  color: #EF4444;
}

.handle {
  width: 12px;
  height: 12px;
  background: #ffffff;
  border: 2px solid #B45309;
}

.handle-true {
  border-color: #10B981;
}

.handle-false {
  border-color: #EF4444;
}
</style>
