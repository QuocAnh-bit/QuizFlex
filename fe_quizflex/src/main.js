import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router/router'
import './assets/main.css'
import i18n from './i18n'

const savedTheme = localStorage.getItem('quizflex-theme') || 'dark'
document.documentElement.setAttribute('data-theme', savedTheme)

createApp(App).use(router).use(createPinia()).use(i18n).mount('#app')
