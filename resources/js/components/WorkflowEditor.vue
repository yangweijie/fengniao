<template>
  <div class="workflow-editor h-screen flex flex-col bg-gray-100">
    <!-- 顶部工具栏 -->
    <div class="toolbar bg-white border-b border-gray-200 p-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <h1 class="text-xl font-semibold text-gray-900">工作流编辑器</h1>
        <div class="flex items-center space-x-2">
          <button @click="saveWorkflow" 
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            保存
          </button>
          <button @click="runWorkflow" 
                  class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            运行
          </button>
          <button @click="clearWorkflow" 
                  class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            清空
          </button>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <div class="text-sm text-gray-600">
          节点: {{ nodes.length }} | 连接: {{ edges.length }}
        </div>
        <button @click="toggleMinimap" 
                class="px-3 py-1 text-sm bg-gray-200 rounded hover:bg-gray-300">
          {{ showMinimap ? '隐藏' : '显示' }}小地图
        </button>
      </div>
    </div>

    <div class="flex flex-1">
      <!-- 左侧节点面板 -->
      <div class="node-panel w-64 bg-white border-r border-gray-200 p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">节点库</h3>
        
        <div class="space-y-4">
          <div v-for="category in nodeCategories" :key="category.name" class="node-category">
            <h4 class="text-sm font-medium text-gray-700 mb-2">{{ category.name }}</h4>
            <div class="space-y-2">
              <div v-for="nodeType in category.nodes" 
                   :key="nodeType.type"
                   @dragstart="onDragStart($event, nodeType)"
                   draggable="true"
                   class="node-item p-3 border border-gray-300 rounded cursor-move hover:bg-gray-50 hover:border-blue-400">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: nodeType.color }"></div>
                  <span class="text-sm font-medium">{{ nodeType.label }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ nodeType.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 中间画布区域 -->
      <div class="canvas-area flex-1 relative">
        <VueFlow
          v-model:nodes="nodes"
          v-model:edges="edges"
          @drop="onDrop"
          @dragover="onDragOver"
          @node-click="onNodeClick"
          @edge-click="onEdgeClick"
          @connect="onConnect"
          class="vue-flow-container"
          :default-viewport="{ zoom: 1 }"
          :min-zoom="0.2"
          :max-zoom="4"
        >
          <Background pattern="dots" :gap="20" />
          <Controls />
          <MiniMap v-if="showMinimap" />
          
          <!-- 自定义节点模板 -->
          <template #node-start="{ data }">
            <StartNode :data="data" />
          </template>
          
          <template #node-action="{ data }">
            <ActionNode :data="data" />
          </template>
          
          <template #node-condition="{ data }">
            <ConditionNode :data="data" />
          </template>
          
          <template #node-end="{ data }">
            <EndNode :data="data" />
          </template>
        </VueFlow>
      </div>

      <!-- 右侧属性面板 -->
      <div class="property-panel w-80 bg-white border-l border-gray-200 p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">属性设置</h3>
        
        <div v-if="selectedNode" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">节点名称</label>
            <input v-model="selectedNode.data.label" 
                   type="text" 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">描述</label>
            <textarea v-model="selectedNode.data.description" 
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
          
          <!-- 根据节点类型显示不同的配置项 -->
          <component :is="getNodeConfigComponent(selectedNode.type)" 
                     v-if="getNodeConfigComponent(selectedNode.type)"
                     :node="selectedNode" />
        </div>
        
        <div v-else-if="selectedEdge" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">连接标签</label>
            <input v-model="selectedEdge.label" 
                   type="text" 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">连接类型</label>
            <select v-model="selectedEdge.type" 
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="default">默认</option>
              <option value="smoothstep">平滑</option>
              <option value="step">阶梯</option>
              <option value="straight">直线</option>
            </select>
          </div>
        </div>
        
        <div v-else class="text-center text-gray-500 py-8">
          <p>选择一个节点或连接来编辑属性</p>
        </div>
      </div>
    </div>

    <!-- 底部状态栏 -->
    <div class="status-bar bg-gray-50 border-t border-gray-200 px-4 py-2 flex items-center justify-between text-sm text-gray-600">
      <div class="flex items-center space-x-4">
        <span>缩放: {{ Math.round(viewport.zoom * 100) }}%</span>
        <span>位置: ({{ Math.round(viewport.x) }}, {{ Math.round(viewport.y) }})</span>
      </div>
      
      <div class="flex items-center space-x-4">
        <span v-if="lastSaved">最后保存: {{ lastSaved }}</span>
        <span :class="{ 'text-green-600': !hasUnsavedChanges, 'text-orange-600': hasUnsavedChanges }">
          {{ hasUnsavedChanges ? '未保存' : '已保存' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

// 导入自定义节点组件
import StartNode from './nodes/StartNode.vue'
import ActionNode from './nodes/ActionNode.vue'
import ConditionNode from './nodes/ConditionNode.vue'
import EndNode from './nodes/EndNode.vue'

// 导入节点配置组件
import ActionNodeConfig from './configs/ActionNodeConfig.vue'
import ConditionNodeConfig from './configs/ConditionNodeConfig.vue'

const { nodes, edges, addNodes, addEdges, viewport } = useVueFlow()

// 响应式数据
const selectedNode = ref(null)
const selectedEdge = ref(null)
const showMinimap = ref(true)
const hasUnsavedChanges = ref(false)
const lastSaved = ref(null)

// 节点类别定义
const nodeCategories = ref([
  {
    name: '流程控制',
    nodes: [
      {
        type: 'start',
        label: '开始',
        description: '工作流开始节点',
        color: '#10B981'
      },
      {
        type: 'end',
        label: '结束',
        description: '工作流结束节点',
        color: '#EF4444'
      },
      {
        type: 'condition',
        label: '条件判断',
        description: '根据条件分支执行',
        color: '#F59E0B'
      }
    ]
  },
  {
    name: '操作节点',
    nodes: [
      {
        type: 'action',
        label: '执行动作',
        description: '执行具体的操作',
        color: '#3B82F6'
      },
      {
        type: 'script',
        label: '脚本执行',
        description: '执行自定义脚本',
        color: '#8B5CF6'
      },
      {
        type: 'api',
        label: 'API调用',
        description: '调用外部API',
        color: '#06B6D4'
      }
    ]
  }
])

// 拖拽相关方法
const onDragStart = (event, nodeType) => {
  event.dataTransfer.setData('application/reactflow', JSON.stringify(nodeType))
  event.dataTransfer.effectAllowed = 'move'
}

const onDragOver = (event) => {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
}

const onDrop = (event) => {
  event.preventDefault()
  
  const reactFlowBounds = event.target.getBoundingClientRect()
  const nodeData = JSON.parse(event.dataTransfer.getData('application/reactflow'))
  
  const position = {
    x: event.clientX - reactFlowBounds.left,
    y: event.clientY - reactFlowBounds.top,
  }
  
  const newNode = {
    id: `${nodeData.type}_${Date.now()}`,
    type: nodeData.type,
    position,
    data: {
      label: nodeData.label,
      description: nodeData.description,
      color: nodeData.color,
      config: {}
    }
  }
  
  addNodes([newNode])
  hasUnsavedChanges.value = true
}

// 节点和连接事件处理
const onNodeClick = (event) => {
  selectedNode.value = event.node
  selectedEdge.value = null
}

const onEdgeClick = (event) => {
  selectedEdge.value = event.edge
  selectedNode.value = null
}

const onConnect = (connection) => {
  addEdges([connection])
  hasUnsavedChanges.value = true
}

// 工具栏方法
const saveWorkflow = async () => {
  try {
    const workflowData = {
      nodes: nodes.value,
      edges: edges.value,
      viewport: viewport.value
    }
    
    // 这里应该调用API保存工作流
    console.log('保存工作流:', workflowData)
    
    hasUnsavedChanges.value = false
    lastSaved.value = new Date().toLocaleTimeString()
  } catch (error) {
    console.error('保存失败:', error)
  }
}

const runWorkflow = async () => {
  try {
    const workflowData = {
      nodes: nodes.value,
      edges: edges.value
    }
    
    // 这里应该调用API运行工作流
    console.log('运行工作流:', workflowData)
  } catch (error) {
    console.error('运行失败:', error)
  }
}

const clearWorkflow = () => {
  if (confirm('确定要清空工作流吗？')) {
    nodes.value = []
    edges.value = []
    selectedNode.value = null
    selectedEdge.value = null
    hasUnsavedChanges.value = true
  }
}

const toggleMinimap = () => {
  showMinimap.value = !showMinimap.value
}

// 获取节点配置组件
const getNodeConfigComponent = (nodeType) => {
  const configComponents = {
    action: ActionNodeConfig,
    condition: ConditionNodeConfig
  }
  return configComponents[nodeType]
}

// 组件挂载时的初始化
onMounted(() => {
  // 可以在这里加载已保存的工作流
})
</script>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
@import '@vue-flow/controls/dist/style.css';
@import '@vue-flow/minimap/dist/style.css';

.workflow-editor {
  font-family: 'Inter', sans-serif;
}

.vue-flow-container {
  background-color: #f9fafb;
}

.node-item {
  transition: all 0.2s ease;
}

.node-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>
