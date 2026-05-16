<template>
  <div>
    <header class="header">
     USER CLIENT LAYOUT
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>

<style scoped>
.header {
  padding: 20px;
  background: #2563eb;
  color: white;
}
</style>