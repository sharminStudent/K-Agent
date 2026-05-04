<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:export-finetune-dataset {--agent-id=} {--limit=} {--output=storage/app/ai/fine_tune_dataset.jsonl}', function () {
    $python = env('PYTHON_BIN', 'python');
    $output = base_path((string) $this->option('output'));

    $command = [
        $python,
        base_path('python/export_finetune_dataset.py'),
        '--output',
        $output,
    ];

    if (filled($this->option('agent-id'))) {
        $command[] = '--agent-id';
        $command[] = (string) $this->option('agent-id');
    }

    if (filled($this->option('limit'))) {
        $command[] = '--limit';
        $command[] = (string) $this->option('limit');
    }

    $process = new Process($command, base_path(), [
        'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    ]);

    $process->setTimeout(300);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    if (! $process->isSuccessful()) {
        $this->error('Python fine-tune dataset export failed.');

        return self::FAILURE;
    }

    $this->info('Fine-tune dataset export completed.');

    return self::SUCCESS;
})->purpose('Export PostgreSQL chat history to OpenAI fine-tuning JSONL using Python');

Artisan::command('ai:create-finetune-job {trainingFile=storage/app/ai/fine_tune_dataset.jsonl} {--model=gpt-4.1-mini-2025-04-14} {--suffix=}', function () {
    $python = env('PYTHON_BIN', 'python');
    $trainingFile = base_path((string) $this->argument('trainingFile'));

    $command = [
        $python,
        base_path('python/create_finetune_job.py'),
        '--training-file',
        $trainingFile,
        '--model',
        (string) $this->option('model'),
    ];

    if (filled($this->option('suffix'))) {
        $command[] = '--suffix';
        $command[] = (string) $this->option('suffix');
    }

    $process = new Process($command, base_path(), [
        'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    ]);

    $process->setTimeout(300);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    if (! $process->isSuccessful()) {
        $this->error('Python fine-tuning job creation failed.');

        return self::FAILURE;
    }

    $this->info('Fine-tuning job submitted.');

    return self::SUCCESS;
})->purpose('Upload a fine-tuning dataset and create an OpenAI fine-tuning job using Python');

Artisan::command('ai:check-finetune-job {jobId}', function () {
    $python = env('PYTHON_BIN', 'python');

    $command = [
        $python,
        base_path('python/check_finetune_job.py'),
        '--job-id',
        (string) $this->argument('jobId'),
    ];

    $process = new Process($command, base_path(), [
        'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    ]);

    $process->setTimeout(300);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    if (! $process->isSuccessful()) {
        $this->error('Fine-tuning job status check failed.');

        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Check an OpenAI fine-tuning job status using Python');

Artisan::command('ai:check-finetune-events {jobId} {--limit=20}', function () {
    $python = env('PYTHON_BIN', 'python');

    $command = [
        $python,
        base_path('python/check_finetune_events.py'),
        '--job-id',
        (string) $this->argument('jobId'),
        '--limit',
        (string) $this->option('limit'),
    ];

    $process = new Process($command, base_path(), [
        'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    ]);

    $process->setTimeout(300);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    if (! $process->isSuccessful()) {
        $this->error('Fine-tuning job event check failed.');

        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Check OpenAI fine-tuning job events using Python');

Artisan::command('ai:embed-knowledge {input} {--output=storage/app/ai/embedded-knowledge.json} {--model=text-embedding-3-large}', function () {
    $python = env('PYTHON_BIN', 'python');
    $input = base_path((string) $this->argument('input'));
    $output = base_path((string) $this->option('output'));

    $command = [
        $python,
        base_path('python/embed_knowledge.py'),
        '--input',
        $input,
        '--output',
        $output,
        '--model',
        (string) $this->option('model'),
    ];

    $process = new Process($command, base_path(), [
        'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
    ]);

    $process->setTimeout(300);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    if (! $process->isSuccessful()) {
        $this->error('Python knowledge embedding failed.');

        return self::FAILURE;
    }

    $this->info('Python knowledge embedding completed.');

    return self::SUCCESS;
})->purpose('Chunk and embed a text knowledge file using the Python OpenAI workflow');
