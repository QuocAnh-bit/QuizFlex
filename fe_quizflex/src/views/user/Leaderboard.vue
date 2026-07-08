<template>
  <div class="leaderboard-page">
    <!-- Background Glows -->
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>
    <div class="bg-glow glow-3"></div>

    <!-- Floating Particles -->
    <div class="particles">
      <span class="particle p1">✦</span>
      <span class="particle p2">◆</span>
      <span class="particle p3">★</span>
      <span class="particle p4">✧</span>
      <span class="particle p5">▲</span>
      <span class="particle p6">◇</span>
      <span class="particle p7">✦</span>
      <span class="particle p8">★</span>
      <span class="particle p9">◆</span>
      <span class="particle p10">✧</span>
    </div>

    <!-- Side Decorations -->
    <div class="side-trophy">🏆</div>
    <div class="side-chart">📈</div>

    <!-- Top 3 Podium -->
    <div class="podium">
      <!-- Hạng 2 -->
      <div class="podium-card second">
        <div class="rank-badge silver">2</div>
        <div class="avatar silver">
          {{ leaderboard[1]?.name?.charAt(0).toUpperCase() || 'A' }}
        </div>
        <h3>{{ leaderboard[1]?.name || 'Alice' }}</h3>
        <span class="level">Level {{ leaderboard[1]?.level || 2 }}</span>
        <div class="xp">🏆 {{ leaderboard[1]?.xp || 48 }} XP</div>
      </div>

      <!-- Hạng 1 -->
      <div class="podium-card first">
        <div class="crown">👑</div>
        <div class="rank-badge gold">1</div>
        <div class="avatar gold">
          {{ leaderboard[0]?.name?.charAt(0).toUpperCase() || 'N' }}
        </div>
        <h2>
          {{ leaderboard[0]?.name || 'ngô huy' }}
          <span v-if="leaderboard[0]?.is_me" class="me-tag">(bạn)</span>
        </h2>
        <span class="level">Level {{ leaderboard[0]?.level || 1 }}</span>
        <div class="xp gold-xp">🏆 {{ leaderboard[0]?.xp || 60 }} XP</div>
      </div>

      <!-- Hạng 3 -->
      <div class="podium-card third">
        <div class="rank-badge bronze">3</div>
        <div class="avatar bronze">
          {{ leaderboard[2]?.name?.charAt(0).toUpperCase() || 'B' }}
        </div>
        <h3>{{ leaderboard[2]?.name || 'Bob' }}</h3>
        <span class="level">Level {{ leaderboard[2]?.level || 1 }}</span>
        <div class="xp">🏆 {{ leaderboard[2]?.xp || 36 }} XP</div>
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="leaderboard-card">
      <table class="leader-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Người chơi</th>
            <th>XP</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(user, i) in leaderboard.slice(0, visibleCount)"
            :key="i"
            class="leader-row"
            :class="{ me: user.is_me }"
          >
            <td class="rank-cell">
              <span v-if="i === 0" class="medal">🥇</span>
              <span v-else-if="i === 1" class="medal">🥈</span>
              <span v-else-if="i === 2" class="medal">🥉</span>
              <span v-else class="rank-num">#{{ i + 1 }}</span>
            </td>
            <td class="player-cell">
              <div class="avatar-small">{{ user.name.charAt(0).toUpperCase() }}</div>
              <div class="player-info">
                <div class="name">
                  {{ user.name }}
                  <span v-if="user.is_me" class="me-tag">(bạn)</span>
                </div>
                <div class="level">Level {{ user.level }}</div>
              </div>
            </td>
            <td class="xp-cell">
              {{ user.xp }} XP
              <span v-if="user.is_me" class="crown-mini">👑</span>
            </td>
          </tr>
        </tbody>
      </table>

      <button v-if="leaderboard.length > visibleCount" class="show-more" @click="visibleCount += 10">
        Xem thêm <span class="chev">⌄</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const leaderboard = ref([]);
const visibleCount = ref(5);

onMounted(() => {
  leaderboard.value = [
    { rank: 1, name: "ngô huy", level: 1, xp: 60, is_me: true },
    { rank: 2, name: "Alice", level: 2, xp: 48, is_me: false },
    { rank: 3, name: "Bob", level: 1, xp: 36, is_me: false },
    { rank: 4, name: "Charlie", level: 1, xp: 28, is_me: false },
    { rank: 5, name: "David", level: 1, xp: 22, is_me: false },
  ];
});
</script>

<style scoped>
.leaderboard-page {
  position: relative;
  min-height: 100vh;
  background: radial-gradient(circle at 50% 30%, #3b1e6b 0%, #1a0f2e 60%, #12081f 100%);
  overflow: hidden;
  color: white;
  padding: 40px 20px 100px;
}

/* Background Glows */
.bg-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(130px);
  opacity: 0.45;
  z-index: 0;
  pointer-events: none;
}

.glow-1 {
  width: 680px;
  height: 680px;
  background: #9333ea;
  top: -200px;
  left: -200px;
}

.glow-2 {
  width: 580px;
  height: 580px;
  background: #f59e0b;
  bottom: -150px;
  right: -150px;
}

.glow-3 {
  width: 420px;
  height: 420px;
  background: #c026d3;
  top: 45%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0.28;
}

/* Floating Particles */
.particles {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}

.particle {
  position: absolute;
  font-size: 20px;
  color: #e0bbff;
  opacity: 0.65;
  animation: float-particle 14s linear infinite;
  text-shadow: 0 0 8px rgba(224, 187, 255, 0.6);
}

.p1 { top: 12%; left: 8%; animation-delay: 0s; }
.p2 { top: 22%; left: 22%; animation-delay: 1.8s; font-size: 24px; }
.p3 { top: 8%; left: 48%; animation-delay: 3.5s; }
.p4 { top: 35%; left: 68%; animation-delay: 0.8s; }
.p5 { top: 52%; left: 15%; animation-delay: 5.2s; }
.p6 { top: 48%; left: 82%; animation-delay: 2.8s; }
.p7 { top: 68%; left: 28%; animation-delay: 7s; font-size: 18px; }
.p8 { top: 18%; left: 78%; animation-delay: 4.5s; }
.p9 { top: 62%; left: 88%; animation-delay: 6.5s; }
.p10 { top: 25%; left: 5%; animation-delay: 9s; }

@keyframes float-particle {
  0% {
    transform: translateY(0) rotate(0deg);
    opacity: 0.65;
  }
  100% {
    transform: translateY(-900px) rotate(720deg);
    opacity: 0;
  }
}

/* Side Decorations */
.side-trophy {
  position: absolute;
  left: 6%;
  bottom: 12%;
  font-size: 190px;
  opacity: 0.11;
  z-index: 1;
  filter: drop-shadow(0 0 50px #a855f7);
  animation: trophy-float 9s ease-in-out infinite;
}

.side-chart {
  position: absolute;
  right: 6%;
  bottom: 15%;
  font-size: 150px;
  opacity: 0.11;
  z-index: 1;
  animation: chart-float 11s ease-in-out infinite;
}

@keyframes trophy-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-30px); }
}

@keyframes chart-float {
  0%, 100% { transform: translateY(0) rotate(3deg); }
  50% { transform: translateY(-25px) rotate(-3deg); }
}

/* Podium */
.podium {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 32px;
  margin: 40px 0 70px;
}

.podium-card {
  position: relative;
  width: 245px;
  padding: 28px 22px 24px;
  border-radius: 22px;
  text-align: center;
  background: rgba(30, 20, 55, 0.9);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.1);
  transition: all 0.4s ease;
}

.first {
  width: 280px;
  padding-top: 55px;
  transform: translateY(-35px);
  background: linear-gradient(180deg, #4c1d95, #1e1438);
  border: 2px solid #fbbf24;
  box-shadow: 0 0 70px rgba(251, 191, 36, 0.55);
}

.podium-card:hover {
  transform: translateY(-12px);
}

.crown {
  position: absolute;
  top: -38px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 52px;
  filter: drop-shadow(0 12px 18px #fbbf24);
  animation: crown-bob 2.2s infinite ease-in-out;
}

@keyframes crown-bob {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(-10px); }
}

.rank-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 16px;
  color: #111827;
}

.gold { background: #fbbf24; }
.silver { background: #cbd5e1; }
.bronze { background: #f59e0b; }

.avatar {
  width: 80px;
  height: 80px;
  margin: 12px auto;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  font-weight: bold;
  color: white;
  box-shadow: 0 0 30px rgba(147, 51, 234, 0.7);
}

.gold .avatar { width: 92px; height: 92px; font-size: 34px; box-shadow: 0 0 40px #fbbf24; }

/* Leaderboard */
.leaderboard-card {
  position: relative;
  z-index: 2;
  background: rgba(20, 18, 40, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  border: 1px solid rgba(255,255,255,0.08);
  overflow: hidden;
}

.leader-table {
  width: 100%;
  border-collapse: collapse;
}

.leader-table th {
  padding: 20px 24px;
  text-align: left;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #a5b4fc;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.leader-table td {
  padding: 18px 24px;
  vertical-align: middle;
}

.leader-row {
  transition: background 0.3s;
}

.leader-row:hover {
  background: rgba(168, 85, 247, 0.15);
}

.leader-row.me {
  background: linear-gradient(90deg, rgba(168, 85, 247, 0.28), transparent);
  border-left: 4px solid #a855f7;
}

.avatar-small {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-right: 16px;
  float: left;
}

.name {
  font-weight: 700;
  font-size: 16.5px;
}

.level {
  font-size: 13.5px;
  color: #94a3b8;
}

.xp-cell {
  text-align: right;
  font-size: 21px;
  font-weight: 800;
  color: #c084fc;
}

.show-more {
  width: 100%;
  padding: 18px;
  background: transparent;
  border: none;
  color: #c4b5fd;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.show-more:hover {
  background: rgba(168, 85, 247, 0.12);
}
</style>