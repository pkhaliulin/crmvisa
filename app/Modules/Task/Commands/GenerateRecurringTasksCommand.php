<?php

namespace App\Modules\Task\Commands;

use App\Modules\Task\Models\AgencyTask;
use Illuminate\Console\Command;

class GenerateRecurringTasksCommand extends Command
{
    protected $signature = 'tasks:generate-recurring';
    protected $description = 'Создать копии повторяющихся задач, которые были закрыты/верифицированы';

    public function handle(): int
    {
        $tasks = AgencyTask::recurring()
            ->whereIn('status', ['closed', 'verified'])
            ->whereDoesntHave('recurrenceChildren', function ($q) {
                // Нет дочерних задач с due_date >= сегодня
                $q->where('due_date', '>=', now()->toDateString())
                  ->whereNotIn('status', ['cancelled']);
            })
            ->get();

        $created = 0;

        foreach ($tasks as $task) {
            $next = $task->createNextRecurrence();
            if ($next) {
                $created++;
                $this->line("  {$task->title} -> {$next->due_date->format('Y-m-d')}");
            }
        }

        $this->info("Создано повторяющихся задач: {$created}");

        return self::SUCCESS;
    }
}
