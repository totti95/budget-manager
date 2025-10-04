<template>
  <div id="app" class="min-h-screen">
    <NavBar v-if="authStore.isAuthenticated" />
    <main :class="{ 'pt-16': authStore.isAuthenticated }">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavBar from '@/components/NavBar.vue'

const authStore = useAuthStore()
</script>
