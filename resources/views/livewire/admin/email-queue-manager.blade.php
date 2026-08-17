<div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-xl">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-white">Email Queue Monitor</h1>
            </div>
            <p class="text-slate-400 text-sm">Monitor pending email dispatches, inspect failure logs, and trigger manual retries.</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($activeTab === 'failed' && $failedJobsCount > 0)
                <button wire:click="retryAllFailed" wire:loading.attr="disabled"
                    class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Retry All Failed
                </button>
                <button wire:click="clearAllFailed" onclick="return confirm('Clear all failed jobs?')"
                    class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-rose-600/20 hover:text-rose-400 border border-slate-700 text-slate-300 font-bold text-xs transition-all">
                    Clear Failed
                </button>
            @elseif ($activeTab === 'pending' && $pendingJobsCount > 0)
                <button wire:click="purgePendingQueue" onclick="return confirm('Purge all pending jobs from queue?')"
                    class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-rose-600/20 hover:text-rose-400 border border-slate-700 text-slate-300 font-bold text-xs transition-all">
                    Purge Queue
                </button>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center justify-between">
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-1">
        <button wire:click="setTab('pending')"
            class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2.5 {{ $activeTab === 'pending' ? 'bg-sky-500/10 text-sky-400 border border-sky-500/20' : 'text-slate-400 hover:text-white' }}">
            <span>Pending Queue</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono {{ $pendingJobsCount > 0 ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-400' }}">
                {{ $pendingJobsCount }}
            </span>
        </button>

        <button wire:click="setTab('failed')"
            class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2.5 {{ $activeTab === 'failed' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'text-slate-400 hover:text-white' }}">
            <span>Failed Jobs</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono {{ $failedJobsCount > 0 ? 'bg-rose-500 text-white' : 'bg-slate-800 text-slate-400' }}">
                {{ $failedJobsCount }}
            </span>
        </button>
    </div>

    <!-- Queue Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Job Class</th>
                        <th class="py-4 px-6">Target Recipient</th>
                        <th class="py-4 px-6">Queue</th>
                        <th class="py-4 px-6">Attempts</th>
                        <th class="py-4 px-6">{{ $activeTab === 'failed' ? 'Failed At' : 'Queued At' }}</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-sm">
                    @forelse ($jobsList as $job)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-xs text-slate-400">#{{ $job['id'] }}</td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-white">{{ $job['job_name'] }}</div>
                                <div class="text-[11px] text-slate-500 font-mono truncate max-w-xs">{{ $job['full_name'] }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-sky-400 font-mono text-xs">
                                    {{ $job['recipient'] }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-xs font-mono">
                                    {{ $job['queue'] }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs text-slate-300">
                                {{ $job['attempts'] }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                                {{ $job['failed_at'] ?? $job['created_at'] ?? 'Just now' }}
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                @if ($activeTab === 'failed')
                                    <button wire:click="retryFailedJob({{ $job['id'] }})" title="Retry job"
                                        class="px-3 py-1.5 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/20 font-bold text-xs transition-all">
                                        Retry
                                    </button>
                                    <button wire:click="deleteFailedJob({{ $job['id'] }})" title="Delete record"
                                        class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-xs transition-all">
                                        Delete
                                    </button>
                                @else
                                    <button wire:click="deleteJob({{ $job['id'] }})" title="Remove from queue"
                                        class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-xs transition-all">
                                        Remove
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($activeTab === 'failed' && $job['exception'])
                            <tr class="bg-rose-500/5">
                                <td colspan="7" class="px-6 py-3 border-t border-rose-500/10">
                                    <div class="text-xs font-mono text-rose-400/90 whitespace-pre-wrap">
                                        <strong>Error:</strong> {{ $job['exception'] }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 text-sm">
                                No {{ $activeTab }} email jobs currently in the queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($paginator->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
</div>
