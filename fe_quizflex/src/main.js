import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router/router'
import i18n from './i18n'
import './assets/main.css'

const savedTheme = localStorage.getItem('quizflex-theme') || 'light'
document.documentElement.setAttribute('data-theme', savedTheme)

createApp(App).use(router).use(createPinia()).use(i18n).mount('#app')
