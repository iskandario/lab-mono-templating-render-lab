<script setup lang="ts">
import AppNavbar from '@/components/common/AppNavbar.vue'
</script>

<template>
  <v-app>
    <header class="auth-layout__header">
      <AppNavbar />
    </header>
    <v-main class="auth-layout__main">
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">
            <v-card elevation="4" rounded="lg">
              <router-view />
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    <footer class="auth-layout__footer">
      Templating Render Lab
    </footer>
  </v-app>
</template>

<style scoped>
.auth-layout__header,
.auth-layout__footer {
  flex-shrink: 0;
}

.auth-layout__main {
  min-width: 0;
}

.auth-layout__footer {
  padding: 12px 16px 18px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.82rem;
  text-align: center;
}
</style>
