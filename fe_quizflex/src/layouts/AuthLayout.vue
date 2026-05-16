<template>
  <div class="auth-layout">
    <div class="auth-box">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f3f4f6;
}

.auth-box {
  width: 400px;
  background: white;
  padding: 30px;
  border-radius: 12px;
}
</style>