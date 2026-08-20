#!/usr/bin/env bash

BASE_URL="http://127.0.0.1:8000/api"
EMAIL="test-ai-1787064969@test.local"
PASSWORD="TestPassword123!"

echo "=== Login ==="
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

echo "Login Response:"
echo "$LOGIN_RESPONSE" | jq . 2>/dev/null || echo "$LOGIN_RESPONSE"

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token // .data.token // empty' 2>/dev/null)
if [ -z "$TOKEN" ]; then
  echo "ERROR: Could not extract token"
  exit 1
fi

echo ""
echo "=== AI Generation ==="
AI_RESPONSE=$(curl -s -X POST "$BASE_URL/ai/generate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "prompt": "Tạo 2 câu hỏi về lịch sử",
    "count": 2,
    "difficulty": "medium",
    "language": "vi",
    "visibility": "private"
  }')

echo "AI Response:"
echo "$AI_RESPONSE" | jq . 2>/dev/null || echo "$AI_RESPONSE"

JOB_ID=$(echo "$AI_RESPONSE" | jq -r '.data.job_id // .job_id // empty' 2>/dev/null)
if [ -z "$JOB_ID" ]; then
  echo "ERROR: Could not extract job_id"
  exit 1
fi

echo ""
echo "Job ID: $JOB_ID"
echo "=== Job Status ==="
sleep 2
curl -s -X GET "$BASE_URL/ai/jobs/$JOB_ID" \
  -H "Authorization: Bearer $TOKEN" | jq . 2>/dev/null
