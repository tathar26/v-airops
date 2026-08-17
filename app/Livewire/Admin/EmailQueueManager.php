<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class EmailQueueManager extends Component
{
    use WithPagination;

    public $activeTab = 'pending'; // 'pending' or 'failed'
    public $search = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function retryFailedJob($id)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            session()->flash('message', "Job #{$id} has been queued for retry.");
        } catch (\Exception $e) {
            session()->flash('error', "Failed to retry job: " . $e->getMessage());
        }
    }

    public function retryAllFailed()
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            session()->flash('message', "All failed email jobs queued for retry.");
        } catch (\Exception $e) {
            session()->flash('error', "Failed to retry all jobs: " . $e->getMessage());
        }
    }

    public function deleteJob($id)
    {
        DB::table('jobs')->where('id', $id)->delete();
        session()->flash('message', "Pending job #{$id} deleted from queue.");
    }

    public function deleteFailedJob($id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();
        session()->flash('message', "Failed job record #{$id} removed.");
    }

    public function clearAllFailed()
    {
        Artisan::call('queue:flush');
        session()->flash('message', "All failed jobs have been cleared.");
    }

    public function purgePendingQueue()
    {
        DB::table('jobs')->truncate();
        session()->flash('message', "All pending queued jobs purged.");
    }

    public function render()
    {
        $pendingJobsCount = DB::table('jobs')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();

        if ($this->activeTab === 'failed') {
            $jobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->paginate(15);
        } else {
            $jobs = DB::table('jobs')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        // Helper to parse job payload for display
        $formattedJobs = collect($jobs->items())->map(function ($job) {
            $payload = json_decode($job->payload, true);
            $displayName = $payload['displayName'] ?? ($payload['job'] ?? 'Unknown Job');
            
            // Extract recipient email or context if present in serialized command
            $recipientEmail = 'N/A';
            if (isset($payload['data']['command'])) {
                if (preg_match('/"email";s:\d+:"([^"]+)"/', $payload['data']['command'], $matches)) {
                    $recipientEmail = $matches[1];
                }
            }

            return [
                'id' => $job->id,
                'uuid' => $job->uuid ?? null,
                'queue' => $job->queue ?? 'default',
                'job_name' => class_basename($displayName),
                'full_name' => $displayName,
                'recipient' => $recipientEmail,
                'attempts' => $job->attempts ?? 0,
                'created_at' => isset($job->created_at) ? Carbon::createFromTimestamp($job->created_at)->diffForHumans() : null,
                'failed_at' => isset($job->failed_at) ? Carbon::parse($job->failed_at)->diffForHumans() : null,
                'exception' => isset($job->exception) ? \Illuminate\Support\Str::limit($job->exception, 150) : null,
            ];
        });

        return view('livewire.admin.email-queue-manager', [
            'jobsList' => $formattedJobs,
            'paginator' => $jobs,
            'pendingJobsCount' => $pendingJobsCount,
            'failedJobsCount' => $failedJobsCount,
        ])->layout('layouts.app');
    }
}
