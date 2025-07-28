import { createApp } from 'vue'
import { createPinia } from 'pinia'
import WorkflowEditor from './components/WorkflowEditor.vue'

const app = createApp(WorkflowEditor)
const pinia = createPinia()

app.use(pinia)
app.mount('#workflow-editor')
