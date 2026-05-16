import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router/router'
import './assets/main.css'

const savedTheme = localStorage.getItem('quizflex-theme') || 'dark'
document.documentElement.setAttribute('data-theme', savedTheme)

createApp(App).use(router).use(createPinia()).mount('#app')
