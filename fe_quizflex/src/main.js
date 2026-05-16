import { createApp } from "vue";
import App from "./App.vue";

import router from "../src/router/router";
import { createPinia } from "pinia";

// import "./styles/global.scss";

createApp(App).use(router).use(createPinia()).mount("#app");
