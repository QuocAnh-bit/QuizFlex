export const currentRole = 'vip'

export const roleLabels = {
  admin: 'Admin',
  vip: 'VIP',
  user: 'Tài khoản thường',
}

export const visibilityOptions = [
  { value: 'public', label: 'Public', title: 'Công khai', icon: '🌐', description: 'Ai cũng có thể xem và làm quiz.' },
  { value: 'private', label: 'Private', title: 'Riêng tư', icon: '🔒', description: 'Chỉ chủ sở hữu và Admin có quyền xem.' },
  { value: 'group', label: 'Group', title: 'Theo Room', icon: '👥', description: 'Chỉ thành viên room được chỉ định truy cập.' },
]

export const visibilityLabels = {
  public: { label: 'Public', text: 'Công khai', icon: '🌐', className: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25' },
  private: { label: 'Private', text: 'Riêng tư', icon: '🔒', className: 'bg-rose-500/15 text-rose-400 border-rose-500/25' },
  group: { label: 'Group', text: 'Theo room', icon: '👥', className: 'bg-amber-500/15 text-amber-400 border-amber-500/25' },
}

export const allQuizzes = [
  { id: 'bio-basic', title: 'Kiến thức Sinh học cơ bản', author: 'QuizFlex', category: 'Khoa học', tag: 'Sinh học', difficulty: 'Vừa', visibility: 'public', roomCode: '', questions: 10, attempts: 156, avgScore: 87, rating: '4.8', icon: '🧬', badge: 'BIO', cover: 'linear-gradient(135deg, #16a34a, #84cc16)', status: 'Đã xuất bản' },
  { id: 'math-logic', title: 'Math Mind: Tư duy logic', author: 'Ngọc', category: 'Khoa học', tag: 'Toán học', difficulty: 'Khó', visibility: 'private', roomCode: '', questions: 20, attempts: 84, avgScore: 72, rating: '4.6', icon: '🧮', badge: 'MATH', cover: 'linear-gradient(135deg, #7c3aed, #06b6d4)', status: 'Bản nháp' },
  { id: 'english-b1', title: 'English Grammar Basic B1', author: 'Hân', category: 'Ngôn ngữ', tag: 'Tiếng Anh', difficulty: 'Vừa', visibility: 'group', roomCode: 'EN24', questions: 25, attempts: 112, avgScore: 79, rating: '4.7', icon: '📝', badge: 'ENG', cover: 'linear-gradient(135deg, #0ea5e9, #6366f1)', status: 'Đang chạy' },
  { id: 'logo-master', title: 'Logo Quiz: nhận diện thương hiệu', author: 'Minh Anh', category: 'Trivia', tag: 'Logo', difficulty: 'Dễ', visibility: 'public', roomCode: '', questions: 20, attempts: 231, avgScore: 91, rating: '4.6', icon: '🏷️', badge: 'LOGO', cover: 'linear-gradient(135deg, #0f172a, #64748b)', status: 'Đã xuất bản' },
  { id: 'history-vn', title: 'Lịch sử Việt Nam: mốc thời gian', author: 'Mai', category: 'Lịch sử', tag: 'Lịch sử', difficulty: 'Vừa', visibility: 'group', roomCode: 'VN76', questions: 18, attempts: 68, avgScore: 74, rating: '4.5', icon: '📜', badge: 'HIS', cover: 'linear-gradient(135deg, #92400e, #f59e0b)', status: 'Đã xuất bản' },
  { id: 'space', title: 'All About Space', author: 'Huy', category: 'Khoa học', tag: 'Vũ trụ', difficulty: 'Vừa', visibility: 'public', roomCode: '', questions: 22, attempts: 145, avgScore: 82, rating: '4.9', icon: '🪐', badge: 'SPACE', cover: 'linear-gradient(135deg, #020617, #4f46e5)', status: 'Đã xuất bản' },
  { id: 'roblox', title: 'Đoán game Roblox', author: 'Khang', category: 'Giải trí', tag: 'Game', difficulty: 'Dễ', visibility: 'public', roomCode: '', questions: 12, attempts: 198, avgScore: 88, rating: '4.7', icon: '🎮', badge: 'GAME', cover: 'linear-gradient(135deg, #111827, #2563eb)', status: 'Đã xuất bản' },
  { id: 'room-private', title: 'Ôn thi nội bộ lớp 10A1', author: 'Teacher A', category: 'Ngẫu nhiên', tag: 'Ôn thi', difficulty: 'Khó', visibility: 'private', roomCode: '', questions: 30, attempts: 32, avgScore: 69, rating: '4.4', icon: '🎓', badge: 'EXAM', cover: 'linear-gradient(135deg, #991b1b, #111827)', status: 'Riêng tư' },
]

export const quizTopics = [
  {
    id: 'random',
    title: 'Ngẫu nhiên',
    description: 'Các quiz được chọn nhanh để người dùng làm thử ngay.',
    category: 'Ngẫu nhiên',
    quizzes: [allQuizzes[0], allQuizzes[1], allQuizzes[2], allQuizzes[3], allQuizzes[7]],
  },
  {
    id: 'science',
    title: 'Khoa học và Tự nhiên',
    description: 'Sinh học, vật lý, hóa học, vũ trụ, động vật và môi trường.',
    category: 'Khoa học',
    quizzes: [allQuizzes[0], allQuizzes[1], allQuizzes[5]],
  },
  {
    id: 'language',
    title: 'Ngôn ngữ',
    description: 'Tiếng Anh, từ vựng, ngữ pháp, dịch nghĩa và giao tiếp cơ bản.',
    category: 'Ngôn ngữ',
    quizzes: [allQuizzes[2]],
  },
  {
    id: 'entertainment',
    title: 'Giải trí',
    description: 'Phim ảnh, âm nhạc, game, show truyền hình và văn hóa đại chúng.',
    category: 'Giải trí',
    quizzes: [allQuizzes[6]],
  },
  {
    id: 'trivia',
    title: 'Trivia',
    description: 'Câu hỏi nhanh, kiến thức lạ, mẹo vui và thử thách suy luận.',
    category: 'Trivia',
    quizzes: [allQuizzes[3]],
  },
]

export const quizQuestions = [
  { question: 'Bào quan nào chịu trách nhiệm chính trong việc tạo năng lượng cho tế bào?', category: 'Sinh học', difficulty: 'Trung bình', answers: [ { key: 'A', text: 'Ti thể tạo ra năng lượng cho tế bào.' }, { key: 'B', text: 'Ribosome lưu trữ thông tin di truyền.' }, { key: 'C', text: 'Nhân tế bào tiêu hóa chất thải.' }, { key: 'D', text: 'Màng tế bào tổng hợp protein.' } ] },
  { question: 'Trong toán học, số nguyên tố là số như thế nào?', category: 'Toán học', difficulty: 'Dễ', answers: [ { key: 'A', text: 'Số chỉ có hai ước là 1 và chính nó.' }, { key: 'B', text: 'Số chia hết cho 2.' }, { key: 'C', text: 'Số âm bất kỳ.' }, { key: 'D', text: 'Số có nhiều hơn 5 ước.' } ] },
  { question: 'Thủ đô của Nhật Bản là thành phố nào?', category: 'Địa lý', difficulty: 'Dễ', answers: [ { key: 'A', text: 'Tokyo' }, { key: 'B', text: 'Kyoto' }, { key: 'C', text: 'Osaka' }, { key: 'D', text: 'Nagoya' } ] },
  { question: 'Trong tiếng Anh, thì hiện tại hoàn thành thường dùng để diễn tả điều gì?', category: 'Tiếng Anh', difficulty: 'Vừa', answers: [ { key: 'A', text: 'Hành động đã xảy ra và còn liên quan tới hiện tại.' }, { key: 'B', text: 'Hành động sẽ xảy ra ngày mai.' }, { key: 'C', text: 'Thói quen ở quá khứ.' }, { key: 'D', text: 'Mệnh lệnh trực tiếp.' } ] },
  { question: 'Public visibility nghĩa là gì trong QuizFlex?', category: 'Visibility', difficulty: 'Dễ', answers: [ { key: 'A', text: 'Ai cũng có thể xem quiz.' }, { key: 'B', text: 'Chỉ chủ sở hữu xem được.' }, { key: 'C', text: 'Chỉ thành viên room xem được.' }, { key: 'D', text: 'Quiz bị khóa.' } ] },
  { question: 'Room code dùng để làm gì?', category: 'Room', difficulty: 'Dễ', answers: [ { key: 'A', text: 'Để người chơi tham gia phòng.' }, { key: 'B', text: 'Để đổi mật khẩu.' }, { key: 'C', text: 'Để xóa quiz.' }, { key: 'D', text: 'Để thanh toán VIP.' } ] },
  { question: 'OCR trong hệ thống quiz giúp việc gì?', category: 'AI / OCR', difficulty: 'Vừa', answers: [ { key: 'A', text: 'Trích xuất nội dung từ PDF hoặc ảnh.' }, { key: 'B', text: 'Tạo mật khẩu mới.' }, { key: 'C', text: 'Xóa dữ liệu người dùng.' }, { key: 'D', text: 'Tự động thanh toán.' } ] },
  { question: 'VIP có thể làm gì mà user thường không có?', category: 'Role', difficulty: 'Vừa', answers: [ { key: 'A', text: 'Tạo quiz thủ công và tạo room.' }, { key: 'B', text: 'Không được làm quiz.' }, { key: 'C', text: 'Chỉ xem login.' }, { key: 'D', text: 'Không có dashboard.' } ] },
  { question: 'Quiz group nên gắn với đối tượng nào?', category: 'Visibility', difficulty: 'Vừa', answers: [ { key: 'A', text: 'Một room hoặc nhóm cụ thể.' }, { key: 'B', text: 'Tất cả internet.' }, { key: 'C', text: 'Không ai cả.' }, { key: 'D', text: 'Chỉ database.' } ] },
  { question: 'Report tổng hợp giúp người quản lý xem điều gì?', category: 'Report', difficulty: 'Vừa', answers: [ { key: 'A', text: 'Kết quả, điểm, lượt làm và xu hướng học tập.' }, { key: 'B', text: 'Chỉ màu nền website.' }, { key: 'C', text: 'Số lần đổi theme.' }, { key: 'D', text: 'Không có thông tin gì.' } ] },
]

export const dashboardStats = {
  admin: [
    { label: 'Tổng user', value: '1.284', hint: '+12% tháng này' },
    { label: 'Tổng quiz', value: '248', hint: '32 quiz mới' },
    { label: 'Doanh thu', value: '42.8M', hint: 'VIP subscriptions' },
    { label: 'AI usage', value: '8.2k', hint: 'Requests tháng này' },
  ],
  vip: [
    { label: 'Quiz đã tạo', value: '24', hint: '+4 tuần này' },
    { label: 'Room đang chạy', value: '3', hint: '2 room active' },
    { label: 'Lượt AI còn lại', value: '420', hint: 'Gói VIP' },
    { label: 'Điểm trung bình', value: '87%', hint: 'Từ người học' },
  ],
  user: [
    { label: 'Quiz đã làm', value: '18', hint: '+3 tuần này' },
    { label: 'Điểm trung bình', value: '76%', hint: 'Cần cố thêm, đời cũng vậy' },
    { label: 'Room tham gia', value: '4', hint: '2 room active' },
    { label: 'AI còn lại', value: '6', hint: 'Quota miễn phí' },
  ],
}

export const users = [
  { id: 1, name: 'Minh Anh', email: 'minhanh@example.com', role: 'vip', status: 'active', sessions: 3, joinedAt: '2026-05-01', aiUsed: 84 },
  { id: 2, name: 'Quốc Huy', email: 'huy@example.com', role: 'user', status: 'active', sessions: 1, joinedAt: '2026-05-06', aiUsed: 12 },
  { id: 3, name: 'Gia Linh', email: 'linh@example.com', role: 'user', status: 'locked', sessions: 0, joinedAt: '2026-04-18', aiUsed: 4 },
  { id: 4, name: 'Admin QuizFlex', email: 'admin@quizflex.local', role: 'admin', status: 'active', sessions: 5, joinedAt: '2026-03-10', aiUsed: 340 },
  { id: 5, name: 'Teacher B', email: 'teacherb@example.com', role: 'vip', status: 'active', sessions: 2, joinedAt: '2026-04-22', aiUsed: 128 },
]

export const rooms = [
  { id: 'QZ24', title: 'Ôn tập Sinh học 10', host: 'Minh Anh', mode: 'host', visibility: 'group', status: 'live', members: 24, quiz: 'Kiến thức Sinh học cơ bản', startedAt: '08:30', avgScore: 82 },
  { id: 'EN24', title: 'English Grammar B1', host: 'Teacher B', mode: 'member', visibility: 'group', status: 'waiting', members: 18, quiz: 'English Grammar Basic B1', startedAt: '09:15', avgScore: 79 },
  { id: 'VN76', title: 'Lịch sử Việt Nam', host: 'Ngọc', mode: 'host', visibility: 'group', status: 'ended', members: 31, quiz: 'Lịch sử Việt Nam: mốc thời gian', startedAt: 'Yesterday', avgScore: 74 },
]

export const roomPlayers = [
  { name: 'Minh Anh', status: 'Đã trả lời', score: 320 },
  { name: 'Quốc Huy', status: 'Đang suy nghĩ', score: 280 },
  { name: 'Gia Linh', status: 'Đã trả lời', score: 250 },
  { name: 'Bảo Nam', status: 'Chưa trả lời', score: 210 },
]

export const resultHistory = [
  { id: 1, quiz: 'Kiến thức Sinh học cơ bản', date: '16/05/2026', score: 87, correct: 9, total: 10, time: '08:12', visibility: 'public' },
  { id: 2, quiz: 'English Grammar Basic B1', date: '14/05/2026', score: 76, correct: 19, total: 25, time: '13:40', visibility: 'group' },
  { id: 3, quiz: 'Math Mind: Tư duy logic', date: '12/05/2026', score: 68, correct: 14, total: 20, time: '16:02', visibility: 'private' },
  { id: 4, quiz: 'Logo Quiz: nhận diện thương hiệu', date: '10/05/2026', score: 92, correct: 18, total: 20, time: '07:35', visibility: 'public' },
]

export const reportRows = [
  { quiz: 'Kiến thức Sinh học cơ bản', attempts: 156, avgScore: 87, completion: '92%', visibility: 'public', owner: 'QuizFlex' },
  { quiz: 'English Grammar Basic B1', attempts: 112, avgScore: 79, completion: '81%', visibility: 'group', owner: 'Teacher B' },
  { quiz: 'Math Mind: Tư duy logic', attempts: 84, avgScore: 72, completion: '74%', visibility: 'private', owner: 'Ngọc' },
]

export const paymentPlans = [
  { id: 'free', name: 'Free', price: '0đ', period: '/tháng', badge: 'Bắt đầu', features: ['Làm quiz public', 'Join room bằng mã', 'AI giới hạn 10 lượt', 'Không tạo quiz thủ công'], popular: false },
  { id: 'vip-monthly', name: 'VIP Monthly', price: '99.000đ', period: '/tháng', badge: 'Khuyên dùng', features: ['Tạo quiz thủ công', 'Tạo room realtime', 'AI/OCR nhiều hơn', 'Report theo room'], popular: true },
  { id: 'vip-yearly', name: 'VIP Yearly', price: '899.000đ', period: '/năm', badge: 'Tiết kiệm', features: ['Tất cả quyền VIP', 'Ưu tiên quota AI', 'Export report', 'Hỗ trợ sớm'], popular: false },
]

export const paymentHistory = [
  { id: 'INV-1001', name: 'Minh Anh', plan: 'VIP Monthly', amount: '99.000đ', date: '16/05/2026', status: 'Paid' },
  { id: 'INV-1002', name: 'Teacher B', plan: 'VIP Yearly', amount: '899.000đ', date: '12/05/2026', status: 'Paid' },
  { id: 'INV-1003', name: 'Quốc Huy', plan: 'VIP Monthly', amount: '99.000đ', date: '09/05/2026', status: 'Pending' },
]

export const aiUsageRows = [
  { name: 'Admin QuizFlex', role: 'admin', generated: 340, ocr: 88, quota: 'Unlimited' },
  { name: 'Minh Anh', role: 'vip', generated: 84, ocr: 24, quota: '500/month' },
  { name: 'Quốc Huy', role: 'user', generated: 12, ocr: 3, quota: '10/month' },
]
