<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\DevotionalContentGenerator;
use App\Enums\AiGenerationStatus;
use App\Models\AiGenerationLog;
use App\Models\User;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final readonly class GenerateDevotionalContent
{
    public function handle(User $admin, string $prompt): AiGenerationLog
    {
        $log = AiGenerationLog::query()->create([
            'admin_id' => $admin->id,
            'prompt' => $prompt,
            'status' => AiGenerationStatus::Pending,
        ]);

        try {
            /** @var StructuredAgentResponse $response */
            $response = (new DevotionalContentGenerator)->prompt($prompt);

            $content = $response->toArray();

            $log->update([
                'status' => AiGenerationStatus::Completed,
                'generated_content' => [
                    'title' => $content['title'],
                    'body' => $content['body'],
                    'scripture_refs' => $content['scripture_refs'],
                    'reflection_prompts' => $content['reflection_prompts'],
                    'adventist_insights' => $content['adventist_insights'],
                ],
            ]);
        } catch (Throwable $throwable) {
            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $throwable->getMessage(),
            ]);
        }

        return $log->refresh();
    }
}
