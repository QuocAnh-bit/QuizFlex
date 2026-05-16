<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <h2>Quiz Admin</h2>

      <router-link to="/admin">
        Dashboard
      </router-link>

      <router-link to="/admin/questions">
        Questions
      </router-link>
    </aside>

    <main class="content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 250px;
  background: #111827;
  color: white;
  padding: 20px;
}

.content {
  flex: 1;
  padding: 20px;
}
</style>