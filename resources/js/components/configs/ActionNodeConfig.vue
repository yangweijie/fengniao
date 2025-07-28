<template>
  <div class="action-node-config space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">动作类型</label>
      <select v-model="config.action" 
              @change="updateConfig"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">选择动作类型</option>
        <option value="click">点击元素</option>
        <option value="input">输入文本</option>
        <option value="navigate">页面导航</option>
        <option value="wait">等待</option>
        <option value="screenshot">截图</option>
        <option value="script">执行脚本</option>
      </select>
    </div>

    <!-- 点击元素配置 -->
    <div v-if="config.action === 'click'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">选择器</label>
        <input v-model="config.selector" 
               @input="updateConfig"
               type="text" 
               placeholder="CSS选择器，如: #button, .class, [data-id='value']"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">等待时间 (秒)</label>
        <input v-model.number="config.waitTime" 
               @input="updateConfig"
               type="number" 
               min="0" 
               step="0.1"
               placeholder="等待元素出现的时间"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- 输入文本配置 -->
    <div v-if="config.action === 'input'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">选择器</label>
        <input v-model="config.selector" 
               @input="updateConfig"
               type="text" 
               placeholder="输入框的CSS选择器"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">输入内容</label>
        <textarea v-model="config.text" 
                  @input="updateConfig"
                  rows="3"
                  placeholder="要输入的文本内容"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
      </div>
      
      <div class="flex items-center">
        <input v-model="config.clearFirst" 
               @change="updateConfig"
               type="checkbox" 
               id="clearFirst"
               class="mr-2">
        <label for="clearFirst" class="text-sm text-gray-700">输入前清空内容</label>
      </div>
    </div>

    <!-- 页面导航配置 -->
    <div v-if="config.action === 'navigate'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">目标URL</label>
        <input v-model="config.url" 
               @input="updateConfig"
               type="url" 
               placeholder="https://example.com"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div class="flex items-center">
        <input v-model="config.waitForLoad" 
               @change="updateConfig"
               type="checkbox" 
               id="waitForLoad"
               class="mr-2">
        <label for="waitForLoad" class="text-sm text-gray-700">等待页面完全加载</label>
      </div>
    </div>

    <!-- 等待配置 -->
    <div v-if="config.action === 'wait'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">等待类型</label>
        <select v-model="config.waitType" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="time">固定时间</option>
          <option value="element">等待元素</option>
          <option value="condition">等待条件</option>
        </select>
      </div>
      
      <div v-if="config.waitType === 'time'">
        <label class="block text-sm font-medium text-gray-700 mb-2">等待时间 (秒)</label>
        <input v-model.number="config.duration" 
               @input="updateConfig"
               type="number" 
               min="0" 
               step="0.1"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div v-if="config.waitType === 'element'">
        <label class="block text-sm font-medium text-gray-700 mb-2">元素选择器</label>
        <input v-model="config.selector" 
               @input="updateConfig"
               type="text" 
               placeholder="等待出现的元素选择器"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- 截图配置 -->
    <div v-if="config.action === 'screenshot'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">截图描述</label>
        <input v-model="config.description" 
               @input="updateConfig"
               type="text" 
               placeholder="截图的描述信息"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div class="flex items-center">
        <input v-model="config.fullPage" 
               @change="updateConfig"
               type="checkbox" 
               id="fullPage"
               class="mr-2">
        <label for="fullPage" class="text-sm text-gray-700">全页面截图</label>
      </div>
    </div>

    <!-- 执行脚本配置 -->
    <div v-if="config.action === 'script'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">脚本代码</label>
        <textarea v-model="config.code" 
                  @input="updateConfig"
                  rows="6"
                  placeholder="JavaScript代码"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"></textarea>
      </div>
      
      <div class="flex items-center">
        <input v-model="config.async" 
               @change="updateConfig"
               type="checkbox" 
               id="async"
               class="mr-2">
        <label for="async" class="text-sm text-gray-700">异步执行</label>
      </div>
    </div>

    <!-- 错误处理配置 -->
    <div class="border-t pt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-3">错误处理</h4>
      
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">失败时动作</label>
          <select v-model="config.onError" 
                  @change="updateConfig"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="stop">停止执行</option>
            <option value="continue">继续执行</option>
            <option value="retry">重试</option>
          </select>
        </div>
        
        <div v-if="config.onError === 'retry'">
          <label class="block text-sm font-medium text-gray-700 mb-2">重试次数</label>
          <input v-model.number="config.retryCount" 
                 @input="updateConfig"
                 type="number" 
                 min="1" 
                 max="10"
                 class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  node: {
    type: Object,
    required: true
  }
})

// 初始化配置
const config = reactive(props.node.data.config || {
  action: '',
  onError: 'stop',
  retryCount: 3
})

// 更新节点配置
const updateConfig = () => {
  props.node.data.config = { ...config }
}

// 监听节点变化
watch(() => props.node.data.config, (newConfig) => {
  Object.assign(config, newConfig)
}, { deep: true })
</script>

<style scoped>
.action-node-config {
  max-height: 500px;
  overflow-y: auto;
}

.action-node-config::-webkit-scrollbar {
  width: 4px;
}

.action-node-config::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.action-node-config::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}
</style>
