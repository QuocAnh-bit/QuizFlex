/**
 * Speech & Sound Effects Service for QuizFlex Flashcards
 * Utilizes Web Speech API for Text-To-Speech (TTS) and Web Audio API for UI Sound Effects.
 * 
 * High-Precision Dual-Language Phrase Engine with English Phrase Detection:
 * - Detects English sentences (including "I am...", "What is...", "You are...") instantly.
 * - Prevents English sentences from being misclassified as Vietnamese.
 * - Preserves unaccented Vietnamese words for Vietnamese sentences.
 */

// Tập hợp các từ Tiếng Anh cao tần dùng để phát hiện câu Tiếng Anh lập tức
const EN_HIGH_FREQUENCY_WORDS = new Set([
  'i', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had',
  'do', 'does', 'did', 'will', 'would', 'shall', 'should', 'can', 'could', 'may', 'might', 'must',
  'what', 'where', 'when', 'which', 'who', 'whom', 'whose', 'why', 'how', 'the', 'this', 'that',
  'these', 'those', 'you', 'he', 'she', 'it', 'we', 'they', 'my', 'your', 'his', 'her', 'its', 'our', 'their',
  'in', 'on', 'at', 'for', 'from', 'with', 'about', 'of', 'by'
])

// Tập hợp từ tiếng Việt không dấu phổ biến nhất để không bị nhận nhầm là Tiếng Anh
const VI_UNACCENTED_WORDS = new Set([
  'an', 'anh', 'ao', 'ba', 'bac', 'bai', 'ban', 'bao', 'bay', 'be', 'ben', 'bi', 'bien', 'biet', 
  'bo', 'boc', 'buoc', 'ca', 'cac', 'cai', 'cam', 'can', 'cao', 'cay', 'cha', 'chay', 'che', 'chi', 
  'chia', 'cho', 'chot', 'chu', 'chua', 'chuc', 'chuyen', 'co', 'con', 'coc', 'coi', 'com', 'cu', 
  'cua', 'cuc', 'cung', 'cuoi', 'cuoc', 'da', 'dai', 'dang', 'dao', 'dat', 'dau', 'de', 'den', 'dep', 
  'di', 'diem', 'dinh','doc', 'doi', 'dong', 'du', 'dua', 'duoc', 'duoi', 'em', 'gia', 'giai', 
  'giao', 'giu', 'giua', 'ha', 'hai', 'hang', 'hay', 'he', 'hien', 'hieu', 'hinh', 'ho', 'hoa', 'hoc', 
  'hoi', 'hon', 'hop', 'huong', 'khi', 'kho', 'khong', 'khu', 'kiem', 'la', 'lai', 'lam', 'lan', 'lau', 
  'le', 'len', 'lo', 'loai', 'loi', 'lon', 'luong', 'luu', 'ma', 'mai', 'mang', 'mau', 'me', 'mo', 
  'moi', 'mot', 'muc', 'mua', 'muon', 'na', 'nam', 'nay', 'nen', 'neu', 'ngoai', 'ngon', 'ngu', 
  'nguoi', 'nha', 'nhan', 'nhieu', 'nhin', 'nho', 'nhu', 'nhung', 'noi', 'nuoc', 'oi', 'phai', 'phan', 
  'phong', 'phu', 'qua', 'quan', 'quang', 'quoc', 'quy', 'ra', 'sang', 'sao', 'se', 'sau', 'sinh', 
  'so', 'song', 'sua', 'ta', 'tai', 'tam', 'tan', 'tao', 'tay', 'te', 'ten', 'theo', 'thi', 'thich', 
  'thoi', 'thong', 'thu', 'thua', 'thuc', 'thuoc', 'thuong', 'tinh', 'toa', 'toan', 'toi', 
  'ton', 'tong', 'tot', 'tra', 'trai', 'trang', 'tren', 'trong', 'tru', 'trung', 'truoc', 'truong', 
  'tu', 'tua', 'tuc', 'tuoi', 'tuong', 'tuy', 'va', 'van', 've', 'vi', 'viec', 'viet', 'vo', 'voi', 
  'vu', 'xa', 'xem', 'xin', 'xuat', 'y', 'yeu', 'ai', 'xinh','quen','thay','dang','đang','nhay','nhau','vui'
])

class SpeechService {
  constructor() {
    this.synth = typeof window !== 'undefined' && 'speechSynthesis' in window ? window.speechSynthesis : null
    this.audioCtx = null
    this.isSpeaking = false
    this.voices = []

    if (this.synth) {
      this.loadVoices()
      if (typeof window !== 'undefined' && window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = () => this.loadVoices()
      }
    }
  }

  loadVoices() {
    if (!this.synth) return
    this.voices = this.synth.getVoices() || []
  }

  isVietnameseToken(word) {
    if (!word) return false
    const viDiacriticsRegex = /[àáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ]/i
    if (viDiacriticsRegex.test(word)) return true
    
    const clean = word.toLowerCase().replace(/[^a-z]/g, '')
    if (VI_UNACCENTED_WORDS.has(clean)) return true
    return false
  }

  isEnglishToken(word) {
    if (!word) return false
    const viDiacriticsRegex = /[àáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ]/i
    if (viDiacriticsRegex.test(word)) return false

    const clean = word.replace(/^['"“`\(\[\{\<\>]+|['"”`\)\}\]\.\,\;\!\?\:\-]+$/g, '')
    if (!clean || clean.length < 1) return false

    const lower = clean.toLowerCase()
    if (EN_HIGH_FREQUENCY_WORDS.has(lower)) return true
    if (VI_UNACCENTED_WORDS.has(lower)) return false

    // Nhận diện từ Tiếng Anh
    if (/[zfwjZFWJ]/.test(clean)) return true
    if (/[A-Z].*[A-Z]/.test(clean)) return true 
    if (/^[A-Z][a-z]{2,}$/.test(clean)) return true 
    if (/^[a-z]{4,}$/.test(clean) && !VI_UNACCENTED_WORDS.has(lower)) return true

    return false
  }

  /**
   * Tách bạch rõ ràng: Cụm Tiếng Việt ➡️ Giọng Tiếng Việt, Cụm Tiếng Anh ➡️ Giọng Tiếng Anh
   */
  extractPureLanguageChunks(text, preferredMode = 'auto') {
    if (!text || !text.trim()) return []

    const cleanText = text.replace(/<[^>]*>?/gm, '').trim()
    if (!cleanText) return []

    if (preferredMode === 'vi') return [{ text: cleanText, lang: 'vi-VN' }]
    if (preferredMode === 'en') return [{ text: cleanText, lang: 'en-US' }]

    const viDiacriticsRegex = /[àáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ]/i
    const hasViDiacritics = viDiacriticsRegex.test(cleanText)

    // Regex phát hiện các cụm xưng hô / câu Tiếng Anh điển hình (I am, You are, What is, Where is...)
    const enPhraseRegex = /\b(i am|i'm|you are|you're|he is|he's|she is|she's|it is|it's|we are|we're|they are|they're|this is|that is|what is|where is|when is|why is|how is|who is|there is|there are|i have|i can|i will|i do|i would|i should|i could|do you|can you|will you|what do|where do|how do)\b/i

    // Nếu câu không có dấu tiếng Việt VÀ chứa cụm từ Tiếng Anh hoặc các từ Tiếng Anh cao tần -> Nhận diện ngay 100% Tiếng Anh
    if (!hasViDiacritics) {
      if (enPhraseRegex.test(cleanText)) {
        return [{ text: cleanText, lang: 'en-US' }]
      }

      const words = cleanText.toLowerCase().split(/\s+/)
      let enWordCount = 0
      for (let w of words) {
        const cleanW = w.replace(/[^a-z]/g, '')
        if (EN_HIGH_FREQUENCY_WORDS.has(cleanW)) {
          enWordCount++
        }
      }

      if (enWordCount >= 1 || words.length <= 4) {
        return [{ text: cleanText, lang: 'en-US' }]
      }
    }

    // Phân tách cụm ngôn ngữ biệt lập cho câu hỗn hợp
    const tokens = cleanText.split(/(\s+|[\(\)\[\]\{\}'"“`]+)/)
    const chunks = []
    let currentChunkText = ''
    let currentLang = null

    for (let token of tokens) {
      if (!token) continue

      let tokenLang = null

      if (this.isVietnameseToken(token)) {
        tokenLang = 'vi-VN'
      } else if (this.isEnglishToken(token)) {
        tokenLang = 'en-US'
      } else {
        tokenLang = currentLang || 'vi-VN'
      }

      if (tokenLang === currentLang || !currentLang) {
        currentLang = tokenLang
        currentChunkText += token
      } else {
        if (currentChunkText.trim()) {
          chunks.push({ text: currentChunkText.trim(), lang: currentLang })
        }
        currentChunkText = token
        currentLang = tokenLang
      }
    }

    if (currentChunkText.trim()) {
      chunks.push({ text: currentChunkText.trim(), lang: currentLang || 'vi-VN' })
    }

    return chunks.length > 0 ? chunks : [{ text: cleanText, lang: 'vi-VN' }]
  }

  /**
   * Làm sạch văn bản và xử lý ký tự chỗ trống (___, ..., ---)
   */
  sanitizeTextForSpeech(text, lang) {
    if (!text) return ''

    let cleaned = text.replace(/<[^>]*>?/gm, '')

    const isVi = (lang === 'vi-VN' || lang === 'vi')
    const blankWord = isVi ? ' chỗ trống ' : ' blank '

    cleaned = cleaned.replace(/_{2,}/g, blankWord)
    cleaned = cleaned.replace(/\s+_\s+/g, blankWord)
    cleaned = cleaned.replace(/\.{3,}/g, blankWord)
    cleaned = cleaned.replace(/-{2,}/g, blankWord)
    cleaned = cleaned.replace(/[\*#\=\~\^\&\|\\]{2,}/g, ' ')

    return cleaned.replace(/\s+/g, ' ').trim()
  }

  /**
   * Tìm giọng đọc chuẩn nhất cho từng ngôn ngữ
   */
  getBestVoice(lang) {
    if (!this.voices || this.voices.length === 0) {
      this.loadVoices()
    }
    const targetLang = (lang || 'vi-VN').toLowerCase().replace('_', '-')
    const langPrefix = targetLang.split('-')[0]

    const matchingVoices = this.voices.filter(v => {
      const vLang = v.lang.toLowerCase().replace('_', '-')
      return vLang === targetLang || vLang.startsWith(langPrefix)
    })

    if (matchingVoices.length === 0) return null

    const preferredVoice = matchingVoices.find(v => 
      v.name.includes('Google') || v.name.includes('Natural') || v.name.includes('Microsoft') || v.default
    )

    return preferredVoice || matchingVoices[0]
  }

  /**
   * Đọc văn bản bằng Động cơ Tách bạch Ngôn ngữ Thông minh
   */
  speak(text, options = {}) {
    if (!this.synth) {
      console.warn('Web Speech API không được hỗ trợ trên trình duyệt này.')
      return
    }

    // Dừng âm thanh đang phát trước đó
    this.stop()

    if (!text || !text.trim()) return

    // 1. Tách bạch rõ ràng: Cụm Tiếng Việt ➡️ Giọng Tiếng Việt, Từ/Cụm Tiếng Anh ➡️ Giọng Tiếng Anh
    const pureChunks = this.extractPureLanguageChunks(text, options.preferredMode)

    // 2. Tạo Utterance phát âm riêng biệt cho từng cụm
    const validUtterances = []
    
    pureChunks.forEach(chunk => {
      const cleanChunkText = this.sanitizeTextForSpeech(chunk.text, chunk.lang)
      if (!cleanChunkText) return

      const utterance = new SpeechSynthesisUtterance(cleanChunkText)
      utterance.lang = chunk.lang

      const voice = this.getBestVoice(chunk.lang)
      if (voice) {
        utterance.voice = voice
      }

      utterance.rate = options.rate || 1.0
      utterance.pitch = options.pitch || 1.0
      validUtterances.push(utterance)
    })

    if (validUtterances.length === 0) return

    // 3. Đưa vào hàng chờ phát âm nối tiếp mượt mà
    this.isSpeaking = true

    validUtterances.forEach((utt, index) => {
      if (index === 0) {
        utt.onstart = () => {
          this.isSpeaking = true
        }
      }
      
      if (index === validUtterances.length - 1) {
        utt.onend = () => {
          this.isSpeaking = false
          if (options.onEnd) options.onEnd()
        }
        utt.onerror = (e) => {
          this.isSpeaking = false
          console.error('Lỗi phát âm TTS:', e)
        }
      }

      this.synth.speak(utt)
    })
  }

  /**
   * Dừng âm thanh phát âm ngay lập tức
   */
  stop() {
    if (this.synth && (this.synth.speaking || this.synth.pending)) {
      this.synth.cancel()
    }
    this.isSpeaking = false
  }

  /**
   * Web Audio API Synthesizer cho hiệu ứng âm thanh nhẹ nhàng
   */
  getAudioContext() {
    if (!this.audioCtx && typeof window !== 'undefined') {
      const AudioCtx = window.AudioContext || window.webkitAudioContext
      if (AudioCtx) {
        this.audioCtx = new AudioCtx()
      }
    }
    if (this.audioCtx && this.audioCtx.state === 'suspended') {
      this.audioCtx.resume()
    }
    return this.audioCtx
  }

  playFlipSound() {
    try {
      const ctx = this.getAudioContext()
      if (!ctx) return

      const osc = ctx.createOscillator()
      const gain = ctx.createGain()

      osc.type = 'sine'
      osc.frequency.setValueAtTime(250, ctx.currentTime)
      osc.frequency.exponentialRampToValueAtTime(500, ctx.currentTime + 0.08)

      gain.gain.setValueAtTime(0.15, ctx.currentTime)
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.08)

      osc.connect(gain)
      gain.connect(ctx.destination)

      osc.start()
      osc.stop(ctx.currentTime + 0.08)
    } catch (err) {
      console.warn('Audio effect error:', err)
    }
  }

  playSuccessSound() {
    try {
      const ctx = this.getAudioContext()
      if (!ctx) return

      const notes = [523.25, 659.25, 783.99] // C5, E5, G5
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()

        osc.type = 'triangle'
        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.06)

        gain.gain.setValueAtTime(0.12, ctx.currentTime + idx * 0.06)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.06 + 0.18)

        osc.connect(gain)
        gain.connect(ctx.destination)

        osc.start(ctx.currentTime + idx * 0.06)
        osc.stop(ctx.currentTime + idx * 0.06 + 0.18)
      })
    } catch (err) {
      console.warn('Audio effect error:', err)
    }
  }

  playReviewSound() {
    try {
      const ctx = this.getAudioContext()
      if (!ctx) return

      const notes = [349.23, 293.66] // F4 to D4
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()

        osc.type = 'sine'
        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.08)

        gain.gain.setValueAtTime(0.1, ctx.currentTime + idx * 0.08)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.08 + 0.15)

        osc.connect(gain)
        gain.connect(ctx.destination)

        osc.start(ctx.currentTime + idx * 0.08)
        osc.stop(ctx.currentTime + idx * 0.08 + 0.15)
      })
    } catch (err) {
      console.warn('Audio effect error:', err)
    }
  }
}

export default new SpeechService()
