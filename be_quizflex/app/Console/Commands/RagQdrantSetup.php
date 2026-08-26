<?php

namespace App\Console\Commands;

use App\Services\RAG\Qdrant\QdrantService;
use Illuminate\Console\Command;

class RagQdrantSetup extends Command
{
    protected $signature =
    'rag:qdrant-setup';

    protected $description =
    'Tạo Qdrant collection cho curriculum RAG';


    public function handle(
        QdrantService $qdrant
    ): int {

        try {

            $result =
                $qdrant->createCollection();


            if (!$result['created']) {

                $this->warn(
                    $result['message']
                );

                return self::SUCCESS;
            }


            $this->info(
                'Qdrant collection created.'
            );

            $this->line(
                'Collection: '
                    . $result['collection']
            );

            $this->line(
                'Dimension: '
                    . $result['dimension']
            );

            $this->line(
                'Distance: '
                    . $result['distance']
            );


            return self::SUCCESS;
        } catch (\Throwable $e) {

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
