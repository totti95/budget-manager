import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import "./styles/main.css";
import VueGridLayout from "vue-grid-layout";

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(VueGridLayout);

app.mount("#app");
