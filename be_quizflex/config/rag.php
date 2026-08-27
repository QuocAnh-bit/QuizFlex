<?php

return [
    'embedding' => [
        'provider' => env(
            'RAG_EMBEDDING_PROVIDER',
            'openrouter'
        ),

        'model' => env(
            'OPENROUTER_EMBEDDING_MODEL'
        ),
    ],

    'openrouter' => [
        'base_url' => env(
            'OPENROUTER_BASE_URL',
            'https://openrouter.ai/api/v1'
        ),

        'api_key' => env(
            'OPENROUTER_API_KEY'
        ),

        'parser_model' => env(
            'OPENROUTER_PARSER_MODEL'
        ),

        'parser_max_tokens' => (int) env(
            'OPENROUTER_PARSER_MAX_TOKENS',
            8000
        ),

        'embedding_model' => env(
            'OPENROUTER_EMBEDDING_MODEL',
            'qwen/qwen3-embedding-0.6b'
        ),
    ],

    'qdrant' => [
        'url' => env(
            'QDRANT_URL',
            'http://127.0.0.1:6333'
        ),

        'api_key' => env(
            'QDRANT_API_KEY'
        ),

        'collection' => env(
            'QDRANT_COLLECTION',
            'quizflex_curriculum_v1'
        ),

        'dimension' => (int) env(
            'QDRANT_DIMENSION',
            4096
        ),
    ],

    'retrieval' => [
        'limit' => (int) env(
            'RAG_RETRIEVAL_LIMIT',
            6
        ),

        'score_threshold' => env(
            'RAG_SCORE_THRESHOLD'
        ),
    ],
];
