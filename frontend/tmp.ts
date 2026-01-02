import { createPinia, setActivePinia } from "pinia";
import { useAuthStore } from "./src/stores/auth";

setActivePinia(createPinia());

const authStore = useAuthStore();

type UserType = typeof authStore.user;
