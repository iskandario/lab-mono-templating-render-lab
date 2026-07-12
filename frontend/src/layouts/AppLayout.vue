<script setup lang="ts">
import AppNavbar from '@/components/common/AppNavbar.vue'
</script>

<template>
  <v-app>
    <header class="app-layout__header">
      <AppNavbar />
    </header>
    <v-main tag="div" class="app-layout__main">
      <router-view />
    </v-main>
  </v-app>
</template>

<style scoped>
.app-layout__header {
  flex-shrink: 0;
}

.app-layout__main {
  min-width: 0;
}
</style>
