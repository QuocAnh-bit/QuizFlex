<template>
  <section
    ref="flashcardContainerRef"
    class="mx-auto max-w-4xl py-4 space-y-6 transition-colors duration-300"
    :class="{
      'fixed inset-0 z-50 m-0 max-w-none h-screen overflow-y-auto bg-slate-900/95 p-4 sm:p-8 flex flex-col justify-start': isFullscreen,
    }"
  >
    <!-- 1. LOADING STATE -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang chuẩn bị bộ thẻ học tập..."
      message="Vui lòng chờ trong giây lát để hệ thống xử lý dữ liệu quiz."
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải bộ thẻ học tập"
      :message="errorMessage"
      @retry="loadQuizDetails"
    >
      <template #actions>
        <button
          type="button"
          class="btn-ghost text-xs inline-flex items-center gap-1.5"
          @click="goBack"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          Quay lại
        </button>
      </template>
    </AppErrorState>

    <!-- 3. LOADED STATE -->
    <template v-else>
      <!-- Header Navigation & Refined Study Toolbar -->
      <div
        class="px-4 py-2.5 sm:px-5 sm:py-3 flex flex-wrap items-center justify-between gap-3 shrink-0 rounded-2xl border transition-all duration-200"
        :class="
          isFullscreen
            ? 'bg-slate-800/95 border-slate-700 text-slate-200 shadow-xl backdrop-blur-md'
            : 'card shadow-xs border-slate-200/90 bg-white/90 backdrop-blur-sm'
        "
      >
        <!-- Left Group: Back & Audio Capsule -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Back Button -->
          <button
            type="button"
            class="h-8 px-3 rounded-xl border text-xs font-semibold inline-flex items-center gap-1.5 transition active:scale-95"
            :class="
              isFullscreen
                ? 'border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700'
                : 'border-slate-200/80 bg-slate-50/80 text-slate-700 hover:bg-slate-100'
            "
            @click="goBack"
          >
            <ArrowLeft class="h-3.5 w-3.5" />
            <span>Quay lại</span>
          </button>

          <div
            class="hidden sm:block h-4 w-[1px]"
            :class="isFullscreen ? 'bg-slate-700' : 'bg-slate-200'"
          ></div>

          <!-- Audio Segmented Capsule -->
          <div
            class="flex items-center gap-0.5 rounded-xl border p-1 text-xs"
            :class="
              isFullscreen
                ? 'border-slate-700/80 bg-slate-900/60'
                : 'border-slate-200/80 bg-slate-50/90'
            "
          >
            <!-- Auto-play TTS button -->
            <button
              type="button"
              class="h-7 rounded-lg px-2.5 font-semibold transition active:scale-95 inline-flex items-center gap-1.5 text-xs"
              :class="
                isAutoPlayAudio
                  ? isFullscreen
                    ? 'bg-slate-700 text-purple-300 shadow-xs font-bold'
                    : 'bg-white text-[#7C3AED] shadow-xs font-bold'
                  : isFullscreen
                  ? 'text-slate-400 hover:text-slate-200'
                  : 'text-slate-600 hover:text-slate-900'
              "
              title="Tự động đọc khi lật/chuyển thẻ"
              @click="toggleAutoPlayAudio"
            >
              <Volume2 v-if="isAutoPlayAudio" class="h-3.5 w-3.5" />
              <VolumeX v-else class="h-3.5 w-3.5 opacity-60" />
              <span class="hidden md:inline">Tự phát âm</span>
            </button>

            <!-- Audio Rate button -->
            <button
              type="button"
              class="h-7 rounded-lg px-2 font-semibold transition active:scale-95 inline-flex items-center gap-1 text-xs"
              :class="
                audioRate < 1.0
                  ? isFullscreen
                    ? 'bg-slate-700 text-indigo-300 shadow-xs font-bold'
                    : 'bg-white text-indigo-700 shadow-xs font-bold'
                  : isFullscreen
                  ? 'text-slate-400 hover:text-slate-200'
                  : 'text-slate-600 hover:text-slate-900'
              "
              title="Tốc độ đọc giọng nói"
              @click="toggleAudioRate"
            >
              <Turtle v-if="audioRate === 0.45" class="h-3.5 w-3.5" />
              <Gauge v-else class="h-3.5 w-3.5 opacity-70" />
              <span>{{ audioRate }}x</span>
            </button>

            <!-- SFX button -->
            <button
              type="button"
              class="h-7 rounded-lg px-2 font-semibold transition active:scale-95 inline-flex items-center gap-1 text-xs"
              :class="
                isSoundEffects
                  ? isFullscreen
                    ? 'bg-slate-700 text-amber-300 shadow-xs font-bold'
                    : 'bg-white text-amber-700 shadow-xs font-bold'
                  : isFullscreen
                  ? 'text-slate-500 hover:text-slate-300'
                  : 'text-slate-400 hover:text-slate-700'
              "
              title="Bật/Tắt hiệu ứng âm thanh"
              @click="toggleSoundEffects"
            >
              <Bell v-if="isSoundEffects" class="h-3.5 w-3.5" />
              <BellOff v-else class="h-3.5 w-3.5 opacity-60" />
              <span class="hidden lg:inline">Hiệu ứng</span>
            </button>
          </div>
        </div>

        <!-- Right Group: Study Modes & Utilities -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Study Modes Segmented Capsule -->
          <div
            class="flex items-center gap-0.5 rounded-xl border p-1 text-xs"
            :class="
              isFullscreen
                ? 'border-slate-700/80 bg-slate-900/60'
                : 'border-slate-200/80 bg-slate-50/90'
            "
          >
            <!-- 1. Shuffle Button -->
            <button
              type="button"
              class="h-7 rounded-lg px-2.5 font-semibold transition active:scale-95 inline-flex items-center gap-1.5 text-xs"
              :class="
                isShuffled
                  ? isFullscreen
                    ? 'bg-slate-700 text-purple-300 shadow-xs font-bold'
                    : 'bg-white text-[#7C3AED] shadow-xs font-bold'
                  : isFullscreen
                  ? 'text-slate-400 hover:text-slate-200'
                  : 'text-slate-600 hover:text-slate-900'
              "
              title="Trộn ngẫu nhiên câu hỏi (Phím tắt: S)"
              @click="toggleShuffle"
            >
              <Shuffle class="h-3.5 w-3.5" />
              <span class="hidden sm:inline">Trộn</span>
            </button>

            <!-- 2. Swap Front/Back Button -->
            <button
              type="button"
              class="h-7 rounded-lg px-2.5 font-semibold transition active:scale-95 inline-flex items-center gap-1.5 text-xs"
              :class="
                isSwappedSides
                  ? isFullscreen
                    ? 'bg-slate-700 text-indigo-300 shadow-xs font-bold'
                    : 'bg-white text-indigo-700 shadow-xs font-bold'
                  : isFullscreen
                  ? 'text-slate-400 hover:text-slate-200'
                  : 'text-slate-600 hover:text-slate-900'
              "
              title="Đảo mặt thẻ: Nhìn đáp án đoán câu hỏi"
              @click="toggleSwapSides"
            >
              <ArrowLeftRight class="h-3.5 w-3.5" />
              <span class="hidden sm:inline">{{ isSwappedSides ? 'Mặt đáp án' : 'Mặt câu hỏi' }}</span>
            </button>

            <!-- 3. Slideshow Action + Pace -->
            <div class="flex items-center gap-0.5 pl-0.5 border-l border-slate-200 dark:border-slate-700">
              <button
                type="button"
                class="h-7 rounded-lg px-2 font-semibold transition active:scale-95 inline-flex items-center gap-1 text-xs"
                :class="
                  isAutoSlideshow
                    ? 'bg-emerald-600 text-white font-bold shadow-xs animate-pulse'
                    : isFullscreen
                    ? 'text-slate-400 hover:text-slate-200'
                    : 'text-slate-600 hover:text-slate-900'
                "
                title="Tự động lật và chuyển thẻ thông minh"
                @click="toggleAutoSlideshow"
              >
                <Pause v-if="isAutoSlideshow" class="h-3.5 w-3.5" />
                <Play v-else class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">{{ isAutoSlideshow ? 'Dừng' : 'Tự chạy' }}</span>
              </button>

              <button
                type="button"
                class="h-7 rounded-lg px-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition"
                title="Đổi tốc độ chờ: Thong thả / Tiêu chuẩn / Nhanh"
                @click="cycleSlideshowPace"
              >
                {{ slideshowPaceLabel }}
              </button>
            </div>
          </div>

          <div
            class="hidden sm:block h-4 w-[1px]"
            :class="isFullscreen ? 'bg-slate-700' : 'bg-slate-200'"
          ></div>

          <!-- Utility Icons: Fullscreen & Keyboard Help -->
          <div class="flex items-center gap-1">
            <!-- Fullscreen Button -->
            <button
              type="button"
              class="h-8 w-8 rounded-xl border grid place-items-center transition active:scale-95"
              :class="
                isFullscreen
                  ? 'border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700'
                  : 'border-slate-200/80 bg-slate-50/80 text-slate-600 hover:bg-slate-100'
              "
              title="Toàn màn hình tập trung (Phím tắt: F)"
              @click="toggleFullscreen"
            >
              <Minimize2 v-if="isFullscreen" class="h-3.5 w-3.5" />
              <Maximize2 v-else class="h-3.5 w-3.5" />
            </button>

            <!-- Keyboard Help Button -->
            <button
              type="button"
              class="h-8 w-8 rounded-xl border grid place-items-center transition active:scale-95"
              :class="
                isFullscreen
                  ? 'border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700'
                  : 'border-slate-200/80 bg-slate-50/80 text-slate-600 hover:bg-slate-100'
              "
              title="Xem bảng phím tắt"
              @click="showShortcutsModal = true"
            >
              <Keyboard class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div
        v-if="questions.length === 0"
        class="card p-10 text-center text-sm font-semibold text-slate-500 my-auto"
      >
        Quiz này không có câu hỏi nào để tạo thẻ ghi nhớ.
      </div>

      <!-- ======================================================== -->
      <!-- 4A. FINISHED STATE: FULLSCREEN MODE (Dark Center Modal) -->
      <!-- ======================================================== -->
      <div
        v-else-if="isFinished && isFullscreen"
        class="flex-1 flex items-center justify-center py-6 w-full"
      >
        <div
          class="w-full max-w-2xl p-8 sm:p-12 rounded-2xl text-center space-y-6 bg-slate-800 border border-slate-700 text-white shadow-2xl ring-1 ring-purple-500/20 animate-in fade-in zoom-in-95"
        >
          <span
            class="inline-flex items-center gap-2 rounded-full bg-purple-950/70 border border-purple-500/40 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-purple-300 shadow-sm"
          >
            <PartyPopper class="h-4 w-4" />
            Hoàn thành bài ôn tập!
          </span>

          <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight">
            Tuyệt vời, bạn đã hoàn thành bộ flashcard!
          </h1>

          <p class="text-sm text-slate-300 max-w-md mx-auto leading-relaxed">
            Bạn đã duyệt qua toàn bộ các câu hỏi trong bộ thẻ ghi nhớ của quiz:
            <b class="text-purple-400 block mt-1 text-base">{{ quizMeta.title }}</b>
          </p>

          <!-- Stats 3 Columns (Dark theme) -->
          <div class="mx-auto grid max-w-lg grid-cols-3 gap-3.5 pt-2">
            <div class="rounded-xl border border-emerald-600/40 bg-emerald-950/60 p-3.5 text-center">
              <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-300">
                Đã thuộc
              </span>
              <b class="mt-1 block text-2xl font-black text-emerald-400">
                {{ masteredQuestions.length }} / {{ totalCount }}
              </b>
            </div>

            <div class="rounded-xl border border-red-600/40 bg-red-950/60 p-3.5 text-center">
              <span class="text-[11px] font-bold uppercase tracking-wider text-red-300">
                Cần ôn lại
              </span>
              <b class="mt-1 block text-2xl font-black text-red-400">
                {{ needReviewQuestions.length }} / {{ totalCount }}
              </b>
            </div>

            <div class="rounded-xl border border-amber-600/40 bg-amber-950/60 p-3.5 text-center">
              <span class="text-[11px] font-bold uppercase tracking-wider text-amber-300">
                Đã gắn sao ⭐
              </span>
              <b class="mt-1 block text-2xl font-black text-amber-400">
                {{ starredQuestions.length }} / {{ totalCount }}
              </b>
            </div>
          </div>

          <!-- Actions (Dark theme) -->
          <div class="flex flex-wrap justify-center gap-3 pt-3">
            <button
              type="button"
              class="btn-primary text-xs sm:text-sm px-6 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-md hover:shadow-purple-500/30 transition active:scale-95"
              @click="restartAll"
            >
              <RotateCcw class="h-4 w-4" />
              Ôn lại tất cả
            </button>

            <button
              v-if="needReviewQuestions.length > 0"
              type="button"
              class="rounded-xl border border-red-700/80 bg-red-950/60 px-5 py-2.5 text-xs sm:text-sm font-bold text-red-300 hover:bg-red-900/60 transition inline-flex items-center gap-2 active:scale-95"
              @click="restartOnlyWeak"
            >
              <Flame class="h-4 w-4 text-red-400" />
              Chỉ ôn thẻ chưa thuộc ({{ needReviewQuestions.length }})
            </button>

            <button
              v-if="starredQuestions.length > 0"
              type="button"
              class="rounded-xl border border-amber-700/80 bg-amber-950/60 px-5 py-2.5 text-xs sm:text-sm font-bold text-amber-300 hover:bg-amber-900/60 transition inline-flex items-center gap-2 active:scale-95"
              @click="restartOnlyStarred"
            >
              <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
              Chỉ ôn thẻ gắn sao ({{ starredQuestions.length }})
            </button>

            <button
              type="button"
              class="rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-xs sm:text-sm text-slate-300 hover:text-white hover:bg-slate-700 transition inline-flex items-center gap-2"
              @click="goBack"
            >
              <ArrowLeft class="h-4 w-4" />
              Quay lại Quiz
            </button>
          </div>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- 4B. FINISHED STATE: NORMAL VIEW (Classic Light Card)    -->
      <!-- ======================================================== -->
      <div
        v-else-if="isFinished && !isFullscreen"
        class="card p-8 sm:p-12 text-center space-y-6 shadow-sm border border-slate-200 bg-white"
      >
        <span
          class="inline-flex items-center gap-2 rounded-full bg-purple-50 border border-purple-200 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-[#7C3AED]"
        >
          <PartyPopper class="h-4 w-4" />
          Hoàn thành bài ôn tập!
        </span>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900">
          Tuyệt vời, bạn đã hoàn thành bộ flashcard!
        </h1>

        <p class="text-sm text-slate-600 max-w-md mx-auto">
          Bạn đã duyệt qua toàn bộ các câu hỏi trong bộ thẻ ghi nhớ của quiz:
          <b class="text-[#7C3AED]">{{ quizMeta.title }}</b>.
        </p>

        <!-- Stats 3 Columns (Light Theme) -->
        <div class="mx-auto grid max-w-md grid-cols-3 gap-3">
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">
              Đã thuộc
            </span>
            <b class="mt-1 block text-2xl font-black text-emerald-700">
              {{ masteredQuestions.length }} / {{ totalCount }}
            </b>
          </div>

          <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-red-800">
              Cần ôn lại
            </span>
            <b class="mt-1 block text-2xl font-black text-red-700">
              {{ needReviewQuestions.length }} / {{ totalCount }}
            </b>
          </div>

          <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">
              Đã gắn sao ⭐
            </span>
            <b class="mt-1 block text-2xl font-black text-amber-700">
              {{ starredQuestions.length }} / {{ totalCount }}
            </b>
          </div>
        </div>

        <!-- Actions (Light Theme) -->
        <div class="flex flex-wrap justify-center gap-3 pt-4">
          <button
            type="button"
            class="btn-primary text-xs inline-flex items-center gap-1.5"
            @click="restartAll"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Ôn lại tất cả
          </button>

          <button
            v-if="needReviewQuestions.length > 0"
            type="button"
            class="btn-secondary text-xs text-red-600 font-bold hover:bg-red-50 inline-flex items-center gap-1.5"
            @click="restartOnlyWeak"
          >
            <Flame class="h-3.5 w-3.5" />
            Chỉ ôn thẻ chưa thuộc ({{ needReviewQuestions.length }})
          </button>

          <button
            v-if="starredQuestions.length > 0"
            type="button"
            class="btn-secondary text-xs text-amber-700 font-bold hover:bg-amber-50 inline-flex items-center gap-1.5"
            @click="restartOnlyStarred"
          >
            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
            Chỉ ôn thẻ gắn sao ({{ starredQuestions.length }})
          </button>

          <button
            type="button"
            class="btn-ghost text-xs inline-flex items-center gap-1.5"
            @click="goBack"
          >
            <ArrowLeft class="h-3.5 w-3.5" />
            Quay lại Quiz
          </button>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- 5. ACTIVE FLASHCARD AREA                                 -->
      <!-- ======================================================== -->
      <div
        v-else
        class="space-y-6"
        :class="{ 'flex-1 flex flex-col justify-center my-auto w-full': isFullscreen }"
      >
        <!-- Progress Header & Slideshow Status -->
        <div
          class="flex items-center justify-between text-xs font-semibold"
          :class="isFullscreen ? 'text-slate-300' : 'text-slate-600'"
        >
          <div class="flex items-center gap-2 flex-wrap">
            <span
              class="rounded-full bg-purple-50 px-3.5 py-1 font-bold text-[#7C3AED] inline-flex items-center gap-1.5 border border-purple-100"
            >
              <ListChecks class="h-3.5 w-3.5" />
              Thẻ {{ currentIndex + 1 }} / {{ activeList.length }}
            </span>

            <span
              v-if="isReviewingMode"
              class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 border border-amber-200"
            >
              {{ isReviewingMode === 'weak' ? 'Chế độ: Thẻ chưa thuộc' : 'Chế độ: Thẻ gắn sao' }}
            </span>

            <!-- Guest Preview Badge -->
            <span
              v-if="!isLoggedIn"
              class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 border border-amber-200 inline-flex items-center gap-1 cursor-pointer hover:bg-amber-100 transition"
              title="Tài khoản khách: Dùng thử 5 thẻ. Nhấp để đăng nhập!"
              @click="goToLogin"
            >
              <span>🔒 Dùng thử: 5 thẻ</span>
            </span>

            <!-- Slideshow Status Indicator -->
            <span
              v-if="isAutoSlideshow"
              class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 animate-pulse"
            >
              <Play class="h-2.5 w-2.5 fill-emerald-600" />
              {{ slideshowStatusText }}
            </span>
          </div>

          <div>
            Đã thuộc:
            <b class="text-emerald-700 font-bold">{{ masteredQuestions.length }}</b>
            · Cần ôn:
            <b class="text-red-700 font-bold">{{ needReviewQuestions.length }}</b>
            <span v-if="starredQuestions.length > 0" class="ml-2 text-amber-600">
              · ⭐ <b>{{ starredQuestions.length }}</b>
            </span>
          </div>
        </div>

        <!-- Progress Bar -->
        <div
          class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200 shadow-inner"
          :class="{ 'bg-slate-800 border-slate-700': isFullscreen }"
        >
          <div
            class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
            :style="{ width: `${progressPercent}%` }"
          ></div>
        </div>

        <!-- Guest Preview Notice Banner -->
        <div
          v-if="!isLoggedIn && questions.length > 5"
          class="rounded-2xl border border-amber-200/90 bg-amber-50/90 p-3 text-xs text-amber-800 flex items-center justify-between gap-3 shadow-xs backdrop-blur-sm"
          :class="{ 'bg-slate-800/90 border-amber-500/40 text-amber-200': isFullscreen }"
        >
          <div class="flex items-center gap-2">
            <span class="text-base">🔒</span>
            <span>
              Bạn đang học thử <b>5 thẻ đầu tiên</b> (trong tổng số {{ questions.length }} thẻ).
            </span>
          </div>
          <button
            type="button"
            class="h-7 px-3 rounded-lg bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-bold text-xs shrink-0 transition active:scale-95 shadow-xs cursor-pointer"
            @click="goToLogin"
          >
            Đăng nhập mở khóa
          </button>
        </div>

        <!-- 3D CARD (Harmonious immersive design for both Normal and Fullscreen) -->
        <div
          class="perspective-container mx-auto w-full transition-all duration-300"
          :class="isFullscreen ? 'max-w-3xl h-[400px] sm:h-[460px]' : 'max-w-2xl h-[360px] sm:h-[400px]'"
          @click="handleManualCardFlip"
        >
          <div
            class="flashcard-inner relative w-full h-full cursor-pointer transition-transform duration-500"
            :class="{ 'flashcard-flipped': isFlipped }"
          >
            <!-- FRONT -->
            <div
              class="flashcard-front absolute inset-0 rounded-3xl p-6 sm:p-9 flex flex-col justify-between shadow-xl transition-all duration-300"
              :class="
                isFullscreen
                  ? 'bg-slate-900/95 border border-slate-700/80 text-white shadow-2xl backdrop-blur-2xl ring-1 ring-purple-500/20'
                  : 'bg-white border border-slate-200/90 text-slate-900 hover:border-purple-300 hover:shadow-2xl'
              "
            >
              <!-- Front Header -->
              <div
                class="flex items-center justify-between text-xs font-bold uppercase tracking-wider"
                :class="isFullscreen ? 'text-purple-300' : 'text-slate-400'"
              >
                <span class="inline-flex items-center gap-1.5 font-bold">
                  {{ isSwappedSides ? 'Đáp án / Gợi ý' : 'Câu hỏi' }}
                </span>

                <div class="flex items-center gap-2">
                  <!-- Star button -->
                  <button
                    type="button"
                    class="rounded-xl p-2 transition active:scale-90 cursor-pointer"
                    :class="
                      isCurrentCardStarred
                        ? isFullscreen
                          ? 'text-amber-400 bg-amber-950/50 border border-amber-500/40'
                          : 'text-amber-500 bg-amber-50 border border-amber-200'
                        : isFullscreen
                        ? 'text-slate-400 hover:text-amber-400 hover:bg-slate-800'
                        : 'text-slate-400 hover:text-amber-500 hover:bg-slate-100'
                    "
                    title="Gắn sao thẻ này"
                    @click.stop="toggleCurrentStar"
                  >
                    <Star
                      class="h-4 w-4"
                      :class="{ 'fill-amber-400': isCurrentCardStarred }"
                    />
                  </button>

                  <!-- Speak button -->
                  <button
                    type="button"
                    class="rounded-xl border px-3 py-1.5 text-xs font-semibold inline-flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs"
                    :class="
                      isFullscreen
                        ? 'border-slate-700 bg-slate-800/80 text-slate-200 hover:bg-slate-700 hover:text-white'
                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
                    "
                    title="Đọc nội dung (Phím tắt: R)"
                    @click.stop="() => speakFrontText()"
                  >
                    <Volume2 class="h-3.5 w-3.5 text-purple-400" />
                    <span>Đọc</span>
                  </button>

                  <span
                    class="inline-flex items-center gap-1 text-[11px] font-semibold"
                    :class="isFullscreen ? 'text-slate-400' : 'text-slate-400'"
                  >
                    <Lightbulb class="h-3.5 w-3.5 text-amber-400" />
                    <span>Nhấp để lật</span>
                  </span>
                </div>
              </div>

              <!-- Front Content -->
              <div class="flex-1 flex items-center justify-center py-3 px-2 text-center overflow-y-auto scrollbar-soft">
                <!-- Case Normal: Question -->
                <div v-if="!isSwappedSides" class="space-y-2.5 flex flex-col items-center justify-center max-w-full">
                  <h3
                    class="font-bold leading-relaxed line-clamp-4 select-none transition-all"
                    :class="[
                      isFullscreen
                        ? 'text-xl sm:text-2xl lg:text-3xl text-white font-black drop-shadow-sm'
                        : 'text-lg sm:text-xl text-slate-900'
                    ]"
                  >
                    {{ currentCard.question }}
                  </h3>

                  <!-- Flashcard Question Image (Centered, elegant & zoomable without flipping card) -->
                  <div v-if="currentCard.image_url" class="pt-2 flex justify-center w-full" @click.stop>
                    <QuestionImage
                      :src="currentCard.image_url"
                      size="compact"
                      max-height="max-h-28 sm:max-h-36 max-w-[280px]"
                      rounded="rounded-xl"
                      container-bg-class="bg-slate-50/70"
                      container-border-class="border border-slate-200/80 shadow-2xs hover:border-purple-300"
                      allow-zoom
                    />
                  </div>
                </div>

                <!-- Case Swapped: Answer as prompt -->
                <div v-else class="space-y-2 text-center">
                  <p
                    class="font-bold leading-relaxed"
                    :class="[
                      isFullscreen
                        ? 'text-xl sm:text-2xl text-emerald-400 font-black'
                        : 'text-lg sm:text-xl text-emerald-700'
                    ]"
                  >
                    {{ getCorrectAnswerText(currentCard) }}
                  </p>
                </div>
              </div>

              <!-- Front Footer -->
              <div
                class="text-center text-xs font-medium pt-2 border-t"
                :class="isFullscreen ? 'border-slate-800 text-slate-400' : 'border-slate-100 text-slate-400'"
              >
                {{ isSwappedSides ? 'Nhấp vào thẻ để xem câu hỏi gốc' : 'Nhấp vào thẻ để xem đáp án đúng' }}
              </div>
            </div>

            <!-- BACK -->
            <div
              class="flashcard-back absolute inset-0 rounded-3xl p-6 sm:p-9 flex flex-col justify-between shadow-xl transition-all duration-300"
              :class="
                isFullscreen
                  ? 'bg-slate-900/95 border border-purple-500/40 text-white shadow-2xl backdrop-blur-2xl ring-1 ring-purple-500/20'
                  : 'bg-purple-50/50 border border-purple-200/90 text-slate-900 hover:border-purple-300 hover:shadow-2xl'
              "
            >
              <!-- Back Header -->
              <div
                class="flex items-center justify-between text-xs font-bold uppercase tracking-wider"
                :class="isFullscreen ? 'text-purple-300' : 'text-slate-500'"
              >
                <span class="font-bold">{{ isSwappedSides ? 'Câu hỏi gốc' : 'Đáp án chính xác' }}</span>

                <div class="flex items-center gap-2">
                  <!-- Star button -->
                  <button
                    type="button"
                    class="rounded-xl p-2 transition active:scale-90 cursor-pointer"
                    :class="
                      isCurrentCardStarred
                        ? isFullscreen
                          ? 'text-amber-400 bg-amber-950/50 border border-amber-500/40'
                          : 'text-amber-500 bg-amber-100 border border-amber-200'
                        : isFullscreen
                        ? 'text-slate-400 hover:text-amber-400 hover:bg-slate-800'
                        : 'text-slate-400 hover:text-amber-500 hover:bg-purple-100'
                    "
                    title="Gắn sao thẻ này"
                    @click.stop="toggleCurrentStar"
                  >
                    <Star
                      class="h-4 w-4"
                      :class="{ 'fill-amber-400': isCurrentCardStarred }"
                    />
                  </button>

                  <!-- Speak button -->
                  <button
                    type="button"
                    class="rounded-xl border px-3 py-1.5 text-xs font-bold inline-flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs"
                    :class="
                      isFullscreen
                        ? 'border-purple-700/60 bg-purple-950/60 text-purple-200 hover:bg-purple-900/60 hover:text-white'
                        : 'border-purple-200 bg-purple-100 text-[#7C3AED] hover:bg-purple-200'
                    "
                    title="Đọc nội dung (Phím tắt: R)"
                    @click.stop="() => speakBackText()"
                  >
                    <Volume2 class="h-3.5 w-3.5 text-purple-400" />
                    <span>Đọc đáp án</span>
                  </button>

                  <span
                    class="font-bold inline-flex items-center gap-1 text-[11px]"
                    :class="isFullscreen ? 'text-emerald-400' : 'text-emerald-700'"
                  >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    <span>Đã lật</span>
                  </span>
                </div>
              </div>

              <!-- Back Content -->
              <div class="flex-1 flex flex-col justify-center py-2 space-y-3 overflow-hidden">
                <!-- Case Normal: Question snippet + Answer list -->
                <template v-if="!isSwappedSides">
                  <p
                    class="text-xs sm:text-sm font-semibold line-clamp-2 text-center px-1"
                    :class="isFullscreen ? 'text-slate-300' : 'text-slate-600'"
                  >
                    {{ currentCard.question }}
                  </p>

                  <div
                    class="grid gap-2 overflow-y-auto pr-1 scrollbar-soft"
                    :class="isFullscreen ? 'max-h-[220px]' : 'max-h-[190px]'"
                  >
                    <div
                      v-for="answer in currentCard.answers"
                      :key="answer.key"
                      class="flex items-center gap-3 rounded-2xl border px-4 py-2.5 text-xs sm:text-sm font-semibold transition"
                      :class="
                        answer.isCorrect
                          ? isFullscreen
                            ? 'border-emerald-500/60 bg-emerald-950/60 text-emerald-200 shadow-md ring-1 ring-emerald-500/30 font-bold'
                            : 'border-emerald-300 bg-emerald-50 text-emerald-900 shadow-sm font-bold'
                          : isFullscreen
                          ? 'border-slate-800/80 bg-slate-800/40 text-slate-400 opacity-50'
                          : 'border-slate-200 bg-white text-slate-500 opacity-60'
                      "
                    >
                      <span
                        class="grid h-6 w-6 sm:h-7 sm:w-7 shrink-0 place-items-center rounded-xl text-xs font-black shadow-xs"
                        :class="
                          answer.isCorrect
                            ? 'bg-emerald-600 text-white'
                            : isFullscreen
                            ? 'bg-slate-800 text-slate-300 border border-slate-700'
                            : 'bg-slate-100 text-slate-600'
                        "
                      >
                        {{ answer.key }}
                      </span>

                      <span class="flex-1 leading-snug">
                        {{ answer.text }}
                      </span>

                      <span
                        v-if="answer.isCorrect"
                        class="font-bold shrink-0 inline-flex items-center gap-1.5 text-xs"
                        :class="isFullscreen ? 'text-emerald-400' : 'text-emerald-700'"
                      >
                        <CheckCircle2 class="h-4 w-4" />
                        Đúng
                      </span>
                    </div>
                  </div>
                </template>

                <!-- Case Swapped: Show full question -->
                <template v-else>
                  <div class="text-center space-y-2 py-4 px-2">
                    <h3
                      class="font-bold leading-relaxed"
                      :class="[
                        isFullscreen
                          ? 'text-lg sm:text-2xl text-white font-black'
                          : 'text-base sm:text-lg text-slate-900'
                      ]"
                    >
                      {{ currentCard.question }}
                    </h3>
                  </div>
                </template>
              </div>

              <!-- Back Footer -->
              <div
                class="text-center text-xs font-medium pt-2 border-t"
                :class="isFullscreen ? 'border-purple-900/40 text-slate-400' : 'border-purple-100 text-slate-400'"
              >
                Đánh dấu mức độ ghi nhớ của bạn ở nút bên dưới
              </div>
            </div>
          </div>
        </div>

        <!-- Controls Action Buttons -->
        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            type="button"
            class="btn-danger text-xs sm:text-sm px-6 sm:px-8 py-2.5 rounded-xl inline-flex items-center gap-2 transition active:scale-95 shadow-sm"
            title="Chưa thuộc (Phím tắt: 1 hoặc Mũi tên Trái)"
            @click.stop="() => markAnswer(false)"
          >
            <XCircle class="h-4 w-4 sm:h-5 sm:w-5" />
            <span>Chưa thuộc</span>
            <kbd class="hidden sm:inline-block rounded bg-red-700/30 px-1.5 py-0.5 text-[10px] font-bold">1</kbd>
          </button>

          <button
            type="button"
            class="btn-success text-xs sm:text-sm px-6 sm:px-8 py-2.5 rounded-xl inline-flex items-center gap-2 transition active:scale-95 shadow-sm"
            title="Đã thuộc (Phím tắt: 2 hoặc Mũi tên Phải)"
            @click.stop="() => markAnswer(true)"
          >
            <CheckCircle2 class="h-4 w-4 sm:h-5 sm:w-5" />
            <span>Đã thuộc</span>
            <kbd class="hidden sm:inline-block rounded bg-emerald-700/30 px-1.5 py-0.5 text-[10px] font-bold">2</kbd>
          </button>
        </div>

        <!-- Keyboard Shortcuts Mini Hint -->
        <div class="flex items-center justify-center gap-4 text-[11px] text-slate-400 font-medium pt-1">
          <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-semibold">Space</kbd> Lật</span>
          <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-semibold">← / 1</kbd> Chưa thuộc</span>
          <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-semibold">→ / 2</kbd> Đã thuộc</span>
          <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-semibold">R</kbd> Đọc lại</span>
          <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-semibold">S</kbd> Trộn</span>
        </div>
      </div>
    </template>

    <!-- Keyboard Shortcuts Modal -->
    <div
      v-if="showShortcutsModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
      @click.self="showShortcutsModal = false"
    >
      <div class="card max-w-md w-full p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between border-b pb-3">
          <div class="flex items-center gap-2">
            <Keyboard class="h-5 w-5 text-[#7C3AED]" />
            <h3 class="text-base font-bold text-slate-900">Phím tắt ôn tập Flashcard</h3>
          </div>
          <button
            type="button"
            class="rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100"
            @click="showShortcutsModal = false"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Lật mặt trước / sau thẻ</span>
            <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">Space</kbd>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Đánh dấu Chưa thuộc</span>
            <div class="flex gap-1">
              <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">←</kbd>
              <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">1</kbd>
            </div>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Đánh dấu Đã thuộc</span>
            <div class="flex gap-1">
              <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">→</kbd>
              <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">2</kbd>
            </div>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Đọc lại âm thanh (TTS)</span>
            <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">R</kbd>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Trộn ngẫu nhiên thẻ</span>
            <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">S</kbd>
          </div>

          <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
            <span class="font-medium text-slate-600">Bật / Tắt Toàn màn hình</span>
            <kbd class="px-2 py-1 rounded bg-slate-100 border border-slate-300 font-mono font-bold text-slate-700">F</kbd>
          </div>
        </div>

        <div class="pt-2 text-center">
          <button
            type="button"
            class="btn-primary text-xs w-full py-2"
            @click="showShortcutsModal = false"
          >
            Đã hiểu
          </button>
        </div>
      </div>
    </div>

    <!-- Guest Limit Modal (Hiển thị khi tài khoản Khách dùng quá 5 thẻ) -->
    <div
      v-if="showGuestLimitModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
      @click.self="showGuestLimitModal = false"
    >
      <div
        class="max-w-md w-full p-6 sm:p-8 space-y-6 text-center rounded-3xl shadow-2xl animate-in fade-in zoom-in duration-200 border"
        :class="isFullscreen ? 'bg-slate-900 border-slate-700 text-white' : 'bg-white border-slate-200 text-slate-900'"
      >
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-3xl text-[#7C3AED] shadow-inner">
          🔒
        </div>

        <div class="space-y-2">
          <h3
            class="text-xl font-black"
            :class="isFullscreen ? 'text-white' : 'text-slate-900'"
          >
            Bạn đã xem hết 5 thẻ dùng thử!
          </h3>
          <p
            class="text-xs leading-relaxed"
            :class="isFullscreen ? 'text-slate-300' : 'text-slate-600'"
          >
            Để tiếp tục ôn tập toàn bộ <b>{{ questions.length }} thẻ</b> của bộ câu hỏi này với đầy đủ tính năng: Tự động chạy, Trộn ngẫu nhiên, Phát âm AI và Lưu tiến độ học tập, vui lòng đăng nhập hoặc đăng ký tài khoản QuizFlex.
          </p>
        </div>

        <div class="space-y-3 pt-2">
          <button
            type="button"
            class="btn-primary w-full py-3 text-xs font-bold inline-flex items-center justify-center gap-2 shadow-md cursor-pointer"
            @click="goToLogin"
          >
            <span>Đăng nhập / Đăng ký ngay (Miễn phí)</span>
            <ArrowRight class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="btn-secondary w-full py-2.5 text-xs font-semibold cursor-pointer"
            @click="restartGuestPreview"
          >
            Ôn lại 5 thẻ vừa rồi
          </button>
        </div>
      </div>
    </div>

    <!-- Smart Resume Session Toast Notification (Phương án 3 - Non-blocking Toast) -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-4 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform translate-y-4 opacity-0 scale-95"
    >
      <div
        v-if="showResumeToast"
        class="fixed bottom-6 right-6 z-50 max-w-sm sm:max-w-md rounded-2xl border p-3.5 sm:p-4 shadow-2xl backdrop-blur-md flex items-center justify-between gap-3 animate-in fade-in slide-in-from-bottom-5"
        :class="
          isFullscreen
            ? 'bg-slate-800/95 border-purple-500/40 text-slate-100 shadow-purple-950/50 ring-1 ring-purple-500/30'
            : 'bg-white/95 border-purple-200 text-slate-900 shadow-xl ring-1 ring-purple-100'
        "
      >
        <div class="flex items-center gap-3 min-w-0">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/60 text-lg text-[#7C3AED]">
            ✨
          </div>
          <div class="min-w-0">
            <p class="text-xs font-bold leading-tight truncate">
              Đã khôi phục tại <b>Thẻ {{ resumedCardIndex }}</b>
            </p>
            <p class="text-[11px] opacity-75 truncate">
              Đã thuộc: {{ masteredQuestions.length }} · Cần ôn: {{ needReviewQuestions.length }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <button
            type="button"
            class="h-7 px-2.5 rounded-lg border text-[11px] font-bold inline-flex items-center gap-1 transition active:scale-95 cursor-pointer"
            :class="
              isFullscreen
                ? 'border-slate-700 bg-slate-700/80 hover:bg-slate-600 text-purple-300'
                : 'border-purple-200 bg-purple-50 hover:bg-purple-100 text-[#7C3AED]'
            "
            title="Xóa tiến độ và học lại từ thẻ số 1"
            @click="handleRestartFromToast"
          >
            <RotateCcw class="h-3 w-3" />
            <span>Học lại từ đầu</span>
          </button>

          <button
            type="button"
            class="h-7 w-7 rounded-lg grid place-items-center opacity-60 hover:opacity-100 transition cursor-pointer"
            title="Đóng thông báo"
            @click="dismissResumeToast"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  ArrowLeft,
  ArrowLeftRight,
  ArrowRight,
  Bell,
  BellOff,
  CheckCircle2,
  Flame,
  Gauge,
  Keyboard,
  Lightbulb,
  ListChecks,
  Maximize2,
  Minimize2,
  PartyPopper,
  Pause,
  Play,
  RotateCcw,
  Shuffle,
  Star,
  Turtle,
  Volume2,
  VolumeX,
  X,
  XCircle,
} from 'lucide-vue-next'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import QuestionImage from '@/components/question/QuestionImage.vue'
import {
  currentUserStorage,
  normalizeQuestion,
  normalizeQuizCard,
  quizzesApi,
  tokenStorage,
} from '@/services/api'
import speechService from '@/services/speechService'

const route = useRoute()
const router = useRouter()

// DOM & State
const flashcardContainerRef = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')
const isFlipped = ref(false)
const currentIndex = ref(0)
const isFinished = ref(false)

// Guest Preview Limit State (Tài khoản khách xem tối đa 5 thẻ)
const isLoggedIn = computed(() => {
  const token = tokenStorage.get()
  const user = currentUserStorage.get()
  if (!token || !user) return false
  const role = String(user.role || '').toLowerCase()
  return role !== 'guest'
})
const GUEST_PREVIEW_LIMIT = 5
const showGuestLimitModal = ref(false)

const goToLogin = () => {
  speechService.stop()
  clearAutoSlideshow()
  router.push({ path: '/login', query: { redirect: route.fullPath } })
}

const goToRegister = () => {
  speechService.stop()
  clearAutoSlideshow()
  router.push({ path: '/register', query: { redirect: route.fullPath } })
}

const restartGuestPreview = () => {
  speechService.stop()
  clearAutoSlideshow()
  showGuestLimitModal.value = false
  currentIndex.value = 0
  isFlipped.value = false
}

// Smart Resume Toast State (Phương án 3 - Non-blocking Toast)
const showResumeToast = ref(false)
const resumedCardIndex = ref(0)
let resumeToastTimer = null

const dismissResumeToast = () => {
  showResumeToast.value = false
  if (resumeToastTimer) {
    clearTimeout(resumeToastTimer)
    resumeToastTimer = null
  }
}

const handleRestartFromToast = () => {
  dismissResumeToast()
  restartAll()
}

// Tools state
const isShuffled = ref(false)
const isSwappedSides = ref(false)
const isAutoSlideshow = ref(false)
const isFullscreen = ref(false)
const showShortcutsModal = ref(false)

// Slideshow pacing: 'slow' (3.5s buffer) | 'normal' (2.2s buffer) | 'fast' (1.2s buffer)
const slideshowPace = ref(localStorage.getItem('flashcard_slideshow_pace') || 'normal')
const slideshowStatusText = ref('')
let slideshowTimeout = null

// Audio Settings
const isAutoPlayAudio = ref(
  localStorage.getItem('flashcard_autoplay_audio') === 'true'
)
const isSoundEffects = ref(
  localStorage.getItem('flashcard_sfx_enabled') !== 'false'
)
const audioRate = ref(
  parseFloat(localStorage.getItem('flashcard_audio_rate') || '1.0')
)
const activeSpeakingTarget = ref(null)

// Data
const quizMeta = ref({
  title: '',
  category: '',
  difficulty: '',
})

const questions = ref([])
const activeList = ref([])

const masteredQuestions = ref([])
const needReviewQuestions = ref([])
const starredQuestions = ref([])
const isReviewingMode = ref(null) // null | 'weak' | 'starred'

const totalCount = computed(() => questions.value.length)

const currentCard = computed(
  () =>
    activeList.value[currentIndex.value] || {
      id: null,
      question: '',
      answers: [],
    }
)

const isCurrentCardStarred = computed(() => {
  if (!currentCard.value || !currentCard.value.id) return false
  return starredQuestions.value.includes(currentCard.value.id)
})

const progressPercent = computed(() => {
  if (activeList.value.length === 0) return 0
  return Math.round((currentIndex.value / activeList.value.length) * 100)
})

// Helper: Get correct answer text
const getCorrectAnswerText = (card) => {
  if (!card || !card.answers) return ''
  const correct = card.answers.filter((a) => a.isCorrect)
  if (correct.length > 0) {
    return correct.map((a) => a.text).join('; ')
  }
  return card.answers[0]?.text || ''
}

// Slideshow buffer in milliseconds based on user preference
const getSlideshowBufferMs = () => {
  if (slideshowPace.value === 'slow') return 3500
  if (slideshowPace.value === 'fast') return 1200
  return 2200 // normal
}

const slideshowPaceLabel = computed(() => {
  if (slideshowPace.value === 'slow') return '🐢 Chậm'
  if (slideshowPace.value === 'fast') return '⚡ Nhanh'
  return '⏳ Vừa'
})

const cycleSlideshowPace = () => {
  if (slideshowPace.value === 'normal') {
    slideshowPace.value = 'slow'
  } else if (slideshowPace.value === 'slow') {
    slideshowPace.value = 'fast'
  } else {
    slideshowPace.value = 'normal'
  }
  localStorage.setItem('flashcard_slideshow_pace', slideshowPace.value)
}

// ----------------------------------------------------
// LOCAL STORAGE PERSISTENCE
// ----------------------------------------------------
const storageSessionKey = computed(() => `flashcard_session_${route.params.id}`)

const saveSessionState = () => {
  if (isLoading.value || questions.value.length === 0) return

  const state = {
    currentIndex: currentIndex.value,
    isFinished: isFinished.value,
    mastered: masteredQuestions.value,
    needReview: needReviewQuestions.value,
    starred: starredQuestions.value,
    isReviewingMode: isReviewingMode.value,
    isSwappedSides: isSwappedSides.value,
  }

  localStorage.setItem(storageSessionKey.value, JSON.stringify(state))
}

const restoreSessionState = () => {
  try {
    const raw = localStorage.getItem(storageSessionKey.value)
    if (!raw) return false

    const parsed = JSON.parse(raw)
    if (parsed && typeof parsed === 'object') {
      masteredQuestions.value = Array.isArray(parsed.mastered) ? parsed.mastered : []
      needReviewQuestions.value = Array.isArray(parsed.needReview) ? parsed.needReview : []
      starredQuestions.value = Array.isArray(parsed.starred) ? parsed.starred : []
      isSwappedSides.value = !!parsed.isSwappedSides

      if (parsed.isFinished) {
        isFinished.value = true
      } else if (
        typeof parsed.currentIndex === 'number' &&
        parsed.currentIndex >= 0 &&
        parsed.currentIndex < activeList.value.length
      ) {
        currentIndex.value = parsed.currentIndex
        
        // Nếu đang học dở từ thẻ 2 trở đi, hiển thị Toast thông báo trang nhã góc dưới
        if (parsed.currentIndex > 0) {
          resumedCardIndex.value = parsed.currentIndex + 1
          showResumeToast.value = true
          if (resumeToastTimer) clearTimeout(resumeToastTimer)
          resumeToastTimer = setTimeout(() => {
            showResumeToast.value = false
          }, 7000)
        }
      }
      return true
    }
  } catch (e) {
    console.warn('Không thể khôi phục phiên học:', e)
  }
  return false
}

// ----------------------------------------------------
// AUDIO & TTS CONTROLS (With Callback Support)
// ----------------------------------------------------
const toggleAutoPlayAudio = () => {
  isAutoPlayAudio.value = !isAutoPlayAudio.value
  localStorage.setItem(
    'flashcard_autoplay_audio',
    isAutoPlayAudio.value ? 'true' : 'false'
  )

  if (!isAutoPlayAudio.value) {
    speechService.stop()
    activeSpeakingTarget.value = null
  }
}

const toggleAudioRate = () => {
  if (audioRate.value === 1.0) {
    audioRate.value = 0.7
  } else if (audioRate.value === 0.7) {
    audioRate.value = 0.45
  } else {
    audioRate.value = 1.0
  }

  localStorage.setItem('flashcard_audio_rate', audioRate.value.toString())
  speechService.stop()
  activeSpeakingTarget.value = null
}

const toggleSoundEffects = () => {
  isSoundEffects.value = !isSoundEffects.value
  localStorage.setItem('flashcard_sfx_enabled', isSoundEffects.value ? 'true' : 'false')
}

const speakFrontText = (onFinish) => {
  const text = isSwappedSides.value
    ? getCorrectAnswerText(currentCard.value)
    : currentCard.value.question

  if (!text) {
    if (onFinish) onFinish()
    return
  }

  activeSpeakingTarget.value = 'front'
  speechService.speak(text, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === 'front') {
        activeSpeakingTarget.value = null
      }
      if (onFinish) onFinish()
    },
  })
}

const speakBackText = (onFinish) => {
  const text = isSwappedSides.value
    ? currentCard.value.question
    : getCorrectAnswerText(currentCard.value) || currentCard.value.question

  if (!text) {
    if (onFinish) onFinish()
    return
  }

  activeSpeakingTarget.value = 'back'
  speechService.speak(text, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === 'back') {
        activeSpeakingTarget.value = null
      }
      if (onFinish) onFinish()
    },
  })
}

const replayCurrentAudio = () => {
  if (isFlipped.value) {
    speakBackText()
  } else {
    speakFrontText()
  }
}

// ----------------------------------------------------
// STAR / BOOKMARK SYSTEM
// ----------------------------------------------------
const toggleCurrentStar = () => {
  const id = currentCard.value?.id
  if (!id) return

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  if (starredQuestions.value.includes(id)) {
    starredQuestions.value = starredQuestions.value.filter((qId) => qId !== id)
  } else {
    starredQuestions.value.push(id)
  }
  saveSessionState()
}

// ----------------------------------------------------
// STUDY TOOLS: SHUFFLE, SWAP SIDES, SMART SLIDESHOW
// ----------------------------------------------------
const toggleShuffle = () => {
  speechService.stop()
  clearAutoSlideshow()
  isShuffled.value = !isShuffled.value

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  if (isShuffled.value) {
    const list = [...activeList.value]
    for (let i = list.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1))
      ;[list[i], list[j]] = [list[j], list[i]]
    }
    activeList.value = list
  } else {
    if (isReviewingMode.value === 'weak') {
      const weakIds = [...needReviewQuestions.value]
      activeList.value = questions.value.filter((q) => weakIds.includes(q.id))
    } else if (isReviewingMode.value === 'starred') {
      const starIds = [...starredQuestions.value]
      activeList.value = questions.value.filter((q) => starIds.includes(q.id))
    } else {
      activeList.value = [...questions.value]
    }
  }

  currentIndex.value = 0
  isFlipped.value = false
  saveSessionState()

  if (isAutoSlideshow.value) {
    runSmartSlideshowFront()
  }
}

const toggleSwapSides = () => {
  speechService.stop()
  clearAutoSlideshow()
  isSwappedSides.value = !isSwappedSides.value
  isFlipped.value = false

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  saveSessionState()

  if (isAutoSlideshow.value) {
    runSmartSlideshowFront()
  } else if (isAutoPlayAudio.value) {
    setTimeout(() => speakFrontText(), 300)
  }
}

// ----------------------------------------------------
// SMART ADAPTIVE SLIDESHOW ENGINE (Audio & Length Sync)
// ----------------------------------------------------
const clearAutoSlideshow = () => {
  if (slideshowTimeout) {
    clearTimeout(slideshowTimeout)
    slideshowTimeout = null
  }
  slideshowStatusText.value = ''
}

const getReadingDuration = (text) => {
  if (!text) return 2500
  const wordCount = text.trim().split(/\s+/).length
  return Math.max(3000, Math.round((wordCount / 3.0) * 1000))
}

const runSmartSlideshowFront = () => {
  clearAutoSlideshow()
  if (!isAutoSlideshow.value || isFinished.value) return

  slideshowStatusText.value = 'Đang đọc mặt trước...'

  const onFrontDone = () => {
    if (!isAutoSlideshow.value || isFinished.value) return

    const bufferMs = getSlideshowBufferMs()
    slideshowStatusText.value = `Chờ ${Math.round(bufferMs / 1000)}s rồi lật...`

    slideshowTimeout = setTimeout(() => {
      if (!isAutoSlideshow.value || isFinished.value) return
      performFlipCard(true)
      runSmartSlideshowBack()
    }, bufferMs)
  }

  if (isAutoPlayAudio.value) {
    speakFrontText(onFrontDone)
  } else {
    const text = isSwappedSides.value
      ? getCorrectAnswerText(currentCard.value)
      : currentCard.value.question
    const readingTime = getReadingDuration(text)
    slideshowTimeout = setTimeout(onFrontDone, readingTime)
  }
}

const runSmartSlideshowBack = () => {
  clearAutoSlideshow()
  if (!isAutoSlideshow.value || isFinished.value) return

  slideshowStatusText.value = 'Đang đọc đáp án...'

  const onBackDone = () => {
    if (!isAutoSlideshow.value || isFinished.value) return

    const bufferMs = getSlideshowBufferMs()
    slideshowStatusText.value = `Chờ ${Math.round(bufferMs / 1000)}s rồi chuyển...`

    slideshowTimeout = setTimeout(() => {
      if (!isAutoSlideshow.value || isFinished.value) return

      // Giới hạn tài khoản khách tối đa 5 thẻ
      if (!isLoggedIn.value && currentIndex.value >= GUEST_PREVIEW_LIMIT - 1) {
        isAutoSlideshow.value = false
        clearAutoSlideshow()
        showGuestLimitModal.value = true
        return
      }

      markAnswer(true, true)

      if (!isFinished.value) {
        setTimeout(runSmartSlideshowFront, 300)
      } else {
        isAutoSlideshow.value = false
        clearAutoSlideshow()
      }
    }, bufferMs)
  }

  if (isAutoPlayAudio.value) {
    speakBackText(onBackDone)
  } else {
    const text = isSwappedSides.value
      ? currentCard.value.question
      : getCorrectAnswerText(currentCard.value)
    const readingTime = getReadingDuration(text)
    slideshowTimeout = setTimeout(onBackDone, readingTime)
  }
}

const toggleAutoSlideshow = () => {
  isAutoSlideshow.value = !isAutoSlideshow.value

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  if (isAutoSlideshow.value) {
    if (isFlipped.value) {
      runSmartSlideshowBack()
    } else {
      runSmartSlideshowFront()
    }
  } else {
    speechService.stop()
    clearAutoSlideshow()
  }
}

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    if (flashcardContainerRef.value?.requestFullscreen) {
      flashcardContainerRef.value.requestFullscreen().catch(() => {})
    } else if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen().catch(() => {})
    }
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {})
    }
    isFullscreen.value = false
  }
}

const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

// ----------------------------------------------------
// CARD FLIP & MARKING
// ----------------------------------------------------
const performFlipCard = (isFromSlideshow = false) => {
  if (!isFromSlideshow) {
    speechService.stop()
    activeSpeakingTarget.value = null
  }

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  isFlipped.value = !isFlipped.value

  if (!isFromSlideshow && isAutoPlayAudio.value) {
    setTimeout(() => {
      if (isFlipped.value) {
        speakBackText()
      } else {
        speakFrontText()
      }
    }, 280)
  }
}

const handleManualCardFlip = () => {
  if (isAutoSlideshow.value) {
    isAutoSlideshow.value = false
    clearAutoSlideshow()
  }
  performFlipCard(false)
}

const markAnswer = (isMastered, isFromSlideshow = false) => {
  if (!isFromSlideshow) {
    speechService.stop()
    activeSpeakingTarget.value = null
    if (isAutoSlideshow.value) {
      isAutoSlideshow.value = false
      clearAutoSlideshow()
    }
  }

  if (isSoundEffects.value) {
    if (isMastered) {
      speechService.playSuccessSound()
    } else {
      speechService.playReviewSound()
    }
  }

  const originalQuestion = currentCard.value
  if (!originalQuestion || !originalQuestion.id) return

  // Giới hạn tài khoản khách tối đa 5 thẻ
  if (!isLoggedIn.value && currentIndex.value >= GUEST_PREVIEW_LIMIT - 1) {
    speechService.stop()
    clearAutoSlideshow()
    isAutoSlideshow.value = false
    showGuestLimitModal.value = true
    return
  }

  if (isMastered) {
    if (!masteredQuestions.value.includes(originalQuestion.id)) {
      masteredQuestions.value.push(originalQuestion.id)
    }
    needReviewQuestions.value = needReviewQuestions.value.filter(
      (id) => id !== originalQuestion.id
    )
  } else {
    if (!needReviewQuestions.value.includes(originalQuestion.id)) {
      needReviewQuestions.value.push(originalQuestion.id)
    }
    masteredQuestions.value = masteredQuestions.value.filter(
      (id) => id !== originalQuestion.id
    )
  }

  if (currentIndex.value < activeList.value.length - 1) {
    isFlipped.value = false

    setTimeout(() => {
      currentIndex.value += 1
      saveSessionState()

      if (!isFromSlideshow && isAutoPlayAudio.value) {
        speakFrontText()
      }
    }, 200)
  } else {
    isFlipped.value = false
    clearAutoSlideshow()
    isAutoSlideshow.value = false

    setTimeout(() => {
      isFinished.value = true
      saveSessionState()
    }, 200)
  }
}

// ----------------------------------------------------
// RESTART ACTIONS
// ----------------------------------------------------
const restartAll = () => {
  speechService.stop()
  clearAutoSlideshow()
  isAutoSlideshow.value = false

  activeList.value = [...questions.value]
  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false

  masteredQuestions.value = []
  needReviewQuestions.value = []
  isReviewingMode.value = null
  isShuffled.value = false

  saveSessionState()

  if (isAutoPlayAudio.value) {
    setTimeout(() => speakFrontText(), 300)
  }
}

const restartOnlyWeak = () => {
  speechService.stop()
  clearAutoSlideshow()
  isAutoSlideshow.value = false

  const weakIds = [...needReviewQuestions.value]
  activeList.value = questions.value.filter((q) => weakIds.includes(q.id))

  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  isReviewingMode.value = 'weak'
  isShuffled.value = false

  saveSessionState()

  if (isAutoPlayAudio.value) {
    setTimeout(() => speakFrontText(), 300)
  }
}

const restartOnlyStarred = () => {
  speechService.stop()
  clearAutoSlideshow()
  isAutoSlideshow.value = false

  const starIds = [...starredQuestions.value]
  activeList.value = questions.value.filter((q) => starIds.includes(q.id))

  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  isReviewingMode.value = 'starred'
  isShuffled.value = false

  saveSessionState()

  if (isAutoPlayAudio.value) {
    setTimeout(() => speakFrontText(), 300)
  }
}

const goBack = () => {
  speechService.stop()
  clearAutoSlideshow()
  if (isFullscreen.value && document.exitFullscreen) {
    document.exitFullscreen().catch(() => {})
  }
  router.push(`/quizzes/${route.params.id}`)
}

// ----------------------------------------------------
// KEYBOARD SHORTCUTS LISTENER
// ----------------------------------------------------
const handleGlobalKeydown = (e) => {
  const targetTag = e.target.tagName.toLowerCase()
  if (targetTag === 'input' || targetTag === 'textarea' || targetTag === 'select') {
    return
  }

  if (showShortcutsModal.value) {
    if (e.code === 'Escape') {
      showShortcutsModal.value = false
    }
    return
  }

  if (isFinished.value) {
    if (e.code === 'KeyR') restartAll()
    return
  }

  // 1. Flip card: Space or Enter
  if (e.code === 'Space' || e.code === 'Enter') {
    e.preventDefault()
    handleManualCardFlip()
    return
  }

  // 2. Mark not mastered: Left Arrow or Digit 1
  if (e.code === 'ArrowLeft' || e.code === 'Digit1' || e.code === 'Numpad1') {
    e.preventDefault()
    markAnswer(false)
    return
  }

  // 3. Mark mastered: Right Arrow or Digit 2
  if (e.code === 'ArrowRight' || e.code === 'Digit2' || e.code === 'Numpad2') {
    e.preventDefault()
    markAnswer(true)
    return
  }

  // 4. Replay TTS: R key
  if (e.code === 'KeyR') {
    e.preventDefault()
    replayCurrentAudio()
    return
  }

  // 5. Shuffle: S key
  if (e.code === 'KeyS') {
    e.preventDefault()
    toggleShuffle()
    return
  }

  // 6. Fullscreen: F key
  if (e.code === 'KeyF') {
    e.preventDefault()
    toggleFullscreen()
    return
  }
}

// ----------------------------------------------------
// INITIAL LOAD
// ----------------------------------------------------
const loadQuizDetails = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const rawData = await quizzesApi.get(route.params.id)
    const normalizedQuiz = normalizeQuizCard(rawData)

    quizMeta.value = {
      title: normalizedQuiz.title,
      category: normalizedQuiz.category,
      difficulty: normalizedQuiz.difficulty,
    }

    const rawQuestions = rawData.questions || []
    questions.value = rawQuestions.map((q) => normalizeQuestion(q))
    activeList.value = [...questions.value]

    // Restore existing session if found
    const restored = restoreSessionState()

    if (!restored && isAutoPlayAudio.value && activeList.value.length > 0) {
      setTimeout(() => speakFrontText(), 500)
    }
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadQuizDetails()
  window.addEventListener('keydown', handleGlobalKeydown)
  document.addEventListener('fullscreenchange', handleFullscreenChange)
})

onUnmounted(() => {
  speechService.stop()
  clearAutoSlideshow()
  dismissResumeToast()
  window.removeEventListener('keydown', handleGlobalKeydown)
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
})
</script>

<style scoped>
.perspective-container {
  perspective: 1200px;
}

.flashcard-inner {
  transform-style: preserve-3d;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.flashcard-flipped {
  transform: rotateY(180deg);
}

.flashcard-front,
.flashcard-back {
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

.flashcard-back {
  transform: rotateY(180deg);
}
</style>
