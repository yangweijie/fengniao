<template>
  <div class="condition-node-config space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">条件类型</label>
      <select v-model="config.conditionType" 
              @change="updateConfig"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">选择条件类型</option>
        <option value="element">元素存在</option>
        <option value="text">文本内容</option>
        <option value="url">URL匹配</option>
        <option value="variable">变量值</option>
        <option value="script">自定义脚本</option>
      </select>
    </div>

    <!-- 元素存在条件 -->
    <div v-if="config.conditionType === 'element'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">元素选择器</label>
        <input v-model="config.selector" 
               @input="updateConfig"
               type="text" 
               placeholder="CSS选择器"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">检查条件</label>
        <select v-model="config.elementCondition" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="exists">元素存在</option>
          <option value="visible">元素可见</option>
          <option value="enabled">元素可用</option>
          <option value="selected">元素被选中</option>
        </select>
      </div>
    </div>

    <!-- 文本内容条件 -->
    <div v-if="config.conditionType === 'text'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">文本来源</label>
        <select v-model="config.textSource" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="page">页面标题</option>
          <option value="element">元素文本</option>
          <option value="attribute">元素属性</option>
        </select>
      </div>
      
      <div v-if="config.textSource === 'element' || config.textSource === 'attribute'">
        <label class="block text-sm font-medium text-gray-700 mb-2">元素选择器</label>
        <input v-model="config.selector" 
               @input="updateConfig"
               type="text" 
               placeholder="CSS选择器"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div v-if="config.textSource === 'attribute'">
        <label class="block text-sm font-medium text-gray-700 mb-2">属性名称</label>
        <input v-model="config.attributeName" 
               @input="updateConfig"
               type="text" 
               placeholder="如: value, href, class"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">比较方式</label>
        <select v-model="config.textComparison" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="equals">等于</option>
          <option value="contains">包含</option>
          <option value="startsWith">开始于</option>
          <option value="endsWith">结束于</option>
          <option value="regex">正则匹配</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">期望值</label>
        <input v-model="config.expectedValue" 
               @input="updateConfig"
               type="text" 
               placeholder="期望的文本内容"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- URL匹配条件 -->
    <div v-if="config.conditionType === 'url'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">URL部分</label>
        <select v-model="config.urlPart" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="full">完整URL</option>
          <option value="hostname">主机名</option>
          <option value="pathname">路径</option>
          <option value="search">查询参数</option>
          <option value="hash">锚点</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">匹配方式</label>
        <select v-model="config.urlComparison" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="equals">等于</option>
          <option value="contains">包含</option>
          <option value="startsWith">开始于</option>
          <option value="regex">正则匹配</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">期望值</label>
        <input v-model="config.expectedValue" 
               @input="updateConfig"
               type="text" 
               placeholder="期望的URL内容"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- 变量值条件 -->
    <div v-if="config.conditionType === 'variable'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">变量名称</label>
        <input v-model="config.variableName" 
               @input="updateConfig"
               type="text" 
               placeholder="变量名称"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">比较操作</label>
        <select v-model="config.variableComparison" 
                @change="updateConfig"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="equals">等于</option>
          <option value="notEquals">不等于</option>
          <option value="greater">大于</option>
          <option value="less">小于</option>
          <option value="greaterEqual">大于等于</option>
          <option value="lessEqual">小于等于</option>
          <option value="contains">包含</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">比较值</label>
        <input v-model="config.expectedValue" 
               @input="updateConfig"
               type="text" 
               placeholder="比较的值"
               class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- 自定义脚本条件 -->
    <div v-if="config.conditionType === 'script'" class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">条件脚本</label>
        <textarea v-model="config.script" 
                  @input="updateConfig"
                  rows="6"
                  placeholder="返回 true 或 false 的 JavaScript 代码"
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"></textarea>
      </div>
      
      <div class="text-xs text-gray-500">
        <p>脚本应该返回布尔值 (true/false)。</p>
        <p>可以使用 document, window 等浏览器对象。</p>
      </div>
    </div>

    <!-- 高级设置 -->
    <div class="border-t pt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-3">高级设置</h4>
      
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">超时时间 (秒)</label>
          <input v-model.number="config.timeout" 
                 @input="updateConfig"
                 type="number" 
                 min="1" 
                 max="300"
                 placeholder="条件检查的超时时间"
                 class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="flex items-center">
          <input v-model="config.caseSensitive" 
                 @change="updateConfig"
                 type="checkbox" 
                 id="caseSensitive"
                 class="mr-2">
          <label for="caseSensitive" class="text-sm text-gray-700">区分大小写</label>
        </div>
        
        <div class="flex items-center">
          <input v-model="config.waitForCondition" 
                 @change="updateConfig"
                 type="checkbox" 
                 id="waitForCondition"
                 class="mr-2">
          <label for="waitForCondition" class="text-sm text-gray-700">等待条件满足</label>
        </div>
      </div>
    </div>

    <!-- 输出标签设置 -->
    <div class="border-t pt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-3">输出标签</h4>
      
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">True 分支标签</label>
          <input v-model="config.trueLabel" 
                 @input="updateConfig"
                 type="text" 
                 placeholder="True"
                 class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">False 分支标签</label>
          <input v-model="config.falseLabel" 
                 @input="updateConfig"
                 type="text" 
                 placeholder="False"
                 class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
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
  conditionType: '',
  timeout: 30,
  caseSensitive: false,
  waitForCondition: true,
  trueLabel: 'True',
  falseLabel: 'False'
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
.condition-node-config {
  max-height: 500px;
  overflow-y: auto;
}

.condition-node-config::-webkit-scrollbar {
  width: 4px;
}

.condition-node-config::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.condition-node-config::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}
</style>
